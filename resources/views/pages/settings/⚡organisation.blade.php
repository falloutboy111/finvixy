<?php

use App\Jobs\SyncReceiptsToDrive;
use App\Models\ConnectedAccount;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Organisation')] #[Layout('layouts.app.sidebar')] class extends Component {

    public string $driveFolderName = '';
    public string $driveFolderPath = '';
    public bool $syncing = false;

    public function mount(): void
    {
        $account = $this->googleAccount;
        if ($account) {
            $this->driveFolderName = $account->settings['drive_root_name'] ?? '';
            $this->driveFolderPath = $account->settings['drive_folder_path'] ?? '';
        }
    }

    #[Computed]
    public function members(): \Illuminate\Database\Eloquent\Collection
    {
        $user = Auth::user();

        return User::query()
            ->where('organisation_id', $user->organisation_id)
            ->orderBy('id')
            ->get(['id', 'name', 'email']);
    }

    #[Computed]
    public function totalReceiptCount(): int
    {
        return Expense::query()
            ->where('user_id', Auth::id())
            ->whereNotNull('receipt_path')
            ->where('receipt_path', '!=', '')
            ->count();
    }

    #[Computed]
    public function syncedReceiptCount(): int
    {
        return Expense::query()
            ->where('user_id', Auth::id())
            ->whereNotNull('drive_file_id')
            ->count();
    }

    #[Computed]
    public function googleAccount(): ?ConnectedAccount
    {
        return ConnectedAccount::query()
            ->where('user_id', Auth::id())
            ->where('provider', 'google_drive')
            ->where('is_active', true)
            ->first();
    }

    public function saveDriveSettings(): void
    {
        $this->validate([
            'driveFolderName' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s\-_.]*$/'],
            'driveFolderPath' => ['nullable', 'string', 'max:500', 'regex:/^[a-zA-Z0-9\/\-_.]*$/'],
        ], [
            'driveFolderName.regex' => 'Folder name may only contain letters, numbers, spaces, hyphens, underscores, and dots.',
            'driveFolderPath.regex' => 'Path may only contain letters, numbers, forward slashes, hyphens, underscores, and dots.',
        ]);

        $account = $this->googleAccount;
        if (! $account) {
            return;
        }

        $settings = $account->settings ?? [];

        $newName = trim($this->driveFolderName);
        $newPath = ltrim(rtrim(trim($this->driveFolderPath), '/'), '/');

        // If the root folder name has changed, clear the picker folder ID so
        // the new name takes effect (name-based lookup replaces the picker selection).
        if ($newName && $newName !== ($settings['drive_root_name'] ?? '')) {
            unset($settings['drive_folder_id'], $settings['drive_folder_name']);
        }

        if ($newName !== '') {
            $settings['drive_root_name'] = $newName;
        } else {
            unset($settings['drive_root_name']);
        }

        if ($newPath !== '') {
            $settings['drive_folder_path'] = $newPath;
        } else {
            unset($settings['drive_folder_path']);
        }

        $account->update(['settings' => $settings]);

        unset($this->googleAccount);

        session()->flash('status', 'drive-settings-saved');
    }

    public function resyncAll(): void
    {
        $account = $this->googleAccount;
        if (! $account) {
            return;
        }

        SyncReceiptsToDrive::dispatch($account->id, Auth::id(), force: true);

        $this->syncing = true;
        session()->flash('status', 'resync-started');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Organisation') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Organisation')" :subheading="__('Team members and organisation-wide integrations')">
        <div class="space-y-8">

            {{-- Flash messages --}}
            @if (session('status') === 'drive-settings-saved')
                <flux:callout variant="success" icon="check-circle">
                    <flux:callout.heading>{{ __('Drive settings saved') }}</flux:callout.heading>
                    <flux:callout.text>{{ __('New receipts will sync to the updated folder and path.') }}</flux:callout.text>
                </flux:callout>
            @endif

            @if (session('status') === 'resync-started')
                <flux:callout variant="success" icon="arrow-path">
                    <flux:callout.heading>{{ __('Resync started') }}</flux:callout.heading>
                    <flux:callout.text>{{ __('All receipts are being pushed to Google Drive in the background. This may take a few minutes.') }}</flux:callout.text>
                </flux:callout>
            @endif

            {{-- Team --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="sm">{{ __('Team') }}</flux:heading>
                    <flux:button size="sm" variant="primary" icon="plus" disabled>
                        {{ __('Add Member') }}
                    </flux:button>
                </div>

                <div class="space-y-2">
                    @foreach ($this->members as $member)
                        <div class="flex items-center gap-3 rounded-lg border border-zinc-800 bg-zinc-900/50 px-4 py-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-600 text-xs font-semibold text-white uppercase">
                                {{ mb_substr($member->name, 0, 1) }}
                            </div>
                            <div class="min-w-0 flex-1 grid grid-cols-2 gap-2">
                                <flux:text size="sm" class="font-medium truncate">{{ $member->name }}</flux:text>
                                <flux:text size="sm" class="text-zinc-400 truncate">{{ $member->email }}</flux:text>
                            </div>
                            @if ($member->id === Auth::id())
                                <flux:badge color="yellow" size="sm">{{ __('Owner') }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ __('Member') }}</flux:badge>
                            @endif
                        </div>
                    @endforeach
                </div>

                <flux:text size="xs" class="mt-3 text-zinc-500">
                    {{ __('To manage permissions, visit') }}
                    <flux:link href="{{ route('profile.edit') }}" wire:navigate size="xs">{{ __('Settings → Profile') }}</flux:link>.
                </flux:text>
            </div>

            <flux:separator />

            {{-- Google Drive folder settings --}}
            <div>
                <flux:heading size="sm" class="mb-1">{{ __('Google Drive') }}</flux:heading>

                @if ($this->googleAccount)
                    <div class="flex items-center gap-2 mb-5">
                        <flux:icon.check-circle class="h-4 w-4 text-emerald-500 shrink-0" />
                        <flux:text size="sm" class="text-emerald-400">{{ __('Connected as') }}</flux:text>
                        <flux:text size="sm" class="font-medium text-emerald-300">{{ $this->googleAccount->email }}</flux:text>
                    </div>

                    <form wire:submit="saveDriveSettings" class="space-y-5">

                        {{-- Drive Folder Name --}}
                        <div>
                            <flux:input
                                wire:model="driveFolderName"
                                label="{{ __('Drive Folder Name') }}"
                                placeholder="{{ __('e.g. Enclivix-Accounting') }}"
                                description="{{ __('The root folder in Google Drive where receipts are stored. Leave blank to use the default auto-generated folder.') }}"
                            />
                            @error('driveFolderName')
                                <flux:text size="xs" class="mt-1 text-red-400">{{ $message }}</flux:text>
                            @enderror
                        </div>

                        {{-- Drive Folder Path --}}
                        <div>
                            <flux:input
                                wire:model="driveFolderPath"
                                label="{{ __('Drive Folder Path') }}"
                                placeholder="{{ __('e.g. receipts or v1/receipts') }}"
                                description="{{ __('Optional sub-path within the root folder. Segments are created automatically. Each month\'s receipts are placed inside this path.') }}"
                            />
                            @error('driveFolderPath')
                                <flux:text size="xs" class="mt-1 text-red-400">{{ $message }}</flux:text>
                            @enderror
                        </div>

                        {{-- Preview of resolved path --}}
                        @php
                            $previewRoot = $driveFolderName ?: ($this->googleAccount->settings['drive_folder_name'] ?? ($this->googleAccount->organisation?->name.'-finvixy' ?? 'finvixy'));
                            $previewPath = $driveFolderPath ? '/'.$driveFolderPath : '';
                            $previewFull = $previewRoot.$previewPath.'/2026-06/vendor_2026-06-30.jpg';
                        @endphp
                        <div class="rounded-md border border-zinc-700/50 bg-zinc-800/30 px-4 py-3">
                            <flux:text size="xs" class="text-zinc-500 mb-1">{{ __('Files will be saved to:') }}</flux:text>
                            <flux:text size="xs" class="font-mono text-zinc-300 break-all">{{ $previewFull }}</flux:text>
                        </div>

                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="saveDriveSettings">{{ __('Save') }}</span>
                            <span wire:loading wire:target="saveDriveSettings">{{ __('Saving…') }}</span>
                        </flux:button>
                    </form>

                    <flux:separator class="my-6" />

                    {{-- Resync All --}}
                    <div>
                        <flux:heading size="sm" class="mb-1">{{ __('Receipt Sync') }}</flux:heading>
                        <flux:text size="sm" class="text-zinc-400 mb-4">
                            {{ __('Push all receipt images from storage to the configured Google Drive folder. Use this after changing your folder name or path to re-upload everything to the new location.') }}
                        </flux:text>

                        <div class="flex items-center gap-4 text-sm text-zinc-400 mb-4">
                            <div class="flex items-center gap-1.5">
                                <flux:icon.check-circle class="h-4 w-4 text-emerald-500" />
                                <span>{{ $this->syncedReceiptCount }} {{ __('synced to Drive') }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <flux:icon.photo class="h-4 w-4 text-zinc-500" />
                                <span>{{ $this->totalReceiptCount }} {{ __('total receipts') }}</span>
                            </div>
                        </div>

                        <flux:button
                            variant="danger"
                            wire:click="resyncAll"
                            wire:confirm="{{ __('This will re-upload all :count receipts to Google Drive, overwriting any existing Drive links. Continue?', ['count' => $this->totalReceiptCount]) }}"
                            wire:loading.attr="disabled"
                            :disabled="$syncing || $this->totalReceiptCount === 0"
                            icon="arrow-path"
                        >
                            <span wire:loading.remove wire:target="resyncAll">
                                {{ __('Resync All :count Receipts', ['count' => $this->totalReceiptCount]) }}
                            </span>
                            <span wire:loading wire:target="resyncAll">
                                {{ __('Queuing…') }}
                            </span>
                        </flux:button>
                    </div>
                @else
                    <flux:text size="sm" class="text-zinc-500">
                        {{ __('Connect Google Drive in') }}
                        <flux:link href="{{ route('connected-accounts.edit') }}" wire:navigate size="sm">{{ __('Connected accounts') }}</flux:link>
                        {{ __('to configure folder settings.') }}
                    </flux:text>
                @endif
            </div>

        </div>
    </x-pages::settings.layout>
</section>
