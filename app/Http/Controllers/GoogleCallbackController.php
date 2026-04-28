<?php

namespace App\Http\Controllers;

use App\Jobs\SyncCategoryFoldersToDrive;
use App\Models\ConnectedAccount;
use App\Models\User;
use App\Services\GoogleDriveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleCallbackController extends Controller
{
    /**
     * Handle the Google OAuth callback.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            return redirect()->route('connected-accounts.edit')
                ->with('google-error', __('Google authorization was cancelled or denied.'));
        }

        if (! $request->has('code') || ! $request->has('state')) {
            return redirect()->route('connected-accounts.edit')
                ->with('google-error', __('Missing authorization code. Please try again.'));
        }

        try {
            $stateData = json_decode(base64_decode($request->state), true);

            if (! $stateData || ! isset($stateData['user_id'])) {
                return redirect()->route('connected-accounts.edit')
                    ->with('google-error', __('Invalid state parameter.'));
            }

            // Verify state token is not older than 10 minutes
            if (time() - ($stateData['timestamp'] ?? 0) > 600) {
                return redirect()->route('connected-accounts.edit')
                    ->with('google-error', __('Authorization expired. Please try again.'));
            }

            $user = User::find($stateData['user_id']);

            if (! $user) {
                return redirect()->route('connected-accounts.edit')
                    ->with('google-error', __('User not found.'));
            }

            // Exchange code for tokens
            $result = GoogleDriveService::exchangeCode($request->code);
            $token = $result['token'];
            $email = $result['email'];

            // Upsert saved account
            $existing = ConnectedAccount::query()
                ->where('user_id', $user->id)
                ->where('provider', 'google_drive')
                ->first();

            if ($existing) {
                $existing->update([
                    'email' => $email,
                    'credentials' => [
                        'access_token' => $token['access_token'],
                        'refresh_token' => $token['refresh_token'] ?? $existing->credentials['refresh_token'] ?? null,
                        'token_type' => $token['token_type'] ?? 'Bearer',
                        'created' => $token['created'] ?? time(),
                    ],
                    'expires_at' => isset($token['expires_in']) ? now()->addSeconds($token['expires_in']) : null,
                    'is_active' => true,
                ]);
            } else {
                ConnectedAccount::create([
                    'organisation_id' => $user->organisation_id,
                    'user_id' => $user->id,
                    'provider' => 'google_drive',
                    'email' => $email,
                    'credentials' => [
                        'access_token' => $token['access_token'],
                        'refresh_token' => $token['refresh_token'] ?? null,
                        'token_type' => $token['token_type'] ?? 'Bearer',
                        'created' => $token['created'] ?? time(),
                    ],
                    'expires_at' => isset($token['expires_in']) ? now()->addSeconds($token['expires_in']) : null,
                    'is_active' => true,
                ]);
            }

            Log::info('Google Drive account connected', [
                'user_id' => $user->id,
                'email' => $email,
            ]);

            // Sync category folders to the newly connected Drive
            if ($user->organisation_id) {
                SyncCategoryFoldersToDrive::dispatch($user->organisation_id, $user->id);
            }

            // If connecting during onboarding, redirect back there
            if (($stateData['redirect'] ?? null) === 'onboarding') {
                return redirect()->route('onboarding', ['step' => 3]);
            }

            return redirect()->route('connected-accounts.edit')
                ->with('google-connected', true)
                ->with('google-email', $email);

        } catch (\Exception $e) {
            Log::error('Google OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // If connecting during onboarding, redirect back there on error
            if (isset($stateData) && ($stateData['redirect'] ?? null) === 'onboarding') {
                return redirect()->route('onboarding', ['step' => 3]);
            }

            return redirect()->route('connected-accounts.edit')
                ->with('google-error', __('Failed to connect Google account. Please try again.'));
        }
    }
}
