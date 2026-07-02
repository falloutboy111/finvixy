<?php

namespace App\Services;

use App\Models\AiUsageLog;
use App\Models\Expense;
use App\Models\PriceLookupUsage;
use App\Models\User;
use App\Models\WhatsappWebhook;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Builds and sends the PII-FREE operational-stats payloads to the Enclivix CRM.
 *
 * This is a SEPARATE channel from the customer-info CRM sync (EnclivixCrmService).
 * It carries only counts, model/agent names, cost estimates and timestamps —
 * never names, phone numbers, message text or receipt content.
 */
class FinvixyStatsService
{
    /** Stable identifier for the single WhatsApp agent (future-proofs per-agent rollups). */
    private const AGENT_KEY = 'finvixy-whatsapp';

    public function enabled(): bool
    {
        return (bool) config('services.finvixy_stats.enabled')
            && ! empty(config('services.finvixy_stats.url'));
    }

    /**
     * POST a payload to the Enclivix ingest endpoint. Throws on non-2xx so the
     * caller (queued job / command) can retry. Never includes PII.
     */
    public function send(array $payload): void
    {
        $response = Http::withHeaders(['X-Finvixy-Token' => config('services.enclivix_crm.token')])
            ->timeout(15)
            ->acceptJson()
            ->post((string) config('services.finvixy_stats.url'), $payload);

        if (! $response->successful()) {
            throw new \RuntimeException("Finvixy stats push failed ({$response->status()}): ".$response->body());
        }
    }

    /**
     * Build a PII-free signup event payload.
     *
     * @return array<string, mixed>
     */
    public function signupPayload(): array
    {
        return [
            'event_id'    => (string) Str::uuid(),
            'type'        => 'signup',
            'occurred_at' => now()->toIso8601String(),
            'users_total' => User::count(),
        ];
    }

    /**
     * Build a PII-free usage rollup for the window [$from, $to).
     *
     * @return array<string, mixed>
     */
    public function buildRollup(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $monthStart = Carbon::now()->startOfMonth()->toDateString();

        return [
            'type'         => 'rollup',
            'snapshot_id'  => (string) Str::uuid(),
            'window_start' => $from->toIso8601String(),
            'window_end'   => $to->toIso8601String(),
            'users'        => [
                'new'   => User::whereBetween('created_at', [$from, $to])->count(),
                'total' => User::count(),
            ],
            'tokens'       => $this->tokenBreakdown($from, $to),
            'serper'       => [
                'used_this_month' => (int) PriceLookupUsage::where('month', $monthStart)->sum('count'),
                'user_cap'        => (int) config('services.lookup.monthly_cap', 50),
                'account_cap'     => (int) config('services.finvixy_stats.account_cap', 2500),
            ],
            'receipts'     => [
                'processed_in_window' => Expense::whereBetween('created_at', [$from, $to])
                    ->whereNotNull('receipt_path')->count(),
                'total'               => Expense::whereNotNull('receipt_path')->count(),
            ],
            'messages'     => [
                'in_window' => WhatsappWebhook::whereBetween('created_at', [$from, $to])->count(),
                'total'     => WhatsappWebhook::count(),
            ],
        ];
    }

    /**
     * Token/cost aggregation from AiUsageLog, grouped per model and per agent.
     *
     * @return array<string, mixed>
     */
    private function tokenBreakdown(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = AiUsageLog::whereBetween('created_at', [$from, $to])
            ->selectRaw(
                'model_name, service_type,
                 SUM(prompt_tokens) as input_tokens,
                 SUM(completion_tokens) as output_tokens,
                 SUM(cache_read_tokens) as cache_read_tokens,
                 SUM(cache_write_tokens) as cache_write_tokens,
                 SUM(estimated_cost) as cost,
                 COUNT(*) as requests'
            )
            ->groupBy('model_name', 'service_type')
            ->get();

        $blank = fn () => [
            'input_tokens' => 0, 'output_tokens' => 0,
            'cache_read_tokens' => 0, 'cache_write_tokens' => 0,
            'cost' => 0.0, 'requests' => 0,
        ];

        $perModel = [];
        $perAgent = [];
        $totals   = $blank();

        foreach ($rows as $r) {
            $model = $r->model_name ?: 'unknown';
            // One agent today; key on a stable identifier so multi-agent later
            // needs no rework. Map by service_type, defaulting to the WA agent.
            $agent = $r->service_type === 'bedrock_agent' ? self::AGENT_KEY : (string) $r->service_type;

            $bucket = [
                'input_tokens'       => (int) $r->input_tokens,
                'output_tokens'      => (int) $r->output_tokens,
                'cache_read_tokens'  => (int) $r->cache_read_tokens,
                'cache_write_tokens' => (int) $r->cache_write_tokens,
                'cost'               => round((float) $r->cost, 8),
                'requests'           => (int) $r->requests,
            ];

            $perModel[$model] = $this->mergeBucket($perModel[$model] ?? $blank(), $bucket);
            $perAgent[$agent] = $this->mergeBucket($perAgent[$agent] ?? $blank(), $bucket);
            $totals           = $this->mergeBucket($totals, $bucket);
        }

        return ['per_model' => $perModel, 'per_agent' => $perAgent, 'totals' => $totals];
    }

    /**
     * @param  array<string, float|int>  $a
     * @param  array<string, float|int>  $b
     * @return array<string, float|int>
     */
    private function mergeBucket(array $a, array $b): array
    {
        foreach ($b as $k => $v) {
            $a[$k] = ($a[$k] ?? 0) + $v;
        }
        $a['cost'] = round((float) $a['cost'], 8);

        return $a;
    }
}
