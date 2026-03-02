<?php

use App\Services\EmailOtpService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Enable email verification')] #[Layout('layouts.auth.simple')] class extends Component {
    public function enable(): void
    {
        $user = Auth::user();

        $user->update([
            'email_2fa_enabled_at' => now(),
        ]);

        session()->flash('status', __('Email two-factor authentication has been enabled.'));
        $this->redirect(route('dashboard'));
    }

    public function skip(): void
    {
        session()->put('email_2fa_prompt_dismissed', true);
        $this->redirect(route('dashboard'));
    }
} ?>

<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('Secure your account')"
        :description="__('Your account is 7 days old. We recommend enabling email verification to protect your account.')"
    />

    <div class="space-y-4">
        <div class="glow-card rounded-xl p-4">
            <div class="flex items-start gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-emerald-500/10 shrink-0">
                    <flux:icon name="shield-check" class="size-5 text-emerald-400" />
                </div>
                <div>
                    <flux:heading size="sm">{{ __('How it works') }}</flux:heading>
                    <flux:text class="mt-1">
                        {{ __('Each time you log in, we\'ll send a 6-digit code to your email. Enter the code to confirm it\'s you.') }}
                    </flux:text>
                </div>
            </div>
        </div>

        <flux:button
            variant="primary"
            wire:click="enable"
            class="w-full"
        >
            {{ __('Enable email verification') }}
        </flux:button>

        <div class="text-center">
            <button wire:click="skip" class="text-sm text-gray-400 underline cursor-pointer hover:text-gray-300">
                {{ __('Remind me later') }}
            </button>
        </div>
    </div>
</div>
