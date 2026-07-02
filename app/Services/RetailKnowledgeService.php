<?php

namespace App\Services;

use App\Models\KnowledgeBlock;
use Illuminate\Support\Facades\Cache;

/**
 * Serves the SA retailer + product knowledge block from the knowledge_blocks
 * table (editable without a redeploy; bump `version` on edit).
 *
 * Two consumers:
 *  - AgentCoreService injects promptBlock() into the agent's system prompt,
 *    where it sits behind the Bedrock prompt-cache checkpoint (written once,
 *    read at cache rate while the content — and therefore the bytes — are
 *    unchanged).
 *  - AgentToolService uses normalise()/retailers()/isAggregator() to drive
 *    per-retailer price-check queries and result tagging.
 */
class RetailKnowledgeService
{
    private const CACHE_TTL = 300; // seconds; content changes rarely

    /** @return array<string, mixed> */
    public function content(): array
    {
        return Cache::remember('knowledge_block:sa_retail', self::CACHE_TTL, function () {
            $block = KnowledgeBlock::where('key', 'sa_retail')->where('active', true)->first();

            return $block ? ['version' => $block->version, ...$block->content] : ['version' => 0];
        });
    }

    /**
     * Render the knowledge as a stable text block for the agent's system
     * prompt. Deterministic output: identical DB content → identical bytes →
     * Bedrock prompt-cache hit.
     */
    public function promptBlock(): string
    {
        $c = $this->content();

        if (($c['version'] ?? 0) === 0) {
            return '';
        }

        $retailers = collect($c['retailers'] ?? [])
            ->map(fn ($r) => $r['name']
                .(empty($r['aka']) ? '' : ' (aka '.implode(', ', $r['aka']).')')
                .(empty($r['notes']) ? '' : ' — '.$r['notes']))
            ->join('; ');

        $aggregators = collect($c['aggregators'] ?? [])
            ->map(fn ($a) => $a['name'].(empty($a['notes']) ? '' : ' — '.$a['notes']))
            ->join('; ');

        $norms = collect($c['normalisations'] ?? [])
            ->map(fn ($v, $k) => "'{$k}' means {$v}")
            ->join('; ');

        $brands = collect($c['brands'] ?? [])
            ->map(fn ($v, $k) => "{$k} is a {$v} brand")
            ->join('; ');

        return "SA RETAIL KNOWLEDGE (v{$c['version']}): "
            ."RETAILERS (sell directly; their prices are real store prices): {$retailers}. "
            ."AGGREGATORS (comparison sites; their prices are indicative — always say e.g. "
            ."'via Troli, a comparison site — verify in-store', never present as a store price): {$aggregators}. "
            ."RECEIPT ABBREVIATIONS: {$norms}. "
            ."BRAND HINTS: {$brands}.";
    }

    /**
     * Expand receipt-label abbreviations and append brand→category hints so a
     * messy receipt name becomes a searchable retail term.
     */
    public function normalise(string $productName): string
    {
        $c = $this->content();
        $result = $productName;

        foreach (($c['normalisations'] ?? []) as $abbrev => $expansion) {
            $result = preg_replace(
                '/\b'.preg_quote($abbrev, '/').'\b/i',
                $expansion,
                $result,
            ) ?? $result;
        }

        foreach (($c['brands'] ?? []) as $brand => $category) {
            if (stripos($result, $brand) !== false && stripos($result, $category) === false) {
                $result .= ' '.$category;
            }
        }

        return trim(preg_replace('/\s+/', ' ', $result) ?? $result);
    }

    /**
     * Retailer names in priority order for per-retailer query fan-out.
     *
     * @return list<string>
     */
    public function retailers(): array
    {
        return collect($this->content()['retailers'] ?? [])
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * Name of the aggregator a text mentions, or null if none.
     */
    public function aggregatorIn(string $text): ?string
    {
        foreach (($this->content()['aggregators'] ?? []) as $agg) {
            if (stripos($text, $agg['name']) !== false) {
                return $agg['name'];
            }
        }

        return null;
    }
}
