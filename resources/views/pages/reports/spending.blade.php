<?php

use App\Models\Expense;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Spending Report')] #[Layout('layouts.app.sidebar')] class extends Component {
    #[Url]
    public string $period = '6';

    /**
     * @return array<int, array{label: string, amount: float}>
     */
    public function getMonthlyDataProperty(): array
    {
        $user = Auth::user();

        if (! $user->organisation_id) {
            return [];
        }

        $months = (int) $this->period;
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $total = Expense::query()
                ->where('organisation_id', $user->organisation_id)
                ->excludeDuplicates()
                ->whereYear('date', $date->year)
                ->whereMonth('date', $date->month)
                ->sum('amount');

            $data[] = [
                'label' => $date->format('M Y'),
                'amount' => round((float) $total, 2),
            ];
        }

        return $data;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getCategoryBreakdownProperty()
    {
        $user = Auth::user();

        if (! $user->organisation_id) {
            return collect();
        }

        $months = (int) $this->period;
        $from = Carbon::now()->subMonths($months)->startOfMonth();

        return Expense::query()
            ->where('organisation_id', $user->organisation_id)
            ->excludeDuplicates()
            ->where('date', '>=', $from)
            ->whereNotNull('category')
            ->selectRaw('category, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();
    }

    public function getTotalSpendProperty(): float
    {
        return collect($this->monthlyData)->sum('amount');
    }

    public function getAverageMonthlyProperty(): float
    {
        $data = $this->monthlyData;

        return count($data) > 0 ? round($this->totalSpend / count($data), 2) : 0;
    }
} ?>

<div>
    {{-- Header --}}
    <div class="flex flex-col gap-4 mb-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" class="text-white">Spending Report</flux:heading>
            <flux:text class="text-gray-400 mt-1">Track your spending over time.</flux:text>
        </div>
        <flux:select wire:model.live="period" class="w-40">
            <option value="3">Last 3 months</option>
            <option value="6">Last 6 months</option>
            <option value="12">Last 12 months</option>
        </flux:select>
    </div>

    {{-- Summary Cards --}}
    <div class="grid gap-4 md:grid-cols-3 mb-8">
        <div class="glow-card rounded-2xl p-5">
            <flux:text class="text-gray-400 text-sm mb-2">Total Spend</flux:text>
            <flux:heading size="xl" class="text-white">R{{ number_format($this->totalSpend, 2) }}</flux:heading>
        </div>
        <div class="glow-card rounded-2xl p-5">
            <flux:text class="text-gray-400 text-sm mb-2">Monthly Average</flux:text>
            <flux:heading size="xl" class="text-white">R{{ number_format($this->averageMonthly, 2) }}</flux:heading>
        </div>
        <div class="glow-card rounded-2xl p-5">
            <flux:text class="text-gray-400 text-sm mb-2">Categories Used</flux:text>
            <flux:heading size="xl" class="text-white">{{ $this->categoryBreakdown->count() }}</flux:heading>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Monthly Chart --}}
        <div class="glow-card rounded-2xl p-6">
            <flux:heading size="lg" class="text-white mb-6">Monthly Spending</flux:heading>

            @php
                $chartData = $this->monthlyData;
                $maxAmount = max(array_column($chartData, 'amount') ?: [1]);
            @endphp

            <div class="flex items-end gap-2 h-52">
                @foreach ($chartData as $bar)
                    @php
                        $height = $maxAmount > 0 ? max(($bar['amount'] / $maxAmount) * 100, 4) : 4;
                    @endphp
                    <div class="flex-1 flex flex-col items-center gap-2">
                        <flux:text class="text-xs text-gray-400">R{{ number_format($bar['amount'], 0) }}</flux:text>
                        <div class="w-full rounded-t-lg bg-gradient-to-t from-emerald-600 to-emerald-400 transition-all duration-300"
                             style="height: {{ $height }}%">
                        </div>
                        <flux:text class="text-xs text-gray-500">{{ $bar['label'] }}</flux:text>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Category Breakdown --}}
        <div class="glow-card rounded-2xl p-6">
            <flux:heading size="lg" class="text-white mb-6">By Category</flux:heading>

            <div class="space-y-4">
                @forelse ($this->categoryBreakdown as $cat)
                    @php
                        $percentage = $this->totalSpend > 0 ? round(($cat->total / $this->totalSpend) * 100, 1) : 0;
                    @endphp
                    <div wire:key="cat-{{ $cat->category }}">
                        <div class="flex items-center justify-between mb-1.5">
                            <flux:text class="text-sm text-white capitalize">{{ str_replace('-', ' ', $cat->category) }}</flux:text>
                            <flux:text class="text-sm text-gray-400">R{{ number_format($cat->total, 2) }} ({{ $percentage }}%)</flux:text>
                        </div>
                        <div class="w-full h-2 bg-white/5 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-emerald-600 to-emerald-400 rounded-full transition-all duration-300"
                                 style="width: {{ $percentage }}%">
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <flux:icon name="chart-bar" class="size-10 text-gray-600 mx-auto mb-3" />
                        <flux:text class="text-gray-500">No spending data yet</flux:text>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
