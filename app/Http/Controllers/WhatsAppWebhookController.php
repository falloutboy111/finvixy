<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessExpenseImage;
use App\Models\Expense;
use App\Models\User;
use App\Models\WhatsappWebhook;
use App\Services\OrgStorageService;
use App\Services\PlanLimitService;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        protected WhatsAppService $whatsApp,
    ) {}

    /**
     * GET — Meta webhook verification (subscribe handshake).
     */
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token')) {
            Log::info('WhatsApp webhook verified');

            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('WhatsApp webhook verification failed', [
            'mode' => $mode,
            'token_received' => $token,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * POST — Handle incoming WhatsApp messages.
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            Log::info('WhatsApp webhook received', ['payload' => $request->all()]);

            $entry = $request->input('entry.0', []);
            $changes = $entry['changes'][0] ?? [];
            $value = $changes['value'] ?? [];
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

            // Always 200 — prevents Meta from retrying
            return response()->json('', 200);
        }
    }

    /**
     * Process a single inbound WhatsApp message.
     */
    protected function processMessage(array $message): void
    {
        $type = $message['type'] ?? null;
        $from = $message['from'] ?? null;
        $messageId = $message['id'] ?? null;

        // Idempotency — skip duplicates (Meta retries)
        $webhook = WhatsappWebhook::query()->firstOrCreate(
            ['message_id' => $messageId],
            [
                'from' => $from,
                'type' => $type ?? 'unknown',
                'payload' => $message,
                'status' => 'received',
            ]
        );

        if (! $webhook->wasRecentlyCreated) {
            Log::info('WhatsApp duplicate webhook skipped', ['message_id' => $messageId]);

            return;
        }

        // Only accept image and document (PDF) messages
        if (! in_array($type, ['image', 'document'])) {
            $this->whatsApp->sendMessage(
                $from,
                "📎 Please send a *photo* or *PDF* of your receipt and I'll scan it for you."
            );
            $webhook->update(['status' => 'ignored']);

            return;
        }

        // Look up user by WhatsApp number
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
            $this->whatsApp->sendMessage(
                $from,
                "⚠️ This number isn't linked to a Finvixy account. Please link your WhatsApp number in Settings first."
            );
            $webhook->update(['status' => 'ignored']);

            return;
        }

        // Attach user + org to webhook
        $webhook->update([
            'user_id' => $user->id,
            'organisation_id' => $user->organisation_id,
            'status' => 'processing',
        ]);

        // Plan limit check
        $limit = app(PlanLimitService::class)->checkReceiptLimit($user);

        if (! $limit['allowed']) {
            $webhook->update([
                'status' => 'limit_exceeded',
                'error_message' => 'Monthly receipt limit reached',
            ]);

            $this->whatsApp->sendMessage(
                $from,
                "⚠️ You've used all *{$limit['used']}/{$limit['limit']}* receipts this month.\n\nUpgrade your plan at ".config('app.url').'/settings/billing'.' to continue scanning.'
            );

            return;
        }

        // Extract media metadata
        $media = $type === 'image' ? ($message['image'] ?? []) : ($message['document'] ?? []);
        $mediaId = $media['id'] ?? null;
        $mimeType = $media['mime_type'] ?? 'image/jpeg';
        $caption = $media['caption'] ?? null;

        if (! $mediaId) {
            Log::warning('WhatsApp message has no media ID', ['message_id' => $messageId]);
            $webhook->update(['status' => 'failed', 'error_message' => 'No media ID']);

            return;
        }

        // Download from WhatsApp CDN
        $content = $this->whatsApp->downloadMedia($mediaId);

        if (! $content) {
            $webhook->update(['status' => 'failed', 'error_message' => 'Media download failed']);
            $this->whatsApp->sendMessage($from, '❌ Failed to download the image. Please try sending it again.');

            return;
        }

        // Upload to org-storage
        $ext = $this->whatsApp->extensionFromMime($mimeType);
        $storageService = new OrgStorageService($user->organisation);
        $s3Path = $storageService->storeRaw('receipts', $content, Str::uuid().'.'.$ext);

        // Create expense
        $expense = Expense::query()->create([
            'organisation_id' => $user->organisation_id,
            'user_id' => $user->id,
            'name' => $caption ?: 'WhatsApp Receipt',
            'amount' => 0,
            'date' => now(),
            'receipt_path' => $s3Path,
            'status' => 'pending',
            'is_duplicate' => false,
            'additional_fields' => [
                'source' => 'whatsapp',
                'whatsapp_from' => $from,
                'whatsapp_message_id' => $messageId,
                'whatsapp_caption' => $caption,
            ],
        ]);

        $webhook->update(['expense_id' => $expense->id]);

        // Dispatch OCR processing
        ProcessExpenseImage::dispatch($expense);

        $this->whatsApp->sendMessage(
            $from,
            "✅ Receipt received! I'm scanning it now — you'll get the details in a moment."
        );

        Log::info('WhatsApp receipt queued for processing', [
            'expense_id' => $expense->id,
            'user_id' => $user->id,
            'from' => $from,
        ]);
    }
}
