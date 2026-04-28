<?php

use App\Jobs\ProcessExpenseImage;
use App\Models\Expense;
use App\Services\OrgStorageService;
use App\Services\PlanLimitService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Title('Expenses')] #[Layout('layouts.app.sidebar')] class extends Component {
    use WithFileUploads;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $sortBy = 'created_at';

    #[Url]
    public string $sortDir = 'desc';

    public bool $showUploadModal = false;

    public bool $showDetailModal = false;

    public ?int $viewingExpenseId = null;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    #[Validate(['receipts.*' => 'required|file|mimes:jpeg,jpg,png,gif,webp,pdf|max:10240'])]
    public array $receipts = [];

    public bool $uploading = false;

    /**
     * Get current plan limit info for the logged-in user.
     *
     * @return array{allowed: bool, used: int, limit: int|string, remaining: int|string}
     */
    #[Computed]
    public function planLimit(): array
    {
        return app(PlanLimitService::class)->checkReceiptLimit(Auth::user(), 0);
    }

    /**
     * Check if the user has hit their plan limit.
     */
    #[Computed]
    public function isAtLimit(): bool
    {
        $limit = $this->planLimit;

        if ($limit['limit'] === 'unlimited' || $limit['limit'] === 0) {
            return false;
        }

        return $limit['used'] >= $limit['limit'];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'desc';
        }
    }

    public function getExpensesProperty()
    {
        $user = Auth::user();

        if (! $user->organisation_id) {
            return Expense::query()->where('id', 0)->paginate(15);
        }

        return Expense::query()
            ->where('organisation_id', $user->organisation_id)
            ->excludeDuplicates()
            ->with('user')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('category', 'like', '%' . $this->search . '%')
                    ->orWhere('notes', 'like', '%' . $this->search . '%');
            }))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->category, fn ($q) => $q->where('category', $this->category))
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate(15);
    }

    /**
     * @return list<string>
     */
    public function getCategoriesProperty(): array
    {
        $user = Auth::user();

        if (! $user->organisation_id) {
            return [];
        }

        return Expense::query()
            ->where('organisation_id', $user->organisation_id)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values()
            ->toArray();
    }

    public function getHasProcessingProperty(): bool
    {
        $user = Auth::user();

        if (! $user->organisation_id) {
            return false;
        }

        return Expense::query()
            ->where('organisation_id', $user->organisation_id)
            ->whereIn('status', ['pending', 'processing'])
            ->whereNotNull('receipt_path')
            ->exists();
    }

    public function getViewingExpenseProperty(): ?Expense
    {
        if (! $this->viewingExpenseId) {
            return null;
        }

        return Expense::query()
            ->where('organisation_id', Auth::user()->organisation_id)
            ->with('expenseItems')
            ->find($this->viewingExpenseId);
    }

    /**
     * Generate a time-limited pre-signed URL for the receipt (15 minutes).
     */
    public function getReceiptUrl(int $expenseId): ?string
    {
        $user = Auth::user();

        $expense = Expense::query()
            ->where('organisation_id', $user->organisation_id)
            ->find($expenseId);

        if (! $expense || ! $expense->receipt_path) {
            return null;
        }

        $storageService = new OrgStorageService($user->organisation);

        return $storageService->temporaryUrl($expense->receipt_path, 15);
    }

    public function viewExpense(int $id): void
    {
        $this->viewingExpenseId = $id;
        $this->showDetailModal = true;
    }

    /**
     * Open the receipt in a new tab via a temporary pre-signed URL.
     */
    public function viewReceipt(int $expenseId): void
    {
        $url = $this->getReceiptUrl($expenseId);

        if (! $url) {
            session()->flash('error', 'Receipt file not available.');

            return;
        }

        $this->js("window.open('{$url}', '_blank')");
    }

    /**
     * Download the receipt via a temporary pre-signed URL with content-disposition.
     */
    public function downloadReceipt(int $expenseId): void
    {
        $user = Auth::user();

        $expense = Expense::query()
            ->where('organisation_id', $user->organisation_id)
            ->find($expenseId);

        if (! $expense || ! $expense->receipt_path) {
            session()->flash('error', 'Receipt file not available.');

            return;
        }

        $storageService = new OrgStorageService($user->organisation);
        $filename = basename($expense->receipt_path);

        $url = $storageService->temporaryUrl($expense->receipt_path, 15);

        $this->js("
            const a = document.createElement('a');
            a.href = '{$url}';
            a.download = '{$filename}';
            document.body.appendChild(a);
            a.click();
            a.remove();
        ");
    }

    public function uploadReceipts(): void
    {
        $this->validate();

        $user = Auth::user();
        $limitService = app(PlanLimitService::class);
        $limit = $limitService->checkReceiptLimit($user, count($this->receipts));

        if (! $limit['allowed']) {
            $this->addError('receipts', "Monthly receipt limit reached ({$limit['used']}/{$limit['limit']}). Upgrade your plan for more.");
            return;
        }

        $this->uploading = true;

        $storageService = new OrgStorageService($user->organisation);

        foreach ($this->receipts as $file) {
            $path = $storageService->store('receipts', $file);

            $expense = Expense::query()->create([
                'organisation_id' => $user->organisation_id,
                'user_id' => $user->id,
                'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'amount' => 0,
                'date' => now(),
                'receipt_path' => $path,
                'status' => 'pending',
                'is_duplicate' => false,
            ]);

            ProcessExpenseImage::dispatch($expense);
        }

        $this->receipts = [];
        $this->uploading = false;
        $this->showUploadModal = false;

        session()->flash('message', count($this->receipts) > 1
            ? 'Receipts uploaded! Processing will happen in the background.'
            : 'Receipt uploaded! Processing will happen in the background.'
        );
    }

    public function deleteExpense(int $id): void
    {
        $user = Auth::user();
        $expense = Expense::query()
            ->where('organisation_id', $user->organisation_id)
            ->findOrFail($id);

        // Clean up receipt file and reclaim storage
        if ($expense->receipt_path) {
            $storageService = new OrgStorageService($user->organisation);
            $storageService->delete($expense->receipt_path);
        }

        $expense->expenseItems()->delete();
        $expense->delete();
    }

    public function reprocessExpense(int $id): void
    {
        $user = Auth::user();
        $expense = Expense::query()
            ->where('organisation_id', $user->organisation_id)
            ->findOrFail($id);

        if (! $expense->receipt_path) {
            session()->flash('error', 'No receipt file to reprocess.');
            return;
        }

        // Clear previous results
        $expense->expenseItems()->delete();
        $expense->update([
            'status' => 'pending',
            'extracted_data' => null,
            'additional_fields' => null,
        ]);

        // Re-dispatch the processing job
        ProcessExpenseImage::dispatch($expense);

        session()->flash('message', 'Reprocessing started for "' . $expense->name . '".');
    }

    public function removeReceipt(int $index): void
    {
        unset($this->receipts[$index]);
        $this->receipts = array_values($this->receipts);
    }
} ?>

