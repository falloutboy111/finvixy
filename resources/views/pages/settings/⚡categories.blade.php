<?php

use App\Jobs\SyncCategoryFoldersToDrive;
use App\Models\ExpenseCategory;
use App\Models\Organisation;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Expense Categories')] #[Layout('layouts.app.sidebar')] class extends Component {
    /** @var array<int, array{id: int, name: string, slug: string, description: string|null, is_default: bool}> */
    public array $categories = [];

    public string $newName = '';
    public string $newDescription = '';

    public ?int $editingId = null;
    public string $editName = '';
    public string $editDescription = '';

    public bool $showAddForm = false;

    public function mount(): void
    {
        $this->loadCategories();
    }

    public function loadCategories(): void
    {
        $user = Auth::user();

        if (! $user->organisation_id) {
            $this->categories = [];

            return;
        }

        $this->categories = ExpenseCategory::query()
            ->where('organisation_id', $user->organisation_id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description', 'is_default', 'sort_order'])
            ->toArray();
    }

    public function addCategory(): void
    {
        $this->validate([
            'newName' => ['required', 'string', 'max:50'],
            'newDescription' => ['nullable', 'string', 'max:255'],
        ]);

        $user = Auth::user();

        if (! $user->organisation_id) {
            return;
        }

        // Limit to 50 categories
        $count = ExpenseCategory::where('organisation_id', $user->organisation_id)->count();
        if ($count >= 50) {
            $this->addError('newName', 'Maximum of 50 categories reached. Delete unused categories first.');

            return;
        }

        // Check for duplicate name
        $exists = ExpenseCategory::where('organisation_id', $user->organisation_id)
            ->where('name', $this->newName)
            ->exists();

        if ($exists) {
            $this->addError('newName', 'A category with this name already exists.');

            return;
        }

        $maxSort = ExpenseCategory::where('organisation_id', $user->organisation_id)->max('sort_order') ?? 0;

        ExpenseCategory::create([
            'organisation_id' => $user->organisation_id,
            'name' => $this->newName,
            'description' => $this->newDescription ?: null,
            'is_default' => false,
            'sort_order' => $maxSort + 1,
        ]);

        $this->reset('newName', 'newDescription', 'showAddForm');
        $this->loadCategories();

        // Sync new category folder to Google Drive
        if ($user->organisation_id) {
            SyncCategoryFoldersToDrive::dispatch($user->organisation_id, $user->id);
        }

        $this->dispatch('category-saved');
    }

    public function startEdit(int $id): void
    {
        $category = collect($this->categories)->firstWhere('id', $id);

        if (! $category) {
            return;
        }

        $this->editingId = $id;
        $this->editName = $category['name'];
        $this->editDescription = $category['description'] ?? '';
    }

    public function cancelEdit(): void
    {
        $this->reset('editingId', 'editName', 'editDescription');
    }

    public function updateCategory(): void
    {
        $this->validate([
            'editName' => ['required', 'string', 'max:50'],
            'editDescription' => ['nullable', 'string', 'max:255'],
        ]);

        $user = Auth::user();
        $category = ExpenseCategory::where('organisation_id', $user->organisation_id)
            ->find($this->editingId);

        if (! $category) {
            return;
        }

        if ($category->is_default) {
            $this->addError('editName', 'Default categories cannot be renamed.');

            return;
        }

        // Check for duplicate name
        $exists = ExpenseCategory::where('organisation_id', $user->organisation_id)
            ->where('name', $this->editName)
            ->where('id', '!=', $this->editingId)
            ->exists();

        if ($exists) {
            $this->addError('editName', 'A category with this name already exists.');

            return;
        }

        $category->update([
            'name' => $this->editName,
            'description' => $this->editDescription ?: null,
        ]);

        $this->reset('editingId', 'editName', 'editDescription');
        $this->loadCategories();

        $this->dispatch('category-saved');
    }

    public function deleteCategory(int $id): void
    {
        $user = Auth::user();
        $category = ExpenseCategory::where('organisation_id', $user->organisation_id)
            ->find($id);

        if (! $category) {
            return;
        }

        if ($category->is_default) {
            session()->flash('error', 'Default categories cannot be deleted.');

            return;
        }

        $category->delete();
        $this->loadCategories();

        $this->dispatch('category-deleted');
    }

    public function resetToDefaults(): void
    {
        $user = Auth::user();

        if (! $user->organisation_id) {
            return;
        }

        // Delete all custom categories
        ExpenseCategory::where('organisation_id', $user->organisation_id)
            ->where('is_default', false)
            ->delete();

        // Ensure all defaults exist
        $organisation = Organisation::find($user->organisation_id);
        $existing = ExpenseCategory::where('organisation_id', $user->organisation_id)
            ->pluck('slug')
            ->toArray();

        foreach (Organisation::$defaultCategories as $index => $cat) {
            if (! in_array($cat['slug'], $existing)) {
                ExpenseCategory::create([
                    'organisation_id' => $user->organisation_id,
                    'name' => $cat['name'],
                    'slug' => $cat['slug'],
                    'description' => $cat['description'],
                    'is_default' => true,
                    'sort_order' => $index,
                ]);
            }
        }

        $this->loadCategories();
        $this->dispatch('categories-reset');

        // Sync all category folders to Drive
        if ($user->organisation_id) {
            SyncCategoryFoldersToDrive::dispatch($user->organisation_id, $user->id);
        }
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Expense Categories') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Expense Categories')" :subheading="__('Manage the categories used to classify your receipts. The AI will use these when scanning.')">
        <div class="my-6 w-full space-y-6">

            {{-- Info callout --}}
            <flux:callout variant="outline">
                <flux:callout.heading>{{ __('How categories work') }}</flux:callout.heading>
                <flux:callout.text>{{ __('When you scan a receipt, the AI automatically assigns it to one of these categories. Add custom categories that match your business needs.') }}</flux:callout.text>
            </flux:callout>

            {{-- Category list --}}
            <div class="space-y-2">
                @forelse ($categories as $category)
                    @if ($editingId === $category['id'])
                        {{-- Inline edit form --}}
                        <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/5 p-3">
                            <form wire:submit="updateCategory" class="space-y-3">
                                <flux:input
                                    wire:model="editName"
                                    :label="__('Name')"
                                    placeholder="Category name"
                                    maxlength="50"
                                    required
                                />
                                <flux:error name="editName" />

                                <flux:input
                                    wire:model="editDescription"
                                    :label="__('Description')"
                                    placeholder="Short description for AI context"
                                    maxlength="255"
                                />

                                <div class="flex items-center gap-2">
                                    <flux:button type="submit" variant="primary" size="sm">
                                        {{ __('Save') }}
                                    </flux:button>
                                    <flux:button type="button" variant="ghost" size="sm" wire:click="cancelEdit">
                                        {{ __('Cancel') }}
                                    </flux:button>
                                </div>
                            </form>
                        </div>
                    @else
                        <div class="group flex items-center justify-between rounded-lg border border-zinc-800 px-3 py-2.5 transition hover:border-zinc-700">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-medium text-white">{{ $category['name'] }}</p>
                                    @if ($category['is_default'])
                                        <flux:badge size="sm" color="zinc">{{ __('Default') }}</flux:badge>
                                    @endif
                                </div>
                                @if ($category['description'])
                                    <p class="mt-0.5 text-xs text-zinc-500 truncate">{{ $category['description'] }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                @unless ($category['is_default'])
                                    <flux:button variant="ghost" size="sm" icon="pencil-square" wire:click="startEdit({{ $category['id'] }})" />
                                    <flux:button variant="ghost" size="sm" icon="trash" wire:click="deleteCategory({{ $category['id'] }})" wire:confirm="Are you sure you want to delete this category?" />
                                @endunless
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="rounded-lg border border-dashed border-zinc-700 p-6 text-center">
                        <flux:icon name="tag" class="mx-auto size-8 text-zinc-600" />
                        <p class="mt-2 text-sm text-zinc-400">{{ __('No categories yet.') }}</p>
                        <p class="text-xs text-zinc-500">{{ __('Add categories or reset to defaults below.') }}</p>
                    </div>
                @endforelse
            </div>

            {{-- Add category form --}}
            @if ($showAddForm)
                <div class="rounded-lg border border-zinc-700 bg-zinc-800/30 p-4">
                    <form wire:submit="addCategory" class="space-y-3">
                        <flux:input
                            wire:model="newName"
                            :label="__('Category name')"
                            placeholder="e.g. Vehicle Expenses"
                            maxlength="50"
                            required
                        />
                        <flux:error name="newName" />

                        <flux:input
                            wire:model="newDescription"
                            :label="__('Description (optional)')"
                            placeholder="Fuel, tolls, maintenance, parking"
                            maxlength="255"
                        />

                        <div class="flex items-center gap-2">
                            <flux:button type="submit" variant="primary" size="sm">
                                {{ __('Add Category') }}
                            </flux:button>
                            <flux:button type="button" variant="ghost" size="sm" wire:click="$set('showAddForm', false)">
                                {{ __('Cancel') }}
                            </flux:button>
                        </div>
                    </form>
                </div>
            @else
                <flux:button variant="primary" icon="plus" wire:click="$set('showAddForm', true)">
                    {{ __('Add Category') }}
                </flux:button>
            @endif

            {{-- Actions --}}
            <flux:separator />

            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-zinc-500">{{ count($categories) }} / 50 {{ __('categories') }}</p>
                </div>
                <flux:button variant="ghost" size="sm" wire:click="resetToDefaults" wire:confirm="This will remove all custom categories and restore defaults. Are you sure?">
                    {{ __('Reset to Defaults') }}
                </flux:button>
            </div>

            <x-action-message on="category-saved">
                {{ __('Category saved.') }}
            </x-action-message>
            <x-action-message on="category-deleted">
                {{ __('Category deleted.') }}
            </x-action-message>
            <x-action-message on="categories-reset">
                {{ __('Categories reset to defaults.') }}
            </x-action-message>
        </div>
    </x-pages::settings.layout>
</section>
