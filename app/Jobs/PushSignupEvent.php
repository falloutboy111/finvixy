<?php

namespace App\Jobs;

use App\Services\FinvixyStatsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Pushes a PII-free "new signup" event to the Enclivix stats channel.
 *
 * Queued so a CRM outage never blocks the user's signup. Retries with backoff;
 * on permanent failure it logs (no PII) and gives up.
 */
class PushSignupEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 20;

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 120, 300, 900];
    }

    public function handle(FinvixyStatsService $stats): void
    {
        if (! $stats->enabled()) {
            return;
        }

        $stats->send($stats->signupPayload());
    }

    public function failed(\Throwable $e): void
    {
        // No PII — the signup payload carries only a uuid, timestamp and count.
        Log::warning('PushSignupEvent: giving up after retries', ['error' => $e->getMessage()]);
    }
}