<div @if($this->hasProcessing) wire:poll.3s @endif>
    {{-- Flash message --}}
    @if (session('message'))
        <div class="mb-4">
            <flux:callout variant="success" icon="check-circle">
                {{ session('message') }}
            </flux:callout>
        </div>
    @endif

    {{-- Processing banner --}}
    @if ($this->hasProcessing)
        <div class="mb-4 flex items-center gap-3 rounded-xl bg-blue-500/10 ring-1 ring-blue-500/20 px-4 py-3">
            <svg class="size-5 text-blue-400 animate-spin shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-sm text-blue-300">Receipts are being processed&hellip; This page will update automatically.</p>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col gap-4 mb-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Expenses</flux:heading>
            <flux:text class="mt-1">Manage and review your scanned receipts.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="$set('showUploadModal', true)">
            Upload Receipt
        </flux:button>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center">
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search expenses..."
                icon="magnifying-glass"
            />
        </div>
        <flux:select wire:model.live="status" class="w-40">
            <option value="">All statuses</option>
            <option value="pending">Pending</option>
            <option value="processing">Processing</option>
            <option value="processed">Processed</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="failed">Failed</option>
        </flux:select>
        <flux:select wire:model.live="category" class="w-48">
            <option value="">All categories</option>
            @foreach ($this->categories as $cat)
                <option value="{{ $cat }}">{{ ucwords(str_replace('-', ' ', $cat)) }}</option>
            @endforeach
        </flux:select>
    </div>

    {{-- Plan limit banner --}}
    @if ($this->isAtLimit)
        <div class="mb-4 rounded-xl bg-amber-500/10 ring-1 ring-amber-500/20 px-4 py-3">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <flux:icon name="exclamation-triangle" class="size-5 text-amber-400 shrink-0" />
                    <div>
                        <p class="text-sm font-medium text-amber-300">Monthly receipt limit reached ({{ $this->planLimit['used'] }}/{{ $this->planLimit['limit'] }})</p>
                        <p class="text-xs text-amber-400/70 mt-0.5">Upgrade your plan to continue scanning receipts.</p>
                    </div>
                </div>
                <flux:button variant="primary" size="sm" :href="route('billing')">Upgrade Plan</flux:button>
            </div>
        </div>
    @endif

    {{-- Sort bar --}}
    <div class="flex items-center gap-2 mb-4">
        <flux:text class="text-xs text-zinc-500">Sort by:</flux:text>
        <button wire:click="sort('created_at')" class="text-xs px-2 py-1 rounded-md transition-colors {{ $sortBy === 'created_at' ? 'text-emerald-400 bg-emerald-500/10' : 'text-zinc-400 hover:text-white' }}">
            Uploaded
            @if ($sortBy === 'created_at')
                <flux:icon :name="$sortDir === 'asc' ? 'chevron-up' : 'chevron-down'" variant="micro" class="inline size-3" />
            @endif
        </button>
        <button wire:click="sort('date')" class="text-xs px-2 py-1 rounded-md transition-colors {{ $sortBy === 'date' ? 'text-emerald-400 bg-emerald-500/10' : 'text-zinc-400 hover:text-white' }}">
            Receipt Date
            @if ($sortBy === 'date')
                <flux:icon :name="$sortDir === 'asc' ? 'chevron-up' : 'chevron-down'" variant="micro" class="inline size-3" />
            @endif
        </button>
        <button wire:click="sort('amount')" class="text-xs px-2 py-1 rounded-md transition-colors {{ $sortBy === 'amount' ? 'text-emerald-400 bg-emerald-500/10' : 'text-zinc-400 hover:text-white' }}">
            Amount
            @if ($sortBy === 'amount')
                <flux:icon :name="$sortDir === 'asc' ? 'chevron-up' : 'chevron-down'" variant="micro" class="inline size-3" />
            @endif
        </button>
        <button wire:click="sort('name')" class="text-xs px-2 py-1 rounded-md transition-colors {{ $sortBy === 'name' ? 'text-emerald-400 bg-emerald-500/10' : 'text-zinc-400 hover:text-white' }}">
            Name
            @if ($sortBy === 'name')
                <flux:icon :name="$sortDir === 'asc' ? 'chevron-up' : 'chevron-down'" variant="micro" class="inline size-3" />
            @endif
        </button>
    </div>

    {{-- Expense Cards --}}
    <div class="space-y-3">
        @forelse ($this->expenses as $expense)
            @php
                $statusColor = match($expense->status) {
                    'processed', 'approved' => 'green',
                    'pending' => 'yellow',
                    'processing' => 'blue',
                    'rejected', 'failed' => 'red',
                    default => 'zinc',
                };
            @endphp
            <div class="glow-card rounded-xl p-4 cursor-pointer {{ in_array($expense->status, ['pending', 'processing']) ? 'ring-1 ring-emerald-500/30 animate-pulse' : '' }}" wire:click="viewExpense({{ $expense->id }})" wire:key="card-{{ $expense->id }}">
                <div class="flex items-start gap-3">
                    {{-- Icon --}}
                    @if (in_array($expense->status, ['pending', 'processing']))
                        <span class="flex items-center justify-center size-10 rounded-lg bg-blue-500/10 ring-1 ring-blue-500/30 shrink-0 mt-0.5">
                            <svg class="size-5 text-blue-400 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    @else
                        <span class="flex items-center justify-center size-10 rounded-lg bg-emerald-500/10 ring-1 ring-emerald-500/20 shrink-0 mt-0.5">
                            <flux:icon name="document-text" variant="mini" class="size-5 text-emerald-400" />
                        </span>
                    @endif

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-white truncate">{{ $expense->name }}</p>
                                <p class="text-xs text-zinc-500 mt-0.5">
                                    @if ($expense->date)
                                        {{ $expense->date->format('d M Y') }}
                                        <span class="text-zinc-600">&middot;</span>
                                    @endif
                                    <span class="text-zinc-600">Uploaded {{ $expense->created_at?->diffForHumans() ?? 'N/A' }}</span>
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-bold text-white">R{{ number_format($expense->amount, 2) }}</p>
                                <flux:badge size="sm" :color="$statusColor" class="mt-1">{{ ucfirst($expense->status) }}</flux:badge>
                            </div>
                        </div>

                        {{-- Meta row --}}
                        <div class="flex items-center gap-2 mt-2">
                            @if ($expense->category)
                                <span class="inline-flex items-center text-[11px] font-medium text-zinc-400 bg-white/[0.05] px-2 py-0.5 rounded-md capitalize">
                                    {{ str_replace('-', ' ', $expense->category) }}
                                </span>
                            @endif
                            @if ($expense->drive_web_link)
                                <a href="{{ $expense->drive_web_link }}" target="_blank" wire:click.stop class="inline-flex items-center gap-1 text-[11px] text-emerald-400 hover:text-emerald-300">
                                    <flux:icon name="cloud-arrow-up" variant="micro" class="size-3" />
                                    Drive
                                </a>
                            @endif
                            @if ($expense->is_duplicate)
                                <span class="inline-flex items-center text-[11px] text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-md">Duplicate</span>
                            @endif
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="shrink-0" wire:click.stop>
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                            <flux:menu>
                                <flux:menu.item icon="eye" wire:click="viewExpense({{ $expense->id }})">View details</flux:menu.item>
                                <flux:menu.item icon="arrow-path" wire:click="reprocessExpense({{ $expense->id }})" wire:confirm="Reprocess this receipt? Previous results will be replaced.">Reprocess</flux:menu.item>
                                <flux:menu.item icon="trash" variant="danger" wire:click="deleteExpense({{ $expense->id }})" wire:confirm="Are you sure you want to delete this expense?">Delete</flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </div>
            </div>
        @empty
            <div class="glow-card rounded-2xl p-12 text-center">
                <div class="flex items-center justify-center size-14 rounded-2xl bg-zinc-800/80 ring-1 ring-zinc-700/50 mx-auto mb-4">
                    <flux:icon name="document-plus" class="size-7 text-zinc-500" />
                </div>
                <p class="text-sm font-medium text-zinc-400">No expenses yet</p>
                <p class="text-xs text-zinc-600 mt-1">Upload your first receipt to get started.</p>
                <div class="mt-4">
                    <flux:button variant="primary" icon="plus" wire:click="$set('showUploadModal', true)">
                        Upload Receipt
                    </flux:button>
                </div>
            </div>
        @endforelse
    </div>

    @if ($this->expenses->hasPages())
        <div class="mt-6">
            {{ $this->expenses->links() }}
        </div>
    @endif

    {{-- Upload Modal --}}
    <flux:modal wire:model="showUploadModal" class="max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Upload Receipts</flux:heading>
                <flux:text class="mt-1">Upload receipt images or PDFs to scan and extract expense data.</flux:text>
            </div>

            <div>
                {{-- Drop zone --}}
                <label
                    class="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed rounded-xl cursor-pointer transition-colors
                           border-zinc-700 hover:border-emerald-500/40 bg-white/[0.02] hover:bg-emerald-500/5"
                    for="receipt-upload"
                >
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        <flux:icon name="cloud-arrow-up" class="size-8 text-zinc-500 mb-2" />
                        <p class="text-sm text-zinc-400"><span class="font-semibold text-emerald-400">Click to upload</span> or drag and drop</p>
                        <p class="text-xs text-zinc-600 mt-1">JPEG, PNG, WEBP, GIF, PDF (max 10MB each)</p>
                    </div>
                    <input
                        id="receipt-upload"
                        type="file"
                        wire:model="receipts"
                        multiple
                        accept="image/jpeg,image/png,image/gif,image/webp,application/pdf"
                        class="hidden"
                    />
                </label>

                {{-- Error messages --}}
                @error('receipts')
                    <p class="text-sm text-red-400 mt-2">{{ $message }}</p>
                @enderror
                @error('receipts.*')
                    <p class="text-sm text-red-400 mt-2">{{ $message }}</p>
                @enderror
            </div>

            {{-- Selected files preview --}}
            @if (count($receipts) > 0)
                <div class="space-y-2">
                    <flux:text class="text-sm font-medium">{{ count($receipts) }} file(s) selected</flux:text>
                    @foreach ($receipts as $index => $file)
                        <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-white/[0.03] border border-zinc-800" wire:key="file-{{ $index }}">
                            <div class="flex items-center gap-2 min-w-0">
                                <flux:icon name="document-text" variant="mini" class="size-4 text-emerald-400 shrink-0" />
                                <span class="text-sm text-zinc-300 truncate">{{ $file->getClientOriginalName() }}</span>
                                <span class="text-xs text-zinc-600 shrink-0">{{ number_format($file->getSize() / 1024, 0) }} KB</span>
                            </div>
                            <button wire:click="removeReceipt({{ $index }})" class="text-zinc-500 hover:text-red-400 transition-colors p-1">
                                <flux:icon name="x-mark" variant="micro" class="size-4" />
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Actions --}}
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showUploadModal', false)">Cancel</flux:button>
                <flux:button
                    variant="primary"
                    icon="arrow-up-tray"
                    wire:click="uploadReceipts"
                    wire:loading.attr="disabled"
                    :disabled="count($receipts) === 0"
                >
                    <span wire:loading.remove wire:target="uploadReceipts">
                        Upload & Scan {{ count($receipts) > 0 ? '(' . count($receipts) . ')' : '' }}
                    </span>
                    <span wire:loading wire:target="uploadReceipts">
                        Uploading...
                    </span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Expense Detail Modal --}}
    <flux:modal wire:model="showDetailModal" class="max-w-2xl">
        @if ($this->viewingExpense)
            @php $exp = $this->viewingExpense; @endphp
            <div class="space-y-6">
                <div class="flex items-start justify-between">
                    <div>
                        <flux:heading size="lg">{{ $exp->name }}</flux:heading>
                        <flux:text class="mt-1">{{ $exp->date?->format('d F Y') }}</flux:text>
                    </div>
                    <flux:badge :color="match($exp->status) { 'processed', 'approved' => 'green', 'pending' => 'yellow', 'processing' => 'blue', default => 'red' }">
                        {{ ucfirst($exp->status) }}
                    </flux:badge>
                </div>

                {{-- Details grid --}}
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-3 rounded-lg bg-white/[0.03] border border-zinc-800">
                        <p class="text-xs text-zinc-500 mb-1">Amount</p>
                        <p class="text-lg font-semibold text-white">R{{ number_format($exp->amount, 2) }}</p>
                    </div>
                    <div class="p-3 rounded-lg bg-white/[0.03] border border-zinc-800">
                        <p class="text-xs text-zinc-500 mb-1">Category</p>
                        <p class="text-sm text-white capitalize">{{ $exp->category ? str_replace('-', ' ', $exp->category) : 'Uncategorised' }}</p>
                    </div>
                    @if ($exp->tax)
                        <div class="p-3 rounded-lg bg-white/[0.03] border border-zinc-800">
                            <p class="text-xs text-zinc-500 mb-1">Tax</p>
                            <p class="text-sm text-white">R{{ number_format($exp->tax, 2) }}</p>
                        </div>
                    @endif
                    @if ($exp->additional_fields && isset($exp->additional_fields['invoice_number']))
                        <div class="p-3 rounded-lg bg-white/[0.03] border border-zinc-800">
                            <p class="text-xs text-zinc-500 mb-1">Invoice #</p>
                            <p class="text-sm text-white">{{ $exp->additional_fields['invoice_number'] }}</p>
                        </div>
                    @endif
                </div>

                {{-- Line items --}}
                @if ($exp->expenseItems->isNotEmpty())
                    <div>
                        <flux:heading size="sm" class="mb-3">Line Items</flux:heading>
                        <div class="border border-zinc-800 rounded-lg overflow-hidden">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-zinc-800 bg-white/[0.02]">
                                        <th class="text-left p-3 text-zinc-500 font-medium">Item</th>
                                        <th class="text-right p-3 text-zinc-500 font-medium">Qty</th>
                                        <th class="text-right p-3 text-zinc-500 font-medium">Price</th>
                                        <th class="text-right p-3 text-zinc-500 font-medium">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($exp->expenseItems as $item)
                                        <tr class="border-b border-zinc-800/50 last:border-0" wire:key="item-{{ $item->id }}">
                                            <td class="p-3 text-zinc-300">{{ $item->name }}</td>
                                            <td class="p-3 text-right text-zinc-400">{{ $item->qty }}</td>
                                            <td class="p-3 text-right text-zinc-400">R{{ number_format($item->price, 2) }}</td>
                                            <td class="p-3 text-right text-white font-medium">R{{ number_format($item->total, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- Notes --}}
                @if ($exp->notes)
                    <div>
                        <flux:heading size="sm" class="mb-2">Notes</flux:heading>
                        <flux:text>{{ $exp->notes }}</flux:text>
                    </div>
                @endif

                {{-- Receipt image link --}}
                @if ($exp->receipt_path)
                    <div class="flex items-center gap-2 text-sm">
                        <flux:icon name="paper-clip" variant="mini" class="size-4 text-zinc-500" />
                        <span class="text-zinc-400">Receipt file attached</span>
                        <div class="flex items-center gap-2 ml-auto">
                            <flux:button size="xs" variant="ghost" icon="eye" wire:click="viewReceipt({{ $exp->id }})">
                                View
                            </flux:button>
                            <flux:button size="xs" variant="ghost" icon="arrow-down-tray" wire:click="downloadReceipt({{ $exp->id }})">
                                Download
                            </flux:button>
                            @if ($exp->drive_web_link)
                                <a href="{{ $exp->drive_web_link }}" target="_blank" class="text-emerald-400 hover:text-emerald-300 text-xs">
                                    Drive
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="flex justify-end">
                    <flux:button variant="ghost" wire:click="$set('showDetailModal', false)">Close</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
