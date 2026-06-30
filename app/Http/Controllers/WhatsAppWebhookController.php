<?php

namespace App\Http\Controllers;

use App\Jobs\InvokeAgentJob;
use App\Jobs\ProcessExpenseImage;
use App\Models\Expense;
use App\Models\PendingConfirmation;
use App\Models\User;
use App\Models\WhatsappWebhook;
use App\Services\ConfirmationService;
use App\Services\OrgStorageService;
use App\Services\PlanLimitService;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class WhatsAppWebhookController extends Controller
{
    // WhatsApp webhook enum types that the DB column accepts
    private const KNOWN_TYPES = ['text', 'image', 'video', 'audio', 'document', 'location', 'contacts'];

    public function __construct(
        protected WhatsAppService $whatsApp,
    ) {}

    // -------------------------------------------------------------------------
    // GET — Meta hub.challenge verification
    // -------------------------------------------------------------------------

    public function verify(Request $request): Response
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token')) {
            Log::info('WhatsApp webhook verified');

            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('WhatsApp webhook verification failed', [
            'mode'           => $mode,
            'token_received' => $token,
        ]);

        return response('Forbidden', 403);
    }

    // -------------------------------------------------------------------------
    // POST — Inbound messages
    // -------------------------------------------------------------------------

    public function handle(Request $request): JsonResponse
    {
        // Reject requests whose HMAC-SHA256 signature does not match
        if (! $this->verifySignature($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        try {
            Log::info('WhatsApp webhook received', ['payload' => $request->all()]);

            $entry    = $request->input('entry.0', []);
            $changes  = $entry['changes'][0] ?? [];
            $value    = $changes['value'] ?? [];
            $messages = $value['messages'] ?? [];

            if (empty($messages)) {
                return response()->json('', 200);
            }

            foreach ($messages as $message) {
                $this->processMessage($message);
            }

            return response()->json(['success' => true], 200);
        } catch (\Throwable $e) {
            Log::error('WhatsApp webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Always 200 — prevents Meta from retrying indefinitely
            return response()->json('', 200);
        }
    }

    // -------------------------------------------------------------------------
    // HMAC verification
    // -------------------------------------------------------------------------

    /**
     * Verify the X-Hub-Signature-256 header using the raw request body.
     * Meta signs the exact raw bytes — do not re-encode before hashing.
     */
    protected function verifySignature(Request $request): bool
    {
        $appSecret = config('services.whatsapp.app_secret');

        if (empty($appSecret)) {
            // No secret configured; allow through but warn so it's visible in logs
            Log::warning('WHATSAPP_APP_SECRET not set — skipping signature verification');

            return true;
        }

        $rawBody  = $request->getContent();
        $expected = 'sha256='.hash_hmac('sha256', $rawBody, $appSecret);
        $received = $request->header('X-Hub-Signature-256', '');

        if (! hash_equals($expected, $received)) {
            Log::warning('WhatsApp webhook HMAC mismatch', [
                'header_present' => $received !== '',
            ]);

            return false;
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Message routing
    // -------------------------------------------------------------------------

    /**
     * Dispatch a single inbound message to the right handler.
     */
    protected function processMessage(array $message): void
    {
        $type      = $message['type'] ?? null;
        $from      = $message['from'] ?? null;
        $messageId = $message['id'] ?? null;

        // Store unknown types (interactive, etc.) as 'unknown' to satisfy DB enum
        $dbType = in_array($type, self::KNOWN_TYPES, true) ? $type : 'unknown';

        // Idempotency — Meta retries on non-200; skip already-processed IDs
        $webhook = WhatsappWebhook::query()->firstOrCreate(
            ['message_id' => $messageId],
            [
                'from'    => $from,
                'type'    => $dbType,
                'payload' => $message,
                'status'  => 'received',
            ]
        );

        if (! $webhook->wasRecentlyCreated) {
            Log::info('WhatsApp duplicate webhook skipped', ['message_id' => $messageId]);

            return;
        }

        // ---- Resolve identity from sender phone number ----------------------
        // organisation_id and user_id are ALWAYS derived here from the verified
        // phone → DB lookup. They are NEVER taken from message content.
        // This is the multi-tenant security boundary.
        // --------------------------------------------------------------------
        $fromWithPlus = '+'.$from;

        $user = User::query()
            ->where('whatsapp_enabled', true)
            ->where(function ($q) use ($from, $fromWithPlus) {
                $q->where('whatsapp_number', $from)
                    ->orWhere('whatsapp_number', $fromWithPlus);
            })
            ->first();

        if (! $user) {
            Log::info('WhatsApp sender not registered', ['from' => $from]);
            $this->whatsApp->sendText(
                $from,
                "This number isn't linked to a Finvixy account. Please link your WhatsApp number in Settings first."
            );
            $webhook->update(['status' => 'ignored']);

            return;
        }

        $webhook->update([
            'user_id'         => $user->id,
            'organisation_id' => $user->organisation_id,
            'status'          => 'processing',
        ]);

        match (true) {
            in_array($type, ['image', 'document'], true) => $this->handleMediaMessage($message, $from, $messageId, $user, $webhook),
            $type === 'text'                              => $this->handleTextMessage($message, $from, $user, $webhook),
            $type === 'interactive'                       => $this->handleInteractiveReply($message, $from, $user, $webhook),
            default                                       => $webhook->update(['status' => 'ignored']),
        };
    }

    // -------------------------------------------------------------------------
    // Handler: image / document (receipt OCR flow)
    // -------------------------------------------------------------------------

    protected function handleMediaMessage(
        array $message,
        string $from,
        string $messageId,
        User $user,
        WhatsappWebhook $webhook
    ): void {
        $type = $message['type'];

        // Plan receipt-limit gate
        $limit = app(PlanLimitService::class)->checkReceiptLimit($user);

        if (! $limit['allowed']) {
            $webhook->update([
                'status'        => 'ignored',
                'error_message' => 'Monthly receipt limit reached',
            ]);
            $this->whatsApp->sendText(
                $from,
                "You've used all {$limit['used']}/{$limit['limit']} receipts this month. "
                .'Upgrade at '.config('app.url').'/settings/billing'
            );

            return;
        }

        $media    = $type === 'image' ? ($message['image'] ?? []) : ($message['document'] ?? []);
        $mediaId  = $media['id'] ?? null;
        $mimeType = $media['mime_type'] ?? 'image/jpeg';
        $caption  = $media['caption'] ?? null;

        if (! $mediaId) {
            Log::warning('WhatsApp message has no media ID', ['message_id' => $messageId]);
            $webhook->update(['status' => 'failed', 'error_message' => 'No media ID']);

            return;
        }

        $content = $this->whatsApp->downloadMedia($mediaId);

        if (! $content) {
            $webhook->update(['status' => 'failed', 'error_message' => 'Media download failed']);
            $this->whatsApp->sendText($from, 'Failed to download the image. Please try sending it again.');

            return;
        }

        $ext            = $this->whatsApp->extensionFromMime($mimeType);
        $storageService = new OrgStorageService($user->organisation);
        $s3Path         = $storageService->storeRaw('receipts', $content, Str::uuid().'.'.$ext);

        $expense = Expense::query()->create([
            'organisation_id'   => $user->organisation_id,
            'user_id'           => $user->id,
            'name'              => $caption ?: 'WhatsApp Receipt',
            'amount'            => 0,
            'date'              => now(),
            'receipt_path'      => $s3Path,
            'status'            => 'pending',
            'is_duplicate'      => false,
            'additional_fields' => [
                'source'              => 'whatsapp',
                'whatsapp_from'       => $from,
                'whatsapp_message_id' => $messageId,
                'whatsapp_caption'    => $caption,
            ],
        ]);

        $webhook->update(['expense_id' => $expense->id]);

        ProcessExpenseImage::dispatch($expense);

        $this->whatsApp->sendText($from, "Receipt received! Scanning it now — you'll get the details shortly.");

        Log::info('WhatsApp receipt queued for processing', [
            'expense_id' => $expense->id,
            'user_id'    => $user->id,
            'from'       => $from,
        ]);
    }

    // -------------------------------------------------------------------------
    // Handler: text → AgentCore conversational agent
    // -------------------------------------------------------------------------

    protected function handleTextMessage(
        array $message,
        string $from,
        User $user,
        WhatsappWebhook $webhook
    ): void {
        $body = trim($message['text']['body'] ?? '');

        if ($body === '') {
            $webhook->update(['status' => 'ignored']);

            return;
        }

        // Anti-abuse rate limit for authenticated users (sender auth already enforced upstream).
        $limit = (int) config('services.agent_tools.hourly_limit', 50);
        $key   = 'agent-invoke:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $this->whatsApp->sendText(
                $from,
                "You're sending a lot of messages — please try again in a little while."
            );
            $webhook->update(['status' => 'ignored', 'error_message' => 'Rate limited']);

            return;
        }

        RateLimiter::hit($key, 3600);

        // If the user previously tapped "Type a project name", route their text to
        // the agent with enough context to identify the project and call set_expense_project.
        $typeReply = PendingConfirmation::where('user_id', $user->id)
            ->where('awaiting_type_reply', true)
            ->where('expires_at', '>', now())
            ->first();

        $injectProjects = false;

        if ($typeReply) {
            $body = '[Project assignment] The user is replying to a project selection prompt.'
                ." Expense #{$typeReply->expense_id} needs a project assigned."
                ." Their message: \"{$body}\"."
                .' Match the project by name and call set_expense_project to assign it.'
                .' Confirm the assignment in a short reply.';

            $injectProjects = true; // job will append the full project list before invoking
            $typeReply->delete();
        }

        // Offload to a queue so we return 200 to Meta within the webhook timeout.
        // The job invokes the agent and sends the reply in the background.
        InvokeAgentJob::dispatch(
            $from,
            $body,
            $user->organisation_id,
            $user->id,
            $webhook->id,
            $injectProjects,
            $injectProjects, // holding message when project list injected (extra CRM round-trip)
        );

        Log::info('WhatsApp text message queued for agent', [
            'from'    => $from,
            'user_id' => $user->id,
            'length'  => strlen($body),
        ]);
    }

    // -------------------------------------------------------------------------
    // Handler: interactive replies (confirmation flow stub)
    // -------------------------------------------------------------------------

    /**
     * Handle button_reply and list_reply messages from the category/project confirmation flow.
     * Reply IDs are structured: `cat:<expense_id>:<slug>`, `proj:<expense_id>:<uuid_or_special>`.
     * All routing is deterministic — no LLM involved.
     */
    protected function handleInteractiveReply(
        array $message,
        string $from,
        User $user,
        WhatsappWebhook $webhook
    ): void {
        $interactive = $message['interactive'] ?? [];
        $replyType   = $interactive['type'] ?? null; // 'button_reply' | 'list_reply'
        $replyId     = $interactive[$replyType]['id'] ?? null;

        Log::info('WhatsApp interactive reply received', [
            'from'       => $from,
            'user_id'    => $user->id,
            'reply_type' => $replyType,
            'reply_id'   => $replyId,
        ]);

        if (! $replyId) {
            $webhook->update(['status' => 'ignored']);
            return;
        }

        $ack = app(ConfirmationService::class)->handleInteractiveTap($replyId, $user, $this->whatsApp, $from);

        // __more__ returns '' — the list was already sent; don't send an extra text ack
        if ($ack !== '') {
            $this->whatsApp->sendText($from, $ack);
        }

        $webhook->update(['status' => 'processed']);
    }
}
