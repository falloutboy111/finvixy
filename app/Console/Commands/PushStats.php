<?php

namespace App\Console\Commands;

use App\Services\FinvixyStatsService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Aggregates a PII-free usage rollup and POSTs it to the Enclivix stats channel.
 *
 * Intended to run hourly. Each snapshot carries its own uuid + window so the CRM
 * can store history and dedup redeliveries.
 */
class PushStats extends Command
{
    protected $signature = 'finvixy:push-stats
        {--hours=1 : Size of the rollup window ending now}
        {--dry-run : Print the payload instead of sending}';

    protected $description = 'Push a PII-free usage rollup snapshot to the Enclivix stats channel';

    public function handle(FinvixyStatsService $stats): int
    {
        $to   = CarbonImmutable::now();
        $from = $to->subHours(max(1, (int) $this->option('hours')));

        $payload = $stats->buildRollup($from, $to);

        if ($this->option('dry-run')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        if (! $stats->enabled()) {
            $this->warn('Stats push disabled (STATS_PUSH_ENABLED=false or FINVIXY_STATS_URL unset). Skipping.');

            return self::SUCCESS;
        }

        try {
            $stats->send($payload);
        } catch (\Throwable $e) {
            Log::warning('finvixy:push-stats failed', ['error' => $e->getMessage()]);
            $this->error("Push failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info('Rollup snapshot pushed ('.$payload['snapshot_id'].').');

        return self::SUCCESS;
    }
}
