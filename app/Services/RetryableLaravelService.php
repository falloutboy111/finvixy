<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Base trait for implementing exponential backoff retry logic.
 * Used by Textract and Bedrock services.
 */
trait RetryableLaravelService
{
    protected int $maxRetries = 3;
    protected int $initialDelayMs = 100;
    protected float $backoffMultiplier = 2.0;

    /**
     * Execute a callback with exponential backoff retry logic.
     *
     * @param callable $callback
     * @param string $operation Description for logging
     * @param int $maxRetries Override max retries
     *
     * @return mixed
     *
     * @throws \Throwable
     */
    protected function executeWithRetry(callable $callback, string $operation, int $maxRetries = null): mixed
    {
        $maxRetries = $maxRetries ?? $this->maxRetries;
        $lastException = null;
        $delayMs = $this->initialDelayMs;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                Log::debug("🔄 Retry attempt $attempt/$maxRetries: $operation");

                return $callback();
            } catch (\Throwable $e) {
                $lastException = $e;
                $errorCode = $e->getCode();

                // Check if error is retryable (rate limit, timeout, connection error)
                $isRetryable = $this->isRetryableError($e);

                if (! $isRetryable || $attempt === $maxRetries) {
                    Log::error("❌ Operation failed (not retryable or max retries reached): $operation", [
                        'attempt' => $attempt,
                        'error' => $e->getMessage(),
                        'code' => $errorCode,
                    ]);

                    throw $e;
                }

                Log::warning("⚠️ Retryable error detected, will retry: $operation", [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'code' => $errorCode,
                    'wait_ms' => $delayMs,
                ]);

                // Exponential backoff
                usleep($delayMs * 1000);
                $delayMs = (int) ($delayMs * $this->backoffMultiplier);
            }
        }

        throw $lastException;
    }

    /**
     * Check if an error is retryable.
     */
    protected function isRetryableError(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());
        $code = $e->getCode();

        // Rate limiting (429, 503, 509)
        if ($code >= 429 && $code <= 509) {
            return true;
        }

        // Timeout and connection errors
        if (str_contains($message, 'timeout') || str_contains($message, 'connection')) {
            return true;
        }

        // AWS throttling
        if (str_contains($message, 'throttl') || str_contains($message, 'rate')) {
            return true;
        }

        // Service unavailable
        if (str_contains($message, 'unavailable') || str_contains($message, 'temporarily')) {
            return true;
        }

        return false;
    }
}
