<?php

use App\Jobs\SyncExpenseToCrm;
use App\Models\Expense;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new #[Title('CRM')] #[Layout('layouts.app.sidebar')] class extends Component {
    public bool $crmSyncEnabled = false;

    public function mount(): void
    {
        $this->crmSyncEnabled = (bool) Auth::user()->crm_sync_enabled;
    }

    #[Computed]
    public function isEnclivix(): bool
    {
        return str_contains(Auth::user()->email, 'enclivix.com');
    }

    public function updatedCrmSyncEnabled(bool $value): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->update(['crm_sync_enabled' => $value]);
        session()->flash('status', 'crm-saved');
    }

    public function syncAll(): void
    {
        $user = Auth::user();

        if (! $user->crm_sync_enabled) {
            return;
        }

        Expense::where('user_id', $user->id)
            ->where('organisation_id', $user->organisation_id)
            ->whereIn('status', ['processed', 'approved'])
            ->each(fn ($expense) => SyncExpenseToCrm::dispatch($expense));

        session()->flash('status', 'crm-sync-started');

        unset($this->unsyncedCount);
    }

    #[Computed]
    public function unsyncedCount(): int
    {
        if (! Auth::user()->crm_sync_enabled) {
            return 0;
        }

        return Expense::where('user_id', Auth::id())
            ->where('organisation_id', Auth::user()->organisation_id)
            ->whereNull('crm_expense_id')
            ->whereIn('status', ['processed', 'approved'])
            ->count();
    }

    #[Computed]
    public function syncedCount(): int
    {
        return Expense::where('user_id', Auth::id())
            ->whereNotNull('crm_expense_id')
            ->count();
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('CRM')" :subheading="__('Sync expenses to the Enclivix CRM')">
        <div class="my-6 w-full space-y-6">

            @if ($this->isEnclivix)

                {{-- Flash messages --}}
                @if (session('status') === 'crm-saved')
                    <flux:callout variant="success" icon="check-circle">
                        <flux:callout.heading>{{ __('Saved') }}</flux:callout.heading>
                        <flux:callout.text>{{ __('CRM sync preference updated.') }}</flux:callout.text>
                    </flux:callout>
                @endif

                @if (session('status') === 'crm-sync-started')
                    <flux:callout variant="success" icon="arrow-path">
                        <flux:callout.heading>{{ __('Sync started') }}</flux:callout.heading>
                        <flux:callout.text>{{ __('Unsynced expenses are being pushed to the CRM in the background.') }}</flux:callout.text>
                    </flux:callout>
                @endif

                {{-- Toggle --}}
                <div class="rounded-lg border border-zinc-800 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:heading size="sm">{{ __('CRM sync') }}</flux:heading>
                            <flux:text size="sm" class="text-zinc-400">
                                {{ __('Automatically push processed expenses to the Enclivix CRM.') }}
                            </flux:text>
                        </div>
                        <flux:switch wire:model.live="crmSyncEnabled" />
                    </div>
                </div>

                {{-- Sync all --}}
                @if ($crmSyncEnabled)
                    <div class="rounded-lg border border-zinc-800 p-5 space-y-4">
                        <div>
                            <flux:heading size="sm">{{ __('Sync all expenses') }}</flux:heading>
                            <flux:text size="sm" class="text-zinc-400">
                                {{ __('Push all processed expenses that have not yet been sent to the CRM.') }}
                            </flux:text>
                        </div>

                        <div class="flex items-center gap-4 text-sm text-zinc-400">
                            <div class="flex items-center gap-1.5">
                                <flux:icon.check-circle class="h-4 w-4 text-emerald-500" />
                                <span>{{ $this->syncedCount }} {{ __('synced') }}</span>
                            </div>
                            @if ($this->unsyncedCount > 0)
                                <div class="flex items-center gap-1.5">
                                    <flux:icon.clock class="h-4 w-4 text-amber-400" />
                                    <span>{{ $this->unsyncedCount }} {{ __('pending') }}</span>
                                </div>
                            @endif
                        </div>

                        <flux:button
                            variant="primary"
                            size="sm"
                            wire:click="syncAll"
                            icon="arrow-path"
                            :disabled="$this->unsyncedCount === 0"
                        >
                            <span wire:loading.remove wire:target="syncAll">
                                @if ($this->unsyncedCount > 0)
                                    {{ __('Sync :count expenses to CRM', ['count' => $this->unsyncedCount]) }}
                                @else
                                    {{ __('All expenses synced') }}
                                @endif
                            </span>
                            <span wire:loading wire:target="syncAll">
                                {{ __('Queueing...') }}
                            </span>
                        </flux:button>
                    </div>
                @endif

            @else

                {{-- Coming soon for non-enclivix accounts --}}
                <div class="rounded-lg border border-zinc-800 p-8 text-center">
                    <flux:icon.building-office class="mx-auto h-10 w-10 text-zinc-600" />
                    <flux:heading size="sm" class="mt-4">{{ __('Coming soon') }}</flux:heading>
                    <flux:text size="sm" class="mt-2 text-zinc-500">
                        {{ __('CRM integration is not yet available for your account type.') }}
                    </flux:text>
                </div>

            @endif

        </div>
    </x-pages::settings.layout>
</section>
