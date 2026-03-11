<?php

namespace App\Services;

use App\Models\RateLimitLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Rate Limiter Service
 * 
 * Enforces usage quotas per organization and user.
 * Prevents hammering of AWS services and ensures fair usage.
 */
class RateLimiterService
{
    // Default quotas (per day)
    private const DEFAULT_QUOTAS = [
        'textract_calls' => 1000,
        'bedrock_calls' => 1000,
        'image_uploads' => 500,
        'whatsapp_messages' => 100,
    ];

    /**
     * Check if organisation can perform an action.
     * Returns true if allowed, false if rate limited.
     *
     * @param int $organisationId
     * @param string $resourceType One of: textract_calls, bedrock_calls, image_uploads, whatsapp_messages
     * @param int $requestCount Number of requests being made
     *
     * @return array{allowed: bool, remaining: int, reset_at: \DateTime|null, message: string|null}
     */
    public function checkQuota(int $organisationId, string $resourceType, int $requestCount = 1): array
    {
        $cacheKey = "quota:{$organisationId}:{$resourceType}";
        $resetKey = "quota:{$organisationId}:{$resourceType}:reset_at";

        $limit = self::DEFAULT_QUOTAS[$resourceType] ?? 100;
        $currentUsage = (int) Cache::get($cacheKey, 0);
        $remaining = max(0, $limit - $currentUsage);

        Log::debug("📊 Quota check", [
            'organisation_id' => $organisationId,
            'resource_type' => $resourceType,
            'current_usage' => $currentUsage,
            'limit' => $limit,
            'remaining' => $remaining,
            'requested' => $requestCount,
        ]);

        // Check if request would exceed limit
        if ($currentUsage + $requestCount > $limit) {
            $resetAt = Cache::get($resetKey);

            RateLimitLog::create([
                'organisation_id' => $organisationId,
                'resource_type' => $resourceType,
                'quota_limit' => $limit,
                'usage_count' => $currentUsage,
                'remaining' => $remaining,
                'was_throttled' => true,
                'reset_at' => $resetAt,
            ]);

            Log::warning("🚫 Rate limit exceeded", [
                'organisation_id' => $organisationId,
                'resource_type' => $resourceType,
                'current_usage' => $currentUsage,
                'limit' => $limit,
                'requested' => $requestCount,
            ]);

            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_at' => $resetAt ? new \DateTime($resetAt) : null,
                'message' => "Rate limit exceeded for $resourceType. Limit: $limit/day. Reset at: " . ($resetAt ?? 'unknown'),
            ];
        }

        // Increment usage counter (expires at midnight)
        $ttl = $this->getSecondsUntilMidnight();
        Cache::put($cacheKey, $currentUsage + $requestCount, $ttl);

        // Set reset time if not already set
        if (! Cache::has($resetKey)) {
            $resetAt = now()->endOfDay();
            Cache::put($resetKey, $resetAt, $ttl);
        }

        // Log successful quota usage
        RateLimitLog::create([
            'organisation_id' => $organisationId,
            'resource_type' => $resourceType,
            'quota_limit' => $limit,
            'usage_count' => $currentUsage + $requestCount,
            'remaining' => $remaining - $requestCount,
            'was_throttled' => false,
            'reset_at' => Cache::get($resetKey),
        ]);

        return [
            'allowed' => true,
            'remaining' => $remaining - $requestCount,
            'reset_at' => Cache::get($resetKey) ? new \DateTime(Cache::get($resetKey)) : null,
            'message' => null,
        ];
    }

    /**
     * Reset quota for testing/admin purposes.
     */
    public function resetQuota(int $organisationId, string $resourceType): void
    {
        Cache::forget("quota:{$organisationId}:{$resourceType}");
        Cache::forget("quota:{$organisationId}:{$resourceType}:reset_at");

        Log::info("✅ Quota reset", [
            'organisation_id' => $organisationId,
            'resource_type' => $resourceType,
        ]);
    }

    /**
     * Get remaining quota for a resource.
     */
    public function getRemainingQuota(int $organisationId, string $resourceType): int
    {
        $limit = self::DEFAULT_QUOTAS[$resourceType] ?? 100;
        $currentUsage = (int) Cache::get("quota:{$organisationId}:{$resourceType}", 0);

        return max(0, $limit - $currentUsage);
    }

    /**
     * Get seconds until midnight (for cache TTL).
     */
    private function getSecondsUntilMidnight(): int
    {
        return (int) now()->endOfDay()->diffInSeconds();
    }
}
