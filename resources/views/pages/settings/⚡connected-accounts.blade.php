<?php

use App\Jobs\SyncReceiptsToDrive;
use App\Models\ConnectedAccount;
use App\Models\Expense;
use App\Models\XeroConnection;
use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Connected accounts')] #[Layout('layouts.app.sidebar')] class extends Component {
    public bool $syncing = false;

    // Drive folder picker state
    public bool $showFolderPicker = false;
    public bool $loadingFolders = false;
    public string $folderSearch = '';
    public array $availableFolders = [];
    public string $folderPickerError = '';

    /**
     * Get all connected accounts for the current user.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ConnectedAccount>
     */
    #[Computed]
    public function accounts(): \Illuminate\Database\Eloquent\Collection
    {
        return ConnectedAccount::query()
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Check if Google Drive is already connected.
     */
    #[Computed]
    public function isGoogleConnected(): bool
    {
        return $this->accounts->where('provider', 'google_drive')->where('is_active', true)->isNotEmpty();
    }

    /**
     * Get the active Google Drive account.
     */
    #[Computed]
    public function googleAccount(): ?ConnectedAccount
    {
        return $this->accounts->where('provider', 'google_drive')->first();
    }

    /**
     * Count of receipts not yet synced to Drive.
     */
    #[Computed]
    public function unsyncedCount(): int
    {
        return Expense::query()
            ->where('user_id', Auth::id())
            ->whereNotNull('receipt_path')
            ->where('receipt_path', '!=', '')
            ->whereNull('drive_file_id')
            ->count();
    }

    /**
     * Count of receipts already synced.
     */
    #[Computed]
    public function syncedCount(): int
    {
        return Expense::query()
            ->where('user_id', Auth::id())
            ->whereNotNull('drive_file_id')
            ->count();
    }

    /**
     * Redirect user to Google OAuth consent screen.
     */
    public function connectGoogle(): void
    {
        $user = Auth::user();

        $state = [
            'user_id' => $user->id,
            'timestamp' => time(),
            'random' => bin2hex(random_bytes(16)),
        ];

        $authUrl = GoogleDriveService::getAuthUrl($state);

        $this->redirect($authUrl);
    }

    /**
     * Dispatch a job to sync all un-synced receipts to Google Drive.
     */
    public function syncToDrive(): void
    {
        $account = $this->googleAccount;

        if (! $account || ! $account->is_active) {
            session()->flash('google-error', __('No active Google Drive account found.'));

            return;
        }

        if ($this->unsyncedCount === 0) {
            session()->flash('status', 'already-synced');

            return;
        }

        SyncReceiptsToDrive::dispatch($account->id, Auth::id());

        $this->syncing = true;
        session()->flash('status', 'sync-started');
    }

    /**
     * Disconnect a Google Drive account.
     */
    public function disconnect(int $accountId): void
    {
        $account = ConnectedAccount::query()
            ->where('user_id', Auth::id())
            ->where('id', $accountId)
            ->firstOrFail();

        $account->delete();

        unset($this->accounts, $this->isGoogleConnected, $this->googleAccount);

        session()->flash('status', 'google-disconnected');
    }

    /**
     * Listen for account-connected event (after OAuth callback redirect).
     */
    #[On('account-connected')]
    public function refreshAccounts(): void
    {
        unset($this->accounts, $this->isGoogleConnected, $this->googleAccount);
    }

    public function openFolderPicker(): void
    {
        $this->showFolderPicker = true;
        $this->folderPickerError = '';
        $this->loadFolders();
    }

    public function loadFolders(): void
    {
        $account = $this->googleAccount;
        if (! $account) {
            return;
        }

        $this->loadingFolders = true;
        $this->folderPickerError = '';

        try {
            $organisationName = $account->organisation?->name ?? 'Organisation';
            $driveService = new GoogleDriveService($account, $organisationName);
            $this->availableFolders = $driveService->listFolders($this->folderSearch);
        } catch (\Throwable $e) {
            $this->folderPickerError = __('Could not load Drive folders. Please reconnect your account.');
            $this->availableFolders = [];
        } finally {
            $this->loadingFolders = false;
        }
    }

    public function updatedFolderSearch(): void
    {
        $this->loadFolders();
    }

    public function selectFolder(string $folderId, string $folderName): void
    {
        $account = $this->googleAccount;
        if (! $account) {
            return;
        }

        $account->update([
            'settings' => array_merge($account->settings ?? [], [
                'drive_folder_id' => $folderId,
                'drive_folder_name' => $folderName,
            ]),
        ]);

        $this->showFolderPicker = false;
        unset($this->googleAccount);
        session()->flash('status', 'drive-folder-saved');
    }

    public function clearFolder(): void
    {
        $account = $this->googleAccount;
        if (! $account) {
            return;
        }

        $settings = $account->settings ?? [];
        unset($settings['drive_folder_id'], $settings['drive_folder_name']);
        $account->update(['settings' => $settings]);

        unset($this->googleAccount);
        session()->flash('status', 'drive-folder-cleared');
    }

    #[Computed]
    public function xeroConnection(): ?XeroConnection
    {
        return XeroConnection::where('user_id', Auth::id())->first();
    }

    public function disconnectXero(): void
    {
        $conn = $this->xeroConnection;
        if ($conn) {
            // Best-effort revocation on Xero's side
            try {
                \Illuminate\Support\Facades\Http::withToken($conn->access_token)
                    ->delete(config('xero.connections_url').'/'.$conn->tenant_id);
            } catch (\Exception) {
            }
            $conn->delete();
        }

        unset($this->xeroConnection);
        session()->flash('xero-disconnected', true);
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Connected accounts') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Connected accounts')" :subheading="__('Link external services to back up receipts and sync data')">
        <div class="my-6 w-full space-y-6">

            {{-- Success / error flash --}}
            @if (session('google-connected'))
                <flux:callout variant="success" icon="check-circle">
                    <flux:callout.heading>{{ __('Google Drive connected') }}</flux:callout.heading>
                    <flux:callout.text>{{ __('Your Google account (:email) has been linked successfully.', ['email' => session('google-email', '')]) }}</flux:callout.text>
                </flux:callout>
            @endif

            @if (session('google-error'))
                <flux:callout variant="danger" icon="exclamation-triangle">
                    <flux:callout.heading>{{ __('Connection failed') }}</flux:callout.heading>
                    <flux:callout.text>{{ session('google-error') }}</flux:callout.text>
                </flux:callout>
            @endif

            @if (session('status') === 'google-disconnected')
                <flux:callout variant="warning" icon="information-circle">
                    <flux:callout.heading>{{ __('Account disconnected') }}</flux:callout.heading>
                    <flux:callout.text>{{ __('Your Google Drive account has been unlinked.') }}</flux:callout.text>
                </flux:callout>
            @endif

            @if (session('status') === 'sync-started')
                <flux:callout variant="success" icon="arrow-path">
                    <flux:callout.heading>{{ __('Sync started') }}</flux:callout.heading>
                    <flux:callout.text>{{ __('Your receipts are being uploaded to Google Drive in the background. This may take a few minutes.') }}</flux:callout.text>
                </flux:callout>
            @endif

            @if (session('status') === 'already-synced')
                <flux:callout variant="success" icon="check-circle">
                    <flux:callout.heading>{{ __('All synced') }}</flux:callout.heading>
                    <flux:callout.text>{{ __('All your receipts are already backed up to Google Drive.') }}</flux:callout.text>
                </flux:callout>
            @endif

            @if (session('status') === 'drive-folder-saved')
                <flux:callout variant="success" icon="check-circle">
                    <flux:callout.heading>{{ __('Folder updated') }}</flux:callout.heading>
                    <flux:callout.text>{{ __('Receipts will now sync to your chosen folder.') }}</flux:callout.text>
                </flux:callout>
            @endif

            @if (session('status') === 'drive-folder-cleared')
                <flux:callout variant="success" icon="check-circle">
                    <flux:callout.heading>{{ __('Folder reset') }}</flux:callout.heading>
                    <flux:callout.text>{{ __('Receipts will sync to the default Finvixy folder.') }}</flux:callout.text>
                </flux:callout>
            @endif

            {{-- Google Drive section --}}
            <div class="rounded-lg border border-zinc-800 p-5">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        {{-- Google Drive icon --}}
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-800">
                            <svg class="h-6 w-6" viewBox="0 0 87.3 78" xmlns="http://www.w3.org/2000/svg">
                                <path d="m6.6 66.85 3.85 6.65c.8 1.4 1.95 2.5 3.3 3.3l13.75-23.8h-27.5c0 1.55.4 3.1 1.2 4.5z" fill="#0066da"/>
                                <path d="m43.65 25-13.75-23.8c-1.35.8-2.5 1.9-3.3 3.3l-20.4 35.3c-.8 1.4-1.2 2.95-1.2 4.5h27.5z" fill="#00ac47"/>
                                <path d="m73.55 76.8c1.35-.8 2.5-1.9 3.3-3.3l1.6-2.75 7.65-13.25c.8-1.4 1.2-2.95 1.2-4.5h-27.5l5.85 13.35z" fill="#ea4335"/>
                                <path d="m43.65 25 13.75-23.8c-1.35-.8-2.9-1.2-4.5-1.2h-18.5c-1.6 0-3.15.45-4.5 1.2z" fill="#00832d"/>
                                <path d="m59.8 53h-32.3l-13.75 23.8c1.35.8 2.9 1.2 4.5 1.2h50.8c1.6 0 3.15-.45 4.5-1.2z" fill="#2684fc"/>
                                <path d="m73.4 26.5-10.1-17.5c-.8-1.4-1.95-2.5-3.3-3.3l-13.75 23.8 16.15 23.5h27.45c0-1.55-.4-3.1-1.2-4.5z" fill="#ffba00"/>
                            </svg>
                        </div>
                        <div>
                            <flux:heading size="sm">{{ __('Google Drive') }}</flux:heading>
                            <flux:text size="sm" class="text-zinc-400">
                                {{ __('Back up receipts and documents automatically') }}
                            </flux:text>
                        </div>
                    </div>

                    @if ($this->isGoogleConnected)
                        <flux:badge color="green" size="sm">{{ __('Connected') }}</flux:badge>
                    @else
                        <flux:badge color="zinc" size="sm">{{ __('Not connected') }}</flux:badge>
                    @endif
                </div>

                @if ($this->googleAccount)
                    <div class="mt-4 rounded-md border border-zinc-700/50 bg-zinc-800/50 p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <flux:icon.envelope class="h-5 w-5 text-zinc-400" />
                                <div>
                                    <flux:text size="sm" class="font-medium">{{ $this->googleAccount->email }}</flux:text>
                                    <flux:text size="xs" class="text-zinc-500">
                                        {{ __('Connected :date', ['date' => $this->googleAccount->created_at->diffForHumans()]) }}
                                        @if ($this->googleAccount->isExpired())
                                            <span class="text-amber-400"> &middot; {{ __('Token expired') }}</span>
                                        @endif
                                    </flux:text>
                                </div>
                            </div>
                            <flux:button
                                variant="danger"
                                size="sm"
                                wire:click="disconnect({{ $this->googleAccount->id }})"
                                wire:confirm="{{ __('Are you sure you want to disconnect this Google account?') }}"
                            >
                                {{ __('Disconnect') }}
                            </flux:button>
                        </div>
                    </div>

                    @if ($this->googleAccount->isExpired())
                        <div class="mt-3">
                            <flux:button variant="primary" size="sm" wire:click="connectGoogle">
                                {{ __('Reconnect') }}
                            </flux:button>
                        </div>
                    @endif

                    {{-- Sync to Drive section --}}
                    @if (! $this->googleAccount->isExpired())
                        <flux:separator class="my-4" />

                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <flux:heading size="sm">{{ __('Receipt backup') }}</flux:heading>
                                    <flux:text size="xs" class="text-zinc-500">
                                        {{ __('Uploads all receipts to a dedicated Finvixy folder in your Google Drive.') }}
                                    </flux:text>
                                </div>
                            </div>

                            {{-- Destination folder --}}
                            <div class="flex items-center gap-2 rounded-md border border-zinc-700/50 bg-zinc-800/50 px-3 py-2">
                                <flux:icon.folder class="h-4 w-4 shrink-0 text-zinc-400" />
                                <flux:text size="sm" class="flex-1 truncate text-zinc-300">
                                    {{ $this->googleAccount->settings['drive_folder_name'] ?? __('Default (Finvixy folder)') }}
                                </flux:text>
                                <flux:button size="sm" variant="ghost" wire:click="openFolderPicker" class="shrink-0">
                                    {{ __('Change') }}
                                </flux:button>
                                @if (! empty($this->googleAccount->settings['drive_folder_id']))
                                    <flux:button size="sm" variant="ghost" wire:click="clearFolder" class="shrink-0 text-zinc-500 hover:text-red-400">
                                        {{ __('Reset') }}
                                    </flux:button>
                                @endif
                            </div>

                            {{-- Folder picker panel --}}
                            @if ($showFolderPicker)
                                <div class="rounded-md border border-zinc-700 bg-zinc-900 p-4 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <flux:heading size="sm">{{ __('Choose a Drive folder') }}</flux:heading>
                                        <flux:button size="sm" variant="ghost" wire:click="$set('showFolderPicker', false)">
                                            {{ __('Cancel') }}
                                        </flux:button>
                                    </div>

                                    <flux:input
                                        wire:model.live.debounce.400ms="folderSearch"
                                        placeholder="{{ __('Search folders...') }}"
                                        icon="magnifying-glass"
                                        size="sm"
                                    />

                                    @if ($folderPickerError)
                                        <flux:text size="sm" class="text-red-400">{{ $folderPickerError }}</flux:text>
                                    @elseif ($loadingFolders)
                                        <div class="flex items-center gap-2 text-zinc-500">
                                            <flux:icon.arrow-path class="h-4 w-4 animate-spin" />
                                            <flux:text size="sm">{{ __('Loading folders...') }}</flux:text>
                                        </div>
                                    @elseif (empty($availableFolders))
                                        <flux:text size="sm" class="text-zinc-500">{{ __('No folders found.') }}</flux:text>
                                    @else
                                        <div class="max-h-52 overflow-y-auto space-y-1">
                                            @foreach ($availableFolders as $folder)
                                                <button
                                                    wire:click="selectFolder('{{ $folder['id'] }}', '{{ addslashes($folder['name']) }}')"
                                                    class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm hover:bg-zinc-800 transition-colors"
                                                >
                                                    <flux:icon.folder class="h-4 w-4 shrink-0 text-zinc-400" />
                                                    <span class="truncate text-zinc-200">{{ $folder['name'] }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif

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
                                @if ($this->googleAccount->last_sync_at)
                                    <div class="flex items-center gap-1.5 text-zinc-500">
                                        <flux:icon.arrow-path class="h-4 w-4" />
                                        <span>{{ __('Last sync :time', ['time' => $this->googleAccount->last_sync_at->diffForHumans()]) }}</span>
                                    </div>
                                @endif
                            </div>

                            <flux:button
                                variant="primary"
                                size="sm"
                                wire:click="syncToDrive"
                                icon="arrow-path"
                                :disabled="$this->unsyncedCount === 0"
                            >
                                <span wire:loading.remove wire:target="syncToDrive">
                                    @if ($this->unsyncedCount > 0)
                                        {{ __('Sync :count receipts to Drive', ['count' => $this->unsyncedCount]) }}
                                    @else
                                        {{ __('All receipts synced') }}
                                    @endif
                                </span>
                                <span wire:loading wire:target="syncToDrive">
                                    {{ __('Starting sync...') }}
                                </span>
                            </flux:button>
                        </div>
                    @endif
                @else
                    <div class="mt-4">
                        <flux:button variant="primary" wire:click="connectGoogle" icon="arrow-top-right-on-square">
                            {{ __('Connect Google Drive') }}
                        </flux:button>
                        <flux:text size="xs" class="mt-2 text-zinc-500">
                            {{ __('You\'ll be redirected to Google to authorize access. We only request permission to create and manage files in a dedicated Finvixy folder.') }}
                        </flux:text>
                    </div>
                @endif
            </div>

            {{-- Xero section --}}
            <div class="rounded-lg border border-zinc-800 p-5">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-800">
                            {{-- Xero logo mark --}}
                            <svg class="h-6 w-6" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                                <rect width="512" height="512" rx="15%" fill="#13B5EA"/>
                                <path d="M256 135.7c-66.4 0-120.3 53.9-120.3 120.3S189.6 376.3 256 376.3 376.3 322.4 376.3 256 322.4 135.7 256 135.7zm-47.2 155.6-33.5-33.5 33.5-33.5 33.5 33.5-33.5 33.5zm43.4 43.4-33.5-33.5 33.5-33.5 33.5 33.5-33.5 33.5zm3.8-43.4 33.5-33.5 33.5 33.5-33.5 33.5-33.5-33.5zm43.4-43.4-33.5 33.5-33.5-33.5 33.5-33.5 33.5 33.5z" fill="#fff"/>
                            </svg>
                        </div>
                        <div>
                            <flux:heading size="sm">{{ __('Xero') }}</flux:heading>
                            <flux:text size="sm" class="text-zinc-400">
                                {{ __('Push bills and expenses directly to Xero') }}
                            </flux:text>
                        </div>
                    </div>

                    @if ($this->xeroConnection)
                        <flux:badge color="green" size="sm">{{ __('Connected') }}</flux:badge>
                    @else
                        <flux:badge color="zinc" size="sm">{{ __('Not connected') }}</flux:badge>
                    @endif
                </div>

                @if (session('xero-connected'))
                    <div class="mt-4">
                        <flux:callout variant="success" icon="check-circle">
                            <flux:callout.heading>{{ __('Xero connected') }}</flux:callout.heading>
                            <flux:callout.text>{{ __('Connected to :org.', ['org' => session('xero-tenant', 'your organisation')]) }}</flux:callout.text>
                        </flux:callout>
                    </div>
                @endif

                @if (session('xero-error'))
                    <div class="mt-4">
                        <flux:callout variant="danger" icon="exclamation-triangle">
                            <flux:callout.heading>{{ __('Connection failed') }}</flux:callout.heading>
                            <flux:callout.text>{{ session('xero-error') }}</flux:callout.text>
                        </flux:callout>
                    </div>
                @endif

                @if (session('xero-disconnected'))
                    <div class="mt-4">
                        <flux:callout variant="warning" icon="information-circle">
                            <flux:callout.heading>{{ __('Xero disconnected') }}</flux:callout.heading>
                            <flux:callout.text>{{ __('Your Xero organisation has been unlinked.') }}</flux:callout.text>
                        </flux:callout>
                    </div>
                @endif

                @if ($this->xeroConnection)
                    <div class="mt-4 rounded-md border border-zinc-700/50 bg-zinc-800/50 p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <flux:icon.building-office class="h-5 w-5 text-zinc-400" />
                                <div>
                                    <flux:text size="sm" class="font-medium">
                                        {{ $this->xeroConnection->tenant_name ?? $this->xeroConnection->tenant_id }}
                                    </flux:text>
                                    <flux:text size="xs" class="text-zinc-500">
                                        {{ __('Connected :date', ['date' => $this->xeroConnection->created_at->diffForHumans()]) }}
                                        @if ($this->xeroConnection->isExpiringSoon(0))
                                            <span class="text-amber-400"> &middot; {{ __('Token expired — reconnect') }}</span>
                                        @endif
                                    </flux:text>
                                </div>
                            </div>
                            <flux:button
                                variant="danger"
                                size="sm"
                                wire:click="disconnectXero"
                                wire:confirm="{{ __('Are you sure you want to disconnect Xero?') }}"
                            >
                                {{ __('Disconnect') }}
                            </flux:button>
                        </div>
                    </div>

                    @if ($this->xeroConnection->isExpiringSoon(0))
                        <div class="mt-3">
                            <flux:button variant="primary" size="sm" :href="route('xero.connect')" wire:navigate>
                                {{ __('Reconnect Xero') }}
                            </flux:button>
                        </div>
                    @endif
                @else
                    <div class="mt-4">
                        <flux:button variant="primary" :href="route('xero.connect')" icon="arrow-top-right-on-square">
                            {{ __('Connect Xero') }}
                        </flux:button>
                        <flux:text size="xs" class="mt-2 text-zinc-500">
                            {{ __("You'll be redirected to Xero to authorise access.") }}
                        </flux:text>
                    </div>
                @endif
            </div>

        </div>
    </x-pages::settings.layout>
</section>
