<?php

use App\Models\Expense;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Insights')] #[Layout('layouts.app.sidebar')] class extends Component {
    /**
     * @return array<string, mixed>
     */
    public function getInsightsProperty(): array
    {
        $user = Auth::user();

        if (! $user->organisation_id) {
            return $this->emptyInsights();
        }

        $orgId = $user->organisation_id;
        $now = Carbon::now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $sixtyDaysAgo = $now->copy()->subDays(60);

        // Current 30-day spending
        $currentSpend = (float) Expense::query()
            ->where('organisation_id', $orgId)
            ->excludeDuplicates()
            ->where('date', '>=', $thirtyDaysAgo)
            ->sum('amount');

        // Previous 30-day spending
        $previousSpend = (float) Expense::query()
            ->where('organisation_id', $orgId)
            ->excludeDuplicates()
            ->whereBetween('date', [$sixtyDaysAgo, $thirtyDaysAgo])
            ->sum('amount');

        // Average receipt amount
        $avgReceipt = (float) Expense::query()
            ->where('organisation_id', $orgId)
            ->excludeDuplicates()
            ->where('date', '>=', $thirtyDaysAgo)
            ->avg('amount') ?? 0;

        // Most expensive day of week
        $expensiveDay = Expense::query()
            ->where('organisation_id', $orgId)
            ->excludeDuplicates()
            ->where('date', '>=', $thirtyDaysAgo)
            ->selectRaw('DAYOFWEEK(date) as dow, SUM(amount) as total')
            ->groupBy('dow')
            ->orderByDesc('total')
            ->first();

        $dayNames = [1 => 'Sunday', 2 => 'Monday', 3 => 'Tuesday', 4 => 'Wednesday', 5 => 'Thursday', 6 => 'Friday', 7 => 'Saturday'];

        // Top vendors (from expense names)
        $topVendors = Expense::query()
            ->where('organisation_id', $orgId)
            ->excludeDuplicates()
            ->where('date', '>=', $thirtyDaysAgo)
            ->selectRaw('name, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Weekly spending trend
        $weeklyTrend = [];
        for ($i = 3; $i >= 0; $i--) {
            $weekStart = $now->copy()->subWeeks($i)->startOfWeek();
            $weekEnd = $now->copy()->subWeeks($i)->endOfWeek();

            $weeklyTrend[] = [
                'label' => 'Week ' . (4 - $i),
                'amount' => round((float) Expense::query()
                    ->where('organisation_id', $orgId)
                    ->excludeDuplicates()
                    ->whereBetween('date', [$weekStart, $weekEnd])
                    ->sum('amount'), 2),
            ];
        }

        $spendChange = $previousSpend > 0
            ? round((($currentSpend - $previousSpend) / $previousSpend) * 100, 1)
            : 0;

        return [
            'current_spend' => $currentSpend,
            'previous_spend' => $previousSpend,
            'spend_change' => $spendChange,
            'avg_receipt' => round($avgReceipt, 2),
            'expensive_day' => $expensiveDay ? ($dayNames[$expensiveDay->dow] ?? 'Unknown') : 'N/A',
            'expensive_day_total' => $expensiveDay ? round((float) $expensiveDay->total, 2) : 0,
            'top_vendors' => $topVendors,
            'weekly_trend' => $weeklyTrend,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyInsights(): array
    {
        return [
            'current_spend' => 0,
            'previous_spend' => 0,
            'spend_change' => 0,
            'avg_receipt' => 0,
            'expensive_day' => 'N/A',
            'expensive_day_total' => 0,
            'top_vendors' => collect(),
            'weekly_trend' => [],
        ];
    }
} ?>

<div>
    <div class="mb-6">
        <flux:heading size="xl" class="text-white">Insights</flux:heading>
        <flux:text class="text-gray-400 mt-1">Smart analysis of your spending patterns (last 30 days).</flux:text>
    </div>

    @php $insights = $this->insights; @endphp

    {{-- Headline Cards --}}
    <div class="grid gap-4 md:grid-cols-3 mb-8">
        <div class="glow-card rounded-2xl p-5">
            <flux:text class="text-gray-400 text-sm mb-2">30-Day Spending</flux:text>
            <flux:heading size="xl" class="text-white">R{{ number_format($insights['current_spend'], 2) }}</flux:heading>
            <div class="flex items-center gap-1.5 mt-1.5">
                @if ($insights['spend_change'] > 0)
                    <flux:badge color="red" size="sm">+{{ $insights['spend_change'] }}%</flux:badge>
                @elseif ($insights['spend_change'] < 0)
                    <flux:badge color="green" size="sm">{{ $insights['spend_change'] }}%</flux:badge>
                @else
                    <flux:badge size="sm">0%</flux:badge>
                @endif
                <flux:text class="text-xs text-gray-500">vs previous 30 days</flux:text>
            </div>
        </div>

        <div class="glow-card rounded-2xl p-5">
            <flux:text class="text-gray-400 text-sm mb-2">Average Receipt</flux:text>
            <flux:heading size="xl" class="text-white">R{{ number_format($insights['avg_receipt'], 2) }}</flux:heading>
            <flux:text class="text-xs text-gray-500 mt-1.5">Per transaction</flux:text>
        </div>

        <div class="glow-card rounded-2xl p-5">
            <flux:text class="text-gray-400 text-sm mb-2">Biggest Spend Day</flux:text>
            <flux:heading size="xl" class="text-white">{{ $insights['expensive_day'] }}</flux:heading>
            <flux:text class="text-xs text-gray-500 mt-1.5">R{{ number_format($insights['expensive_day_total'], 2) }} total</flux:text>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Weekly Trend --}}
        <div class="glow-card rounded-2xl p-6">
            <flux:heading size="lg" class="text-white mb-6">Weekly Trend</flux:heading>

            @php
                $weeklyData = $insights['weekly_trend'];
                $maxWeek = max(array_column($weeklyData, 'amount') ?: [1]);
            @endphp

            <div class="flex items-end gap-4 h-48">
                @foreach ($weeklyData as $week)
                    @php
                        $height = $maxWeek > 0 ? max(($week['amount'] / $maxWeek) * 100, 4) : 4;
                    @endphp
                    <div class="flex-1 flex flex-col items-center gap-2" wire:key="week-{{ $loop->index }}">
                        <flux:text class="text-xs text-gray-400">R{{ number_format($week['amount'], 0) }}</flux:text>
                        <div class="w-full rounded-t-lg bg-gradient-to-t from-emerald-600 to-emerald-400 transition-all duration-300"
                             style="height: {{ $height }}%">
                        </div>
                        <flux:text class="text-xs text-gray-500">{{ $week['label'] }}</flux:text>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Top Vendors --}}
        <div class="glow-card rounded-2xl p-6">
            <flux:heading size="lg" class="text-white mb-6">Top Vendors</flux:heading>

            <div class="space-y-4">
                @forelse ($insights['top_vendors'] as $vendor)
                    @php
                        $maxVendor = $insights['top_vendors']->max('total') ?: 1;
                        $barWidth = round(($vendor->total / $maxVendor) * 100, 1);
                    @endphp
                    <div wire:key="vendor-{{ $loop->index }}">
                        <div class="flex items-center justify-between mb-1.5">
                            <flux:text class="text-sm text-white truncate max-w-[200px]">{{ $vendor->name }}</flux:text>
                            <div class="flex items-center gap-2 shrink-0">
                                <flux:text class="text-xs text-gray-500">{{ $vendor->count }}x</flux:text>
                                <flux:text class="text-sm text-gray-400">R{{ number_format($vendor->total, 2) }}</flux:text>
                            </div>
                        </div>
                        <div class="w-full h-2 bg-white/5 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-emerald-600 to-emerald-400 rounded-full"
                                 style="width: {{ $barWidth }}%">
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <flux:icon name="building-storefront" class="size-10 text-gray-600 mx-auto mb-3" />
                        <flux:text class="text-gray-500">No vendor data yet</flux:text>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
