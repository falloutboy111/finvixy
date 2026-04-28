<?php

use App\Services\OrgStorageService;
use App\Services\PlanLimitService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Usage')] #[Layout('layouts.app.sidebar')] class extends Component {
    public function with(): array
    {
        $user = Auth::user();
        $organisation = $user->organisation;
        $plan = $user->plan;

        // Receipt usage
        $receiptUsage = app(PlanLimitService::class)->checkReceiptLimit($user, 0);

        // Storage usage
        $storageType = $organisation->storage_type;
        $storageUsedBytes = $organisation->storage_used_bytes;
        $storageLimitBytes = $organisation->storage_limit_bytes;
        $storagePercent = $storageLimitBytes > 0
            ? round(($storageUsedBytes / $storageLimitBytes) * 100, 1)
            : 0;

        return [
            'plan' => $plan,
            'receiptUsage' => $receiptUsage,
            'storageType' => $storageType,
            'storageUsed' => OrgStorageService::formatBytes($storageUsedBytes),
            'storageLimit' => OrgStorageService::formatBytes($storageLimitBytes),
            'storagePercent' => $storagePercent,
        ];
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Usage')" :subheading="__('Monitor your plan usage and storage')">
        <div class="space-y-8 max-w-none w-full">

            {{-- Receipt Usage --}}
            <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-6">
                <flux:heading size="lg">Receipt Usage</flux:heading>
                <flux:text size="sm" class="mb-4">Current billing period — resets monthly.</flux:text>

                <div class="flex items-end justify-between mb-2">
                    <span class="text-2xl font-bold text-white">
                        {{ $receiptUsage['used'] }}
                        <span class="text-base font-normal text-zinc-500">
                            / {{ $receiptUsage['limit'] === 'unlimited' ? '∞' : $receiptUsage['limit'] }}
                        </span>
                    </span>
                    @if ($receiptUsage['remaining'] !== 'unlimited')
                        <flux:text size="sm">{{ $receiptUsage['remaining'] }} remaining</flux:text>
                    @else
                        <flux:badge color="emerald" size="sm">Unlimited</flux:badge>
                    @endif
                </div>

                @if ($receiptUsage['limit'] !== 'unlimited')
                    @php
                        $usagePercent = $receiptUsage['limit'] > 0
                            ? min(100, round(($receiptUsage['used'] / $receiptUsage['limit']) * 100, 1))
                            : 0;
                        $barColor = $usagePercent >= 90 ? 'bg-red-500' : ($usagePercent >= 70 ? 'bg-amber-500' : 'bg-emerald-500');
                    @endphp
                    <div class="w-full rounded-full bg-zinc-800 h-2.5">
                        <div class="{{ $barColor }} h-2.5 rounded-full transition-all" style="width: {{ $usagePercent }}%"></div>
                    </div>
                    <flux:text size="xs" class="mt-1">
                        {{ $plan?->name ?? 'Free' }} plan — {{ $receiptUsage['limit'] }} receipts/month
                    </flux:text>
                @endif
            </div>

            {{-- Storage Usage --}}
            <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-6">
                <flux:heading size="lg">Storage</flux:heading>
                <flux:text size="sm" class="mb-4">Receipt file storage for your organisation.</flux:text>

                <div class="flex items-center gap-3 mb-3">
                    @if ($storageType === 'drive')
                        <flux:badge color="blue" size="sm">Google Drive</flux:badge>
                        <flux:text size="sm">Storage managed by your Google account.</flux:text>
                    @elseif ($storageType === 's3')
                        <flux:badge color="emerald" size="sm">Finvixy Storage</flux:badge>
                    @else
                        <flux:badge size="sm">Not configured</flux:badge>
                        <flux:text size="sm">
                            <a href="{{ route('connected-accounts.edit') }}" class="text-emerald-400 hover:underline" wire:navigate>Set up storage →</a>
                        </flux:text>
                    @endif
                </div>

                @if ($storageType === 's3')
                    <div class="flex items-end justify-between mb-2">
                        <span class="text-lg font-bold text-white">
                            {{ $storageUsed }}
                            <span class="text-sm font-normal text-zinc-500">/ {{ $storageLimit }}</span>
                        </span>
                        <flux:text size="sm">{{ $storagePercent }}% used</flux:text>
                    </div>
                    @php
                        $storageBarColor = $storagePercent >= 90 ? 'bg-red-500' : ($storagePercent >= 70 ? 'bg-amber-500' : 'bg-emerald-500');
                    @endphp
                    <div class="w-full rounded-full bg-zinc-800 h-2.5">
                        <div class="{{ $storageBarColor }} h-2.5 rounded-full transition-all" style="width: {{ $storagePercent }}%"></div>
                    </div>
                @endif
            </div>

        </div>
    </x-pages::settings.layout>
</section>
