<?php

use App\Services\EmailOtpService;
use App\Services\TrustedDeviceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Email verification')] #[Layout('layouts.auth.simple')] class extends Component {
    public string $code = '';

    public bool $codeSent = false;

    public bool $rememberDevice = false;

    public function mount(EmailOtpService $emailOtpService): void
    {
        $user = Auth::user();

        if (! $user || ! $emailOtpService->isRequiredForUser($user)) {
            $this->redirect(route('dashboard'));

            return;
        }

        if (session()->get('email_otp_verified', false)) {
            $this->redirect(route('dashboard'));

            return;
        }

        // Send OTP on page load
        $emailOtpService->send($user);
        $this->codeSent = true;
    }

    public function verify(EmailOtpService $emailOtpService, TrustedDeviceService $trustedDeviceService): void
    {
        $this->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = Auth::user();

        if ($emailOtpService->verify($user, $this->code)) {
            session()->put('email_otp_verified', true);

            // Issue persistent trusted device cookie
            $cookie = $trustedDeviceService->issueCookie($user, $this->rememberDevice);
            Cookie::queue($cookie);

            $this->redirect(route('dashboard'));

            return;
        }

        $this->addError('code', __('The verification code is invalid or has expired.'));
        $this->code = '';
    }

    public function resend(EmailOtpService $emailOtpService): void
    {
        $emailOtpService->send(Auth::user());
        $this->codeSent = true;
        session()->flash('status', __('A new verification code has been sent to your email.'));
    }
} ?>

<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('Check your email')"
        :description="__('We sent a 6-digit verification code to your email address.')"
    />

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle">
            {{ session('status') }}
        </flux:callout>
    @endif

    <form wire:submit="verify" class="space-y-5 text-center">
        <div class="flex items-center justify-center my-5">
            <flux:otp
                wire:model="code"
                length="6"
                label="Verification code"
                label:sr-only
                class="mx-auto"
            />
        </div>

        @error('code')
            <flux:text class="text-red-500">
                {{ $message }}
            </flux:text>
        @enderror

        <div class="flex items-center justify-center gap-2 text-sm text-left">
            <flux:checkbox wire:model="rememberDevice" id="remember_device" />
            <label for="remember_device" class="text-zinc-400 cursor-pointer select-none">
                {{ __('Remember this device for 30 days') }}
            </label>
        </div>

        <flux:button
            variant="primary"
            type="submit"
            class="w-full"
        >
            {{ __('Verify') }}
        </flux:button>
    </form>

    <div class="text-sm text-center">
        <span class="opacity-50">{{ __("Didn't receive the code?") }}</span>
        <button wire:click="resend" class="font-medium underline cursor-pointer text-emerald-400 hover:text-emerald-300">
            {{ __('Resend') }}
        </button>
    </div>
</div>
