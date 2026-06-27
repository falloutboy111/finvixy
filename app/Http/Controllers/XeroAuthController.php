<?php

namespace App\Http\Controllers;

use App\Exceptions\XeroReauthRequired;
use App\Models\XeroConnection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class XeroAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        $request->session()->put('xero_oauth_state', $state);

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => config('xero.client_id'),
            'redirect_uri' => config('xero.redirect_uri'),
            'scope' => config('xero.scopes'),
            'state' => $state,
        ]);

        return redirect(config('xero.authorize_url').'?'.$query);
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            return redirect()->route('connected-accounts.edit')
                ->with('xero-error', __('Xero authorization was cancelled or denied.'));
        }

        if (! $request->has('code') || ! $request->has('state')) {
            return redirect()->route('connected-accounts.edit')
                ->with('xero-error', __('Missing authorization parameters. Please try again.'));
        }

        if ($request->state !== $request->session()->pull('xero_oauth_state')) {
            abort(403, 'Invalid OAuth state.');
        }

        try {
            $tokenResponse = Http::withBasicAuth(
                config('xero.client_id'),
                config('xero.client_secret'),
            )->asForm()->post(config('xero.token_url'), [
                'grant_type' => 'authorization_code',
                'code' => $request->code,
                'redirect_uri' => config('xero.redirect_uri'),
            ]);

            if (! $tokenResponse->successful()) {
                return redirect()->route('connected-accounts.edit')
                    ->with('xero-error', __('Failed to exchange authorization code. Please try again.'));
            }

            $tokens = $tokenResponse->json();

            // Fetch tenant connections
            $connectionsResponse = Http::withToken($tokens['access_token'])
                ->get(config('xero.connections_url'));

            if (! $connectionsResponse->successful() || empty($connectionsResponse->json())) {
                return redirect()->route('connected-accounts.edit')
                    ->with('xero-error', __('Could not retrieve Xero organisation. Please try again.'));
            }

            // TODO: if the user has multiple orgs, present a tenant picker here
            // For now, take the first tenant returned
            $tenant = $connectionsResponse->json()[0];

            XeroConnection::updateOrCreate(
                ['user_id' => auth()->id()],
                [
                    'tenant_id' => $tenant['tenantId'],
                    'tenant_name' => $tenant['tenantName'] ?? null,
                    'access_token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'expires_at' => now()->addSeconds($tokens['expires_in']),
                ],
            );

            return redirect()->route('connected-accounts.edit')
                ->with('xero-connected', true)
                ->with('xero-tenant', $tenant['tenantName'] ?? $tenant['tenantId']);

        } catch (\Exception $e) {
            return redirect()->route('connected-accounts.edit')
                ->with('xero-error', __('Failed to connect Xero account. Please try again.'));
        }
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $conn = XeroConnection::where('user_id', auth()->id())->first();

        if ($conn) {
            // Optionally revoke the connection on Xero's side
            try {
                Http::withToken($conn->access_token)
                    ->delete(config('xero.connections_url').'/'.$conn->tenant_id);
            } catch (\Exception) {
                // Best-effort revocation — always delete locally
            }

            $conn->delete();
        }

        return redirect()->route('connected-accounts.edit')
            ->with('xero-disconnected', true);
    }
}
