<?php

namespace App\Services\Xero;

use App\Exceptions\XeroReauthRequired;
use App\Models\XeroConnection;
use Illuminate\Support\Facades\Http;

class XeroTokenService
{
    /**
     * Returns a valid bearer token for the given connection, refreshing if needed.
     *
     * @throws XeroReauthRequired if the refresh token has expired or refresh fails
     */
    public function validAccessTokenFor(XeroConnection $conn): string
    {
        if (! $conn->isExpiringSoon()) {
            return $conn->access_token;
        }

        return $this->refresh($conn);
    }

    /**
     * @throws XeroReauthRequired
     */
    private function refresh(XeroConnection $conn): string
    {
        try {
            $response = Http::withBasicAuth(
                config('xero.client_id'),
                config('xero.client_secret'),
            )->asForm()->post(config('xero.token_url'), [
                'grant_type' => 'refresh_token',
                'refresh_token' => $conn->refresh_token,
            ]);

            if (! $response->successful()) {
                $this->markNeedsReconnect($conn);
                throw new XeroReauthRequired;
            }

            $tokens = $response->json();

            // Refresh tokens rotate — always persist the new one
            $conn->update([
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'expires_at' => now()->addSeconds($tokens['expires_in']),
            ]);

            return $tokens['access_token'];

        } catch (XeroReauthRequired $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->markNeedsReconnect($conn);
            throw new XeroReauthRequired;
        }
    }

    private function markNeedsReconnect(XeroConnection $conn): void
    {
        // Set expires_at to the past so every subsequent call sees the token as expired
        $conn->update(['expires_at' => now()->subMinute()]);
    }
}
