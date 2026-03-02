<?php

use App\Models\Expense;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] #[Layout('layouts.app.sidebar')] class extends Component {
    /**
     * @return array<string, mixed>
     */
    public function getStatsProperty(): array
    {
        $user = Auth::user();
        $organisationId = $user->organisation_id;

        if (! $organisationId) {
            return $this->emptyStats();
        }

        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        $monthExpenses = Expense::query()
            ->where('organisation_id', $organisationId)
            ->excludeDuplicates()
            ->whereBetween('date', [$startOfMonth, $now])
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount), 0) as total')
            ->first();

        $lastMonthExpenses = Expense::query()
            ->where('organisation_id', $organisationId)
            ->excludeDuplicates()
            ->whereBetween('date', [$startOfLastMonth, $endOfLastMonth])
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount), 0) as total')
            ->first();

        $totalExpenses = Expense::query()
            ->where('organisation_id', $organisationId)
            ->excludeDuplicates()
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount), 0) as total')
            ->first();

        $pendingCount = Expense::query()
            ->where('organisation_id', $organisationId)
            ->where('status', 'pending')
            ->count();

        $topCategory = Expense::query()
            ->where('organisation_id', $organisationId)
            ->excludeDuplicates()
            ->whereBetween('date', [$startOfMonth, $now])
            ->whereNotNull('category')
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->first();

        $growthPercent = $lastMonthExpenses->total > 0
            ? round((($monthExpenses->total - $lastMonthExpenses->total) / $lastMonthExpenses->total) * 100, 1)
            : 0;

        return [
            'month_total' => number_format((float) $monthExpenses->total, 2),
            'month_count' => (int) $monthExpenses->count,
            'all_time_total' => number_format((float) $totalExpenses->total, 2),
            'all_time_count' => (int) $totalExpenses->count,
            'pending_count' => $pendingCount,
            'top_category' => $topCategory?->category ?? 'None',
            'top_category_amount' => $topCategory ? number_format((float) $topCategory->total, 2) : '0.00',
            'growth_percent' => $growthPercent,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getRecentExpensesProperty()
    {
        $user = Auth::user();

        if (! $user->organisation_id) {
            return collect();
        }

        return Expense::query()
            ->where('organisation_id', $user->organisation_id)
            ->excludeDuplicates()
            ->with('user')
            ->latest('date')
            ->limit(6)
            ->get();
    }

    /**
     * @return array<int, array{label: string, amount: float}>
     */
    public function getSpendingChartProperty(): array
    {
        $user = Auth::user();

        if (! $user->organisation_id) {
            return [];
        }

        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months->push([
                'label' => $date->format('M'),
                'start' => $date->copy()->startOfMonth(),
                'end' => $date->copy()->endOfMonth(),
            ]);
        }

        return $months->map(function ($month) use ($user) {
            $total = Expense::query()
                ->where('organisation_id', $user->organisation_id)
                ->excludeDuplicates()
                ->whereBetween('date', [$month['start'], $month['end']])
                ->sum('amount');

            return [
                'label' => $month['label'],
                'amount' => round((float) $total, 2),
            ];
        })->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyStats(): array
    {
        return [
            'month_total' => '0.00',
            'month_count' => 0,
            'all_time_total' => '0.00',
            'all_time_count' => 0,
            'pending_count' => 0,
            'top_category' => 'None',
            'top_category_amount' => '0.00',
            'growth_percent' => 0,
        ];
    }
} ?>

