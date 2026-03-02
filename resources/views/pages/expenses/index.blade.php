<?php

use App\Models\Expense;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Expenses')] #[Layout('layouts.app.sidebar')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $sortBy = 'date';

    #[Url]
    public string $sortDir = 'desc';

    public bool $showUploadModal = false;

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

    public function deleteExpense(int $id): void
    {
        $user = Auth::user();
        $expense = Expense::query()
            ->where('organisation_id', $user->organisation_id)
            ->findOrFail($id);

        $expense->delete();
    }
} ?>

<div>
    {{-- Header --}}
    <div class="flex flex-col gap-4 mb-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" class="text-white">Expenses</flux:heading>
            <flux:text class="text-gray-400 mt-1">Manage and review your scanned receipts.</flux:text>
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
            <option value="processed">Processed</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
        </flux:select>
        <flux:select wire:model.live="category" class="w-48">
            <option value="">All categories</option>
            @foreach ($this->categories as $cat)
                <option value="{{ $cat }}">{{ ucwords(str_replace('-', ' ', $cat)) }}</option>
            @endforeach
        </flux:select>
    </div>

    {{-- Table --}}
    <div class="glow-card rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-white/5">
                        <th class="text-left p-4 text-gray-400 font-medium cursor-pointer hover:text-emerald-400" wire:click="sort('name')">
                            Name
                            @if ($sortBy === 'name')
                                <flux:icon :name="$sortDir === 'asc' ? 'chevron-up' : 'chevron-down'" class="inline size-3" />
                            @endif
                        </th>
                        <th class="text-left p-4 text-gray-400 font-medium">Category</th>
                        <th class="text-right p-4 text-gray-400 font-medium cursor-pointer hover:text-emerald-400" wire:click="sort('amount')">
                            Amount
                            @if ($sortBy === 'amount')
                                <flux:icon :name="$sortDir === 'asc' ? 'chevron-up' : 'chevron-down'" class="inline size-3" />
                            @endif
                        </th>
                        <th class="text-left p-4 text-gray-400 font-medium cursor-pointer hover:text-emerald-400" wire:click="sort('date')">
                            Date
                            @if ($sortBy === 'date')
                                <flux:icon :name="$sortDir === 'asc' ? 'chevron-up' : 'chevron-down'" class="inline size-3" />
                            @endif
                        </th>
                        <th class="text-left p-4 text-gray-400 font-medium">Status</th>
                        <th class="text-right p-4 text-gray-400 font-medium">Drive</th>
                        <th class="text-right p-4 text-gray-400 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->expenses as $expense)
                        <tr class="border-b border-white/5 hover:bg-white/[0.02] transition" wire:key="row-{{ $expense->id }}">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-500/10 shrink-0">
                                        <flux:icon name="document-text" variant="mini" class="size-4 text-emerald-400" />
                                    </div>
                                    <span class="text-white font-medium truncate max-w-[200px]">{{ $expense->name }}</span>
                                </div>
                            </td>
                            <td class="p-4">
                                @if ($expense->category)
                                    <flux:badge size="sm">{{ ucwords(str_replace('-', ' ', $expense->category)) }}</flux:badge>
                                @else
                                    <flux:text class="text-gray-600">—</flux:text>
                                @endif
                            </td>
                            <td class="p-4 text-right text-white font-medium">R{{ number_format($expense->amount, 2) }}</td>
                            <td class="p-4 text-gray-400">{{ $expense->date->format('d M Y') }}</td>
                            <td class="p-4">
                                <flux:badge size="sm" :color="match($expense->status) { 'processed' => 'green', 'pending' => 'yellow', 'approved' => 'green', 'rejected' => 'red', default => 'zinc' }">
                                    {{ ucfirst($expense->status) }}
                                </flux:badge>
                            </td>
                            <td class="p-4 text-right">
                                @if ($expense->drive_web_link)
                                    <a href="{{ $expense->drive_web_link }}" target="_blank" class="text-emerald-400 hover:text-emerald-300">
                                        <flux:icon name="cloud-arrow-up" class="size-4 inline" />
                                    </a>
                                @else
                                    <flux:text class="text-gray-600">—</flux:text>
                                @endif
                            </td>
                            <td class="p-4 text-right">
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                    <flux:menu>
                                        <flux:menu.item icon="eye">View details</flux:menu.item>
                                        <flux:menu.item icon="trash" variant="danger" wire:click="deleteExpense({{ $expense->id }})" wire:confirm="Are you sure you want to delete this expense?">Delete</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-12 text-center">
                                <flux:icon name="document-plus" class="size-12 text-gray-600 mx-auto mb-3" />
                                <flux:heading size="lg" class="text-gray-400">No expenses yet</flux:heading>
                                <flux:text class="text-gray-500 mt-1">Upload your first receipt to get started.</flux:text>
                                <flux:button variant="primary" icon="plus" class="mt-4" wire:click="$set('showUploadModal', true)">
                                    Upload Receipt
                                </flux:button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->expenses->hasPages())
            <div class="p-4 border-t border-white/5">
                {{ $this->expenses->links() }}
            </div>
        @endif
    </div>

    {{-- Upload Modal (placeholder - will be implemented in Phase 4) --}}
    <flux:modal wire:model="showUploadModal">
        <div class="space-y-4">
            <flux:heading size="lg">Upload Receipt</flux:heading>
            <flux:text class="text-gray-400">
                Receipt upload will be available once the scanning service is connected. For now, you can scan receipts via WhatsApp.
            </flux:text>
            <flux:button variant="primary" wire:click="$set('showUploadModal', false)">
                Got it
            </flux:button>
        </div>
    </flux:modal>
</div>
