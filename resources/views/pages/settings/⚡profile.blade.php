<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profile settings')] #[Layout('layouts.app.sidebar')] class extends Component {
    use ProfileValidationRules;

    public string $name = '';
    public string $email = '';
    public string $whatsapp_number = '';
    public bool $whatsapp_enabled = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->whatsapp_number = $user->whatsapp_number ?? '';
        $this->whatsapp_enabled = (bool) $user->whatsapp_enabled;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);
        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Save WhatsApp settings.
     */
    public function updateWhatsApp(): void
    {
        $this->validate([
            'whatsapp_number' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9]{7,15}$/'],
        ]);

        $user = Auth::user();
        $user->whatsapp_number = $this->whatsapp_number ?: null;
        $user->whatsapp_enabled = $this->whatsapp_number ? $this->whatsapp_enabled : false;
        $user->save();

        $this->dispatch('whatsapp-updated');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        <livewire:pages::settings.delete-user-form />

        {{-- WhatsApp Integration --}}
        <flux:separator class="my-8" />

        <div>
            <flux:heading size="lg">{{ __('WhatsApp Receipts') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Link your WhatsApp number to scan receipts by sending photos directly.') }}</flux:text>
        </div>

        <form wire:submit="updateWhatsApp" class="my-6 w-full space-y-6">
            <flux:field>
                <flux:label>{{ __('WhatsApp Number') }}</flux:label>
                <flux:input wire:model="whatsapp_number" type="tel" placeholder="+27821234567" />
                <flux:error name="whatsapp_number" />
                <flux:text size="xs" class="text-zinc-500 mt-1">
                    {{ __('Include country code (e.g. +27 for South Africa). This is the number you\'ll send receipt photos from.') }}
                </flux:text>
            </flux:field>

            @if ($whatsapp_number)
                <flux:field>
                    <div class="flex items-center gap-3">
                        <flux:switch wire:model="whatsapp_enabled" />
                        <flux:label>{{ __('Enable WhatsApp scanning') }}</flux:label>
                    </div>
                    <flux:text size="xs" class="text-zinc-500 mt-1">
                        {{ __('When enabled, send receipt photos to our WhatsApp number and they\'ll be automatically scanned and added to your expenses.') }}
                    </flux:text>
                </flux:field>
            @endif

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">
                    {{ __('Save WhatsApp Settings') }}
                </flux:button>

                <x-action-message class="me-3" on="whatsapp-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-pages::settings.layout>
</section>