<div>
    {{-- Greeting --}}
    <div class="mb-8">
        <flux:heading size="xl" level="1">
            Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ explode(' ', auth()->user()->name)[0] }}
        </flux:heading>
        <flux:text class="mt-1">{{ now()->format('l, j F Y') }} &mdash; here&rsquo;s your expense overview.</flux:text>
    </div>

    {{-- Stats Cards --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-8">
        {{-- This Month --}}
        <div class="glow-card rounded-2xl p-5">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-zinc-400">This Month</span>
                <span class="flex items-center justify-center size-10 rounded-xl bg-emerald-500/10 ring-1 ring-emerald-500/20">
                    <flux:icon name="banknotes" class="size-5 text-emerald-400" />
                </span>
            </div>
            <p class="text-3xl font-bold text-white tracking-tight">R{{ $this->stats['month_total'] }}</p>
            <div class="flex items-center gap-2 mt-2">
                @if ($this->stats['growth_percent'] > 0)
                    <span class="inline-flex items-center gap-0.5 text-xs font-medium text-red-400 bg-red-500/10 px-2 py-0.5 rounded-full">
                        <flux:icon name="arrow-trending-up" variant="micro" class="size-3" />
                        +{{ $this->stats['growth_percent'] }}%
                    </span>
                @elseif ($this->stats['growth_percent'] < 0)
                    <span class="inline-flex items-center gap-0.5 text-xs font-medium text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full">
                        <flux:icon name="arrow-trending-down" variant="micro" class="size-3" />
                        {{ $this->stats['growth_percent'] }}%
                    </span>
                @else
                    <span class="inline-flex items-center text-xs font-medium text-zinc-500 bg-zinc-500/10 px-2 py-0.5 rounded-full">0%</span>
                @endif
                <span class="text-xs text-zinc-500">vs last month</span>
            </div>
        </div>

        {{-- Receipts Scanned --}}
        <div class="glow-card rounded-2xl p-5">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-zinc-400">Receipts Scanned</span>
                <span class="flex items-center justify-center size-10 rounded-xl bg-emerald-500/10 ring-1 ring-emerald-500/20">
                    <flux:icon name="document-text" class="size-5 text-emerald-400" />
                </span>
            </div>
            <p class="text-3xl font-bold text-white tracking-tight">{{ $this->stats['month_count'] }}</p>
            <p class="text-xs text-zinc-500 mt-2">{{ $this->stats['all_time_count'] }} all time</p>
        </div>

        {{-- Pending Review --}}
        <div class="glow-card rounded-2xl p-5">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-zinc-400">Pending Review</span>
                <span class="flex items-center justify-center size-10 rounded-xl bg-amber-500/10 ring-1 ring-amber-500/20">
                    <flux:icon name="clock" class="size-5 text-amber-400" />
                </span>
            </div>
            <p class="text-3xl font-bold text-white tracking-tight">{{ $this->stats['pending_count'] }}</p>
            <p class="text-xs text-zinc-500 mt-2">Awaiting processing</p>
        </div>

        {{-- Top Category --}}
        <div class="glow-card rounded-2xl p-5">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-zinc-400">Top Category</span>
                <span class="flex items-center justify-center size-10 rounded-xl bg-emerald-500/10 ring-1 ring-emerald-500/20">
                    <flux:icon name="chart-pie" class="size-5 text-emerald-400" />
                </span>
            </div>
            <p class="text-xl font-bold text-white capitalize truncate">{{ str_replace('-', ' ', $this->stats['top_category']) }}</p>
            <p class="text-xs text-zinc-500 mt-2">R{{ $this->stats['top_category_amount'] }} this month</p>
        </div>
    </div>

    {{-- Chart + Recent Receipts --}}
    <div class="grid gap-6 lg:grid-cols-5">
        {{-- 6-Month Spending Chart --}}
        <div class="lg:col-span-3 glow-card rounded-2xl p-6">
            <div class="flex items-center justify-between mb-6">
                <flux:heading size="lg">Spending Overview</flux:heading>
                <flux:text class="text-xs text-zinc-500">Last 6 months</flux:text>
            </div>

            @php
                $chartData = $this->spendingChart;
                $maxAmount = max(array_column($chartData, 'amount') ?: [0]);
                $hasData = $maxAmount > 0;
            @endphp

            @if ($hasData)
                <div class="flex items-end gap-3 h-52">
                    @foreach ($chartData as $bar)
                        @php $height = max(($bar['amount'] / $maxAmount) * 100, 6); @endphp
                        <div class="flex-1 flex flex-col items-center gap-2" wire:key="bar-{{ $loop->index }}">
                            <span class="text-[11px] font-medium text-zinc-400">R{{ number_format($bar['amount'], 0) }}</span>
                            <div class="w-full rounded-lg bg-gradient-to-t from-emerald-600 to-emerald-400 transition-all duration-500"
                                 style="height: {{ $height }}%"></div>
                            <span class="text-[11px] text-zinc-500">{{ $bar['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex items-end gap-3 h-52">
                    @foreach ($chartData as $bar)
                        <div class="flex-1 flex flex-col items-center gap-2" wire:key="bar-{{ $loop->index }}">
                            <span class="text-[11px] font-medium text-zinc-600">R0</span>
                            <div class="w-full rounded-lg bg-zinc-800/60 border border-dashed border-zinc-700/50" style="height: 20%"></div>
                            <span class="text-[11px] text-zinc-500">{{ $bar['label'] }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="text-center mt-4">
                    <flux:text class="text-sm text-zinc-500">Scan some receipts to see your spending trends</flux:text>
                </div>
            @endif
        </div>

        {{-- Recent Receipts --}}
        <div class="lg:col-span-2 glow-card rounded-2xl p-6">
            <div class="flex items-center justify-between mb-5">
                <flux:heading size="lg">Recent Receipts</flux:heading>
                <a href="{{ route('expenses.index') }}" wire:navigate class="text-xs font-medium text-emerald-400 hover:text-emerald-300 transition-colors">View all</a>
            </div>

            <div class="space-y-1">
                @forelse ($this->recentExpenses as $expense)
                    <div class="flex items-center justify-between py-2.5 px-2 -mx-2 rounded-lg hover:bg-white/[0.03] transition-colors" wire:key="expense-{{ $expense->id }}">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="flex items-center justify-center size-9 rounded-lg bg-emerald-500/10 shrink-0">
                                <flux:icon name="document-text" variant="mini" class="size-4 text-emerald-400" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-white truncate">{{ $expense->name }}</p>
                                <p class="text-xs text-zinc-500">{{ $expense->date->format('d M Y') }}</p>
                            </div>
                        </div>
                        <div class="text-right shrink-0 ml-3">
                            <p class="text-sm font-semibold text-white">R{{ number_format($expense->amount, 2) }}</p>
                            @php
                                $statusColors = match($expense->status) {
                                    'processed' => 'text-emerald-400 bg-emerald-500/10',
                                    'pending' => 'text-amber-400 bg-amber-500/10',
                                    'rejected' => 'text-red-400 bg-red-500/10',
                                    default => 'text-zinc-400 bg-zinc-500/10',
                                };
                            @endphp
                            <span class="inline-block text-[10px] font-medium {{ $statusColors }} px-1.5 py-0.5 rounded mt-0.5">{{ ucfirst($expense->status) }}</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-10">
                        <div class="flex items-center justify-center size-14 rounded-2xl bg-zinc-800/80 ring-1 ring-zinc-700/50 mx-auto mb-4">
                            <flux:icon name="document-plus" class="size-7 text-zinc-500" />
                        </div>
                        <p class="text-sm font-medium text-zinc-400">No receipts yet</p>
                        <p class="text-xs text-zinc-600 mt-1 max-w-[200px] mx-auto">Scan or upload your first receipt to get started</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Quick Actions (show when empty to guide new users) --}}
    @if ($this->stats['all_time_count'] === 0)
        <div class="mt-8 glow-card rounded-2xl p-6">
            <flux:heading size="lg" class="mb-4">Get Started</flux:heading>
            <div class="grid gap-4 sm:grid-cols-3">
                <a href="{{ route('expenses.index') }}" wire:navigate class="group flex items-center gap-4 p-4 rounded-xl bg-white/[0.02] hover:bg-white/[0.05] border border-zinc-800 hover:border-emerald-500/20 transition-all">
                    <span class="flex items-center justify-center size-11 rounded-xl bg-emerald-500/10 group-hover:bg-emerald-500/20 transition-colors">
                        <flux:icon name="camera" class="size-5 text-emerald-400" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-white">Scan a Receipt</p>
                        <p class="text-xs text-zinc-500">Upload or snap a photo</p>
                    </div>
                </a>

                <a href="{{ route('reports.spending') }}" wire:navigate class="group flex items-center gap-4 p-4 rounded-xl bg-white/[0.02] hover:bg-white/[0.05] border border-zinc-800 hover:border-emerald-500/20 transition-all">
                    <span class="flex items-center justify-center size-11 rounded-xl bg-emerald-500/10 group-hover:bg-emerald-500/20 transition-colors">
                        <flux:icon name="chart-bar-square" class="size-5 text-emerald-400" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-white">View Reports</p>
                        <p class="text-xs text-zinc-500">Spending breakdowns</p>
                    </div>
                </a>

                <a href="{{ route('reports.insights') }}" wire:navigate class="group flex items-center gap-4 p-4 rounded-xl bg-white/[0.02] hover:bg-white/[0.05] border border-zinc-800 hover:border-emerald-500/20 transition-all">
                    <span class="flex items-center justify-center size-11 rounded-xl bg-emerald-500/10 group-hover:bg-emerald-500/20 transition-colors">
                        <flux:icon name="light-bulb" class="size-5 text-emerald-400" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-white">AI Insights</p>
                        <p class="text-xs text-zinc-500">Smart spending analysis</p>
                    </div>
                </a>
            </div>
        </div>
    @endif
</div>
