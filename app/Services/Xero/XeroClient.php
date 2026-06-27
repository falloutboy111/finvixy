<?php

namespace App\Services\Xero;

use App\Exceptions\XeroApiException;
use App\Exceptions\XeroReauthRequired;
use App\Models\XeroConnection;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class XeroClient
{
    private XeroTokenService $tokenService;

    public function __construct(private readonly XeroConnection $conn)
    {
        $this->tokenService = new XeroTokenService;
    }

    public function get(string $endpoint, array $query = []): array
    {
        return $this->request('get', $endpoint, $query);
    }

    public function post(string $endpoint, array $payload): array
    {
        return $this->request('post', $endpoint, $payload);
    }

    private function request(string $method, string $endpoint, array $data, bool $isRetry = false): array
    {
        $token = $this->tokenService->validAccessTokenFor($this->conn);
        $url = rtrim(config('xero.api_base'), '/').'/'.$endpoint;

        $pending = Http::withToken($token)
            ->withHeaders([
                'Xero-tenant-id' => $this->conn->tenant_id,
                'Accept' => 'application/json',
            ]);

        $response = $method === 'get'
            ? $pending->get($url, $data)
            : $pending->post($url, $data);

        if ($response->status() === 401 && ! $isRetry) {
            // Expired/invalid token — force a refresh and retry once
            try {
                $this->conn->update(['expires_at' => now()->subMinute()]);
                return $this->request($method, $endpoint, $data, isRetry: true);
            } catch (XeroReauthRequired $e) {
                throw $e;
            }
        }

        if (! $response->successful()) {
            throw new XeroApiException(
                "Xero API error {$response->status()} on {$method} {$endpoint}",
                $response->json() ?? [],
            );
        }

        return $response->json();
    }
}
