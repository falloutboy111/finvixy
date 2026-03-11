<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Circuit Breaker Pattern Implementation
 * 
 * States:
 * - CLOSED: Normal operation
 * - OPEN: Service unavailable, fail fast
 * - HALF_OPEN: Testing if service is back up
 */
class CircuitBreakerService
{
    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';
    private const STATE_HALF_OPEN = 'half_open';

    private string $serviceName;
    private string $cacheKey;
    private int $failureThreshold = 5;
    private int $successThreshold = 2;
    private int $timeout = 60; // seconds before trying again

    public function __construct(string $serviceName)
    {
        $this->serviceName = $serviceName;
        $this->cacheKey = "circuit_breaker:{$serviceName}";
    }

    /**
     * Execute a callback within the circuit breaker.
     * If circuit is open, returns fallback or throws.
     *
     * @param callable $callback
     * @param callable|null $fallback
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function execute(callable $callback, ?callable $fallback = null): mixed
    {
        $state = $this->getState();

        Log::debug("🔌 Circuit breaker check", [
            'service' => $this->serviceName,
            'state' => $state,
        ]);

        if ($state === self::STATE_OPEN) {
            Log::warning("🚫 Circuit breaker is OPEN - Service unavailable", [
                'service' => $this->serviceName,
            ]);

            if ($fallback) {
                Log::info("↩️ Using fallback for {$this->serviceName}");

                return $fallback();
            }

            throw new \Exception("Circuit breaker is open for {$this->serviceName}. Service temporarily unavailable.");
        }

        try {
            $result = $callback();

            // Success - reset failures
            $this->recordSuccess();

            return $result;
        } catch (\Throwable $e) {
            // Record failure
            $this->recordFailure();

            // Check if we should open the circuit
            if ($this->getFailureCount() >= $this->failureThreshold) {
                $this->open();

                Log::error("🔴 Circuit breaker opened due to repeated failures", [
                    'service' => $this->serviceName,
                    'failures' => $this->getFailureCount(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Get current state of the circuit breaker.
     */
    public function getState(): string
    {
        $state = Cache::get("{$this->cacheKey}:state", self::STATE_CLOSED);

        // Check if timeout has elapsed and transition to half-open
        if ($state === self::STATE_OPEN) {
            $openedAt = Cache::get("{$this->cacheKey}:opened_at", 0);
            if (time() - $openedAt > $this->timeout) {
                $this->setState(self::STATE_HALF_OPEN);

                Log::info("🟡 Circuit breaker transitioning to HALF_OPEN", [
                    'service' => $this->serviceName,
                ]);

                return self::STATE_HALF_OPEN;
            }
        }

        return $state;
    }

    /**
     * Record a successful operation.
     */
    protected function recordSuccess(): void
    {
        $state = $this->getState();

        if ($state === self::STATE_HALF_OPEN) {
            $successCount = (int) Cache::increment("{$this->cacheKey}:success_count", 1, 30);

            if ($successCount >= $this->successThreshold) {
                $this->close();

                Log::info("🟢 Circuit breaker closed - service recovered", [
                    'service' => $this->serviceName,
                    'successes' => $successCount,
                ]);
            }
        } else {
            // Normal operation - reset failures
            Cache::forget("{$this->cacheKey}:failures");
            Cache::forget("{$this->cacheKey}:success_count");
        }
    }

    /**
     * Record a failed operation.
     */
    protected function recordFailure(): void
    {
        Cache::increment("{$this->cacheKey}:failures", 1, 60);
        Cache::forget("{$this->cacheKey}:success_count");

        Log::warning("📊 Circuit breaker recorded failure", [
            'service' => $this->serviceName,
            'failure_count' => $this->getFailureCount(),
            'threshold' => $this->failureThreshold,
        ]);
    }

    /**
     * Get current failure count.
     */
    public function getFailureCount(): int
    {
        return (int) Cache::get("{$this->cacheKey}:failures", 0);
    }

    /**
     * Open the circuit (service is down).
     */
    protected function open(): void
    {
        $this->setState(self::STATE_OPEN);
        Cache::put("{$this->cacheKey}:opened_at", time(), 300);
    }

    /**
     * Close the circuit (service is up).
     */
    protected function close(): void
    {
        $this->setState(self::STATE_CLOSED);
        Cache::forget("{$this->cacheKey}:opened_at");
        Cache::forget("{$this->cacheKey}:failures");
        Cache::forget("{$this->cacheKey}:success_count");
    }

    /**
     * Set circuit state.
     */
    protected function setState(string $state): void
    {
        Cache::put("{$this->cacheKey}:state", $state, 300);
    }

    /**
     * Reset circuit breaker (manual override).
     */
    public function reset(): void
    {
        Cache::forget($this->cacheKey);
        Cache::forget("{$this->cacheKey}:state");
        Cache::forget("{$this->cacheKey}:opened_at");
        Cache::forget("{$this->cacheKey}:failures");
        Cache::forget("{$this->cacheKey}:success_count");

        Log::info("🔄 Circuit breaker reset", [
            'service' => $this->serviceName,
        ]);
    }
}
