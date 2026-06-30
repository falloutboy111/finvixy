<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $accessToken;

    protected string $phoneNumberId;

    protected string $apiVersion;

    public function __construct()
    {
        $this->accessToken = config('services.whatsapp.access_token');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->apiVersion = config('services.whatsapp.api_version', 'v21.0');
    }

    /**
     * Send a text message via WhatsApp Business API.
     */
    public function sendMessage(string $to, string $message): bool
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->post($this->apiUrl('messages'), [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $to,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $message,
                    ],
                ]);

            if ($response->successful()) {
                Log::info('WhatsApp message sent', [
                    'to' => $to,
                    'message_id' => $response->json('messages.0.id'),
                ]);

                return true;
            }

            Log::error('WhatsApp message failed', [
                'to' => $to,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('WhatsApp sendMessage exception', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Download media (image/document) from WhatsApp CDN.
     */
    public function downloadMedia(string $mediaId): ?string
    {
        try {
            // Step 1: Get the media URL
            $metaResponse = Http::withToken($this->accessToken)
                ->get($this->graphUrl($mediaId));

            if (! $metaResponse->successful()) {
                Log::error('WhatsApp: failed to get media URL', [
                    'media_id' => $mediaId,
                    'status' => $metaResponse->status(),
                ]);

                return null;
            }

            $mediaUrl = $metaResponse->json('url');

            if (! $mediaUrl) {
                Log::error('WhatsApp: media URL missing in response', ['media_id' => $mediaId]);

                return null;
            }

            // Step 2: Download the actual binary
            $fileResponse = Http::withToken($this->accessToken)->get($mediaUrl);

            if (! $fileResponse->successful()) {
                Log::error('WhatsApp: failed to download media binary', [
                    'media_id' => $mediaId,
                    'status' => $fileResponse->status(),
                ]);

                return null;
            }

            return $fileResponse->body();
        } catch (\Throwable $e) {
            Log::error('WhatsApp downloadMedia exception', [
                'media_id' => $mediaId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Send a text message — preferred name; delegates to sendMessage().
     */
    public function sendText(string $to, string $body): bool
    {
        return $this->sendMessage($to, $body);
    }

    /**
     * Send an interactive reply-button message (max 3 buttons).
     *
     * $buttons format: [['id' => 'category:meals', 'title' => 'Meals']]
     * Button titles are truncated to 20 chars (WhatsApp limit).
     */
    public function sendButtons(string $to, string $body, array $buttons): bool
    {
        try {
            $interactiveButtons = array_map(fn ($btn) => [
                'type'  => 'reply',
                'reply' => [
                    'id'    => (string) $btn['id'],
                    'title' => mb_substr((string) $btn['title'], 0, 20),
                ],
            ], array_slice($buttons, 0, 3));

            $response = Http::withToken($this->accessToken)
                ->post($this->apiUrl('messages'), [
                    'messaging_product' => 'whatsapp',
                    'recipient_type'    => 'individual',
                    'to'                => $to,
                    'type'              => 'interactive',
                    'interactive'       => [
                        'type'   => 'button',
                        'body'   => ['text' => $body],
                        'action' => ['buttons' => $interactiveButtons],
                    ],
                ]);

            if ($response->successful()) {
                Log::info('WhatsApp buttons sent', ['to' => $to, 'count' => count($interactiveButtons)]);

                return true;
            }

            Log::error('WhatsApp sendButtons failed', [
                'to'     => $to,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('WhatsApp sendButtons exception', ['to' => $to, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Send an interactive list message (up to 10 rows across sections).
     *
     * $sections format:
     *   [['title' => 'Heading', 'rows' => [['id' => '...', 'title' => '...', 'description' => '...']]]]
     */
    public function sendList(string $to, string $header, string $body, array $sections): bool
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->post($this->apiUrl('messages'), [
                    'messaging_product' => 'whatsapp',
                    'recipient_type'    => 'individual',
                    'to'                => $to,
                    'type'              => 'interactive',
                    'interactive'       => [
                        'type'   => 'list',
                        'header' => ['type' => 'text', 'text' => $header],
                        'body'   => ['text' => $body],
                        'action' => [
                            'button'   => 'Choose',
                            'sections' => $sections,
                        ],
                    ],
                ]);

            if ($response->successful()) {
                Log::info('WhatsApp list sent', ['to' => $to]);

                return true;
            }

            Log::error('WhatsApp sendList failed', [
                'to'     => $to,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('WhatsApp sendList exception', ['to' => $to, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Send a combined read-receipt + typing indicator for an inbound message.
     *
     * Uses a 3-second timeout and swallows all exceptions — a failed indicator
     * must never block or delay the actual agent reply. The indicator
     * auto-expires after ~25 seconds or is dismissed when the real reply arrives.
     */
    public function sendTypingIndicator(string $to, string $messageId): void
    {
        if (! config('services.whatsapp.typing_indicator', true)) {
            return;
        }

        try {
            $response = Http::timeout(3)
                ->withToken($this->accessToken)
                ->post($this->apiUrl('messages'), [
                    'messaging_product' => 'whatsapp',
                    'status'            => 'read',
                    'message_id'        => $messageId,
                    'typing_indicator'  => ['type' => 'text'],
                ]);

            if (! $response->successful()) {
                Log::warning('WhatsApp typing indicator failed', [
                    'to'     => $to,
                    'status' => $response->status(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('WhatsApp typing indicator exception', [
                'to'    => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Resolve a file extension from a MIME type.
     */
    public function extensionFromMime(string $mimeType): string
    {
        return match ($mimeType) {
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            default => 'jpg',
        };
    }

    /**
     * Build a Graph API URL for the phone-number endpoint.
     */
    protected function apiUrl(string $path): string
    {
        return "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/{$path}";
    }

    /**
     * Build a Graph API URL for a resource by ID.
     */
    protected function graphUrl(string $resourceId): string
    {
        return "https://graph.facebook.com/{$this->apiVersion}/{$resourceId}";
    }
}
