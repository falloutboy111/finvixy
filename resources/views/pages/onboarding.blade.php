<?php

use App\Models\Plan;
use App\Services\GoogleDriveService;
use App\Services\OrgStorageService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Welcome to Finvixy')] #[Layout('layouts.auth.simple')] class extends Component {
    public int $step = 1;

    public function mount(): void
    {
        $user = Auth::user();

        // If already completed, go to dashboard
        if ($user->onboarding_completed_at) {
            $this->redirectRoute('dashboard');

            return;
        }

        // Resume from query string (e.g. after Paddle checkout or OAuth callback)
        $requestedStep = (int) request()->query('step', 0);
        if ($requestedStep >= 2 && $requestedStep <= 3) {
            $this->step = $requestedStep;

            return;
        }

        // Determine which step to resume from
        if ($user->plan && $user->plan->code !== 'free') {
            $this->step = 3;
        }
    }

    /**
     * Move to the next step.
     */
    public function nextStep(): void
    {
        $this->step = min($this->step + 1, 3);
    }

    /**
     * Open Paddle checkout for a plan.
     */
    public function subscribe(string $planCode): void
    {
        $plan = Plan::query()->where('code', $planCode)->firstOrFail();

        if (! $plan->paddle_price_id) {
            return;
        }

        $user = Auth::user();

        $checkout = $user->subscribe($plan->paddle_price_id, 'default')
            ->returnTo(route('onboarding').'?step=3');

        $items = $checkout->getItems();
        $customer = $checkout->getCustomer();
        $custom = $checkout->getCustomData();
        $returnUrl = $checkout->getReturnUrl();

        $this->dispatch('open-paddle-checkout', [
            'items' => $items,
            'customerId' => $customer?->paddle_id,
            'customData' => $custom,
            'successUrl' => $returnUrl,
        ]);
    }

    /**
     * Skip plan selection (stay on free).
     */
    public function skipPlan(): void
    {
        $this->step = 3;
    }

    /**
     * Start Google Drive OAuth flow.
     */
    public function connectDrive(): void
    {
        $user = Auth::user();

        $state = [
            'user_id' => $user->id,
            'timestamp' => time(),
            'random' => bin2hex(random_bytes(16)),
            'redirect' => 'onboarding',
        ];

        $authUrl = GoogleDriveService::getAuthUrl($state);

        $this->redirect($authUrl);
    }

    /**
     * Choose built-in Finvixy Storage (S3).
     */
    public function useFinvixyStorage(): void
    {
        $user = Auth::user();
        $organisation = $user->organisation;

        $organisation->update(['storage_type' => 's3']);

        // Initialise the org folder on S3
        $service = new OrgStorageService($organisation);
        $service->initialise();

        $this->completeOnboarding();
    }

    /**
     * Complete the onboarding with drive storage.
     */
    public function useDriveStorage(): void
    {
        $user = Auth::user();
        $user->organisation->update(['storage_type' => 'drive']);

        $this->completeOnboarding();
    }

    /**
     * Mark onboarding as complete and redirect to dashboard.
     */
    public function completeOnboarding(): void
    {
        $user = Auth::user();
        $user->update(['onboarding_completed_at' => now()]);

        $this->redirectRoute('dashboard');
    }

    public function with(): array
    {
        $user = Auth::user();
        $plans = Plan::query()->where('is_active', true)->where('code', '!=', 'free')->orderBy('price_monthly')->get();
        $hasDriveConnected = $user->connectedAccounts()->where('provider', 'google_drive')->where('is_active', true)->exists();

        return [
            'user' => $user,
            'organisation' => $user->organisation,
            'currentPlan' => $user->plan,
            'plans' => $plans,
            'hasDriveConnected' => $hasDriveConnected,
        ];
    }
}; ?>

<div
    class="w-full max-w-2xl mx-auto"
    x-data="{ step: @entangle('step') }"
    x-on:open-paddle-checkout.window="
        if (typeof Paddle !== 'undefined') {
            let opts = { items: $event.detail[0].items };
            if ($event.detail[0].customerId) opts.customer = { id: $event.detail[0].customerId };
            if ($event.detail[0].customData) opts.customData = $event.detail[0].customData;
            if ($event.detail[0].successUrl) opts.settings = { successUrl: $event.detail[0].successUrl };
            Paddle.Checkout.open(opts);
        }
    "
>
    {{-- Progress Steps --}}
    <div class="flex items-center justify-center gap-2 mb-8">
        @for ($i = 1; $i <= 3; $i++)
            <div class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold transition {{ $step >= $i ? 'bg-emerald-500 text-white' : 'bg-zinc-800 text-zinc-500' }}">
                    @if ($step > $i)
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    @else
                        {{ $i }}
                    @endif
                </div>
                @if ($i < 3)
                    <div class="h-0.5 w-10 transition {{ $step > $i ? 'bg-emerald-500' : 'bg-zinc-800' }}"></div>
                @endif
            </div>
        @endfor
    </div>

    {{-- Step 1: Account Created --}}
    @if ($step === 1)
        <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-8 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500/10">
                <svg class="h-8 w-8 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>

            <flux:heading size="xl">Welcome, {{ $user->name }}!</flux:heading>
            <flux:text class="mt-2 max-w-md mx-auto">
                Your account and organisation <strong class="text-white">{{ $organisation->name }}</strong> have been created.
                Let's get you set up in just a couple of steps.
            </flux:text>

            <div class="mt-8 rounded-lg border border-zinc-800 bg-zinc-900 p-4 text-left">
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-500/10">
                            <svg class="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-white">{{ $user->name }}</p>
                            <p class="text-xs text-zinc-500">{{ $user->email }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-500/10">
                            <svg class="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-white">{{ $organisation->name }}</p>
                            <p class="text-xs text-zinc-500">Organisation</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-500/10">
                            <svg class="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-white">Free Plan</p>
                            <p class="text-xs text-zinc-500">10 receipts/month</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8">
                <flux:button variant="primary" class="w-full" wire:click="nextStep">
                    Continue — Choose a plan
                </flux:button>
            </div>
        </div>
    @endif

    {{-- Step 2: Choose a Plan --}}
    @if ($step === 2)
        <div class="space-y-4">
            <div class="text-center mb-6">
                <flux:heading size="xl">Choose your plan</flux:heading>
                <flux:text class="mt-2">Pick the plan that fits your needs. You can always change later.</flux:text>
            </div>

            <div class="grid gap-4">
                @foreach ($plans as $plan)
                    @if ($plan->code === 'enterprise')
                        @continue
                    @endif
                    <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-5 transition hover:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-semibold text-white">{{ $plan->name }}</h3>
                                <flux:text size="sm">
                                    {{ $plan->hasReceiptLimit() ? $plan->receipts_limit.' receipts/month' : 'Unlimited receipts' }}
                                </flux:text>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="text-right">
                                    <p class="font-bold text-white">R{{ number_format($plan->price_monthly, 0) }}</p>
                                    <flux:text size="xs">/month</flux:text>
                                </div>
                                @if ($plan->paddle_price_id)
                                    <flux:button variant="primary" size="sm" wire:click="subscribe('{{ $plan->code }}')" wire:loading.attr="disabled">
                                        Select
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="text-center pt-2">
                <flux:button variant="ghost" size="sm" wire:click="skipPlan">
                    Stay on Free plan for now
                </flux:button>
            </div>
        </div>
    @endif

    {{-- Step 3: Connect Storage --}}
    @if ($step === 3)
        <div class="space-y-4">
            <div class="text-center mb-6">
                <flux:heading size="xl">Where should we store your receipts?</flux:heading>
                <flux:text class="mt-2">Choose Google Drive to keep receipts in your own cloud, or use our built-in storage.</flux:text>
            </div>

            {{-- Google Drive Option --}}
            <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-6 transition hover:border-zinc-700">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-zinc-800">
                        <svg class="h-7 w-7" viewBox="0 0 87.3 78" xmlns="http://www.w3.org/2000/svg">
                            <path d="m6.6 66.85 3.85 6.65c.8 1.4 1.95 2.5 3.3 3.3l13.75-23.8h-27.5c0 1.55.4 3.1 1.2 4.5z" fill="#0066da"/>
                            <path d="m43.65 25-13.75-23.8c-1.35.8-2.5 1.9-3.3 3.3l-20.4 35.3c-.8 1.4-1.2 2.95-1.2 4.5h27.5z" fill="#00ac47"/>
                            <path d="m73.55 76.8c1.35-.8 2.5-1.9 3.3-3.3l1.6-2.75 7.65-13.25c.8-1.4 1.2-2.95 1.2-4.5h-27.5l5.85 13.35z" fill="#ea4335"/>
                            <path d="m43.65 25 13.75-23.8c-1.35-.8-2.9-1.2-4.5-1.2h-18.5c-1.6 0-3.15.45-4.5 1.2z" fill="#00832d"/>
                            <path d="m59.8 53h-32.3l-13.75 23.8c1.35.8 2.9 1.2 4.5 1.2h50.8c1.6 0 3.15-.45 4.5-1.2z" fill="#2684fc"/>
                            <path d="m73.4 26.5-12.7-22c-.8-1.4-1.95-2.5-3.3-3.3l-13.75 23.8 16.15 28h27.45c0-1.55-.4-3.1-1.2-4.5z" fill="#ffba00"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-white">Google Drive</h3>
                        <flux:text size="sm" class="mt-1">
                            Store receipts in your own Google Drive. We'll create an organised folder structure automatically.
                        </flux:text>

                        @if ($hasDriveConnected)
                            <div class="mt-3 flex items-center gap-2">
                                <flux:badge color="emerald" size="sm">Connected</flux:badge>
                                <flux:button variant="primary" size="sm" wire:click="useDriveStorage">
                                    Use Google Drive
                                </flux:button>
                            </div>
                        @else
                            <div class="mt-3">
                                <flux:button variant="primary" size="sm" wire:click="connectDrive" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="connectDrive">Connect Google Drive</span>
                                    <span wire:loading wire:target="connectDrive">Connecting...</span>
                                </flux:button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Finvixy Storage Option --}}
            <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-6 transition hover:border-zinc-700">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-zinc-800">
                        <svg class="h-7 w-7 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-white">Finvixy Storage</h3>
                        <flux:text size="sm" class="mt-1">
                            We'll securely store your receipts in your own private folder. Up to 1 GB included.
                            Files are encrypted and accessible on demand.
                        </flux:text>
                        <div class="mt-3">
                            <flux:button variant="filled" size="sm" wire:click="useFinvixyStorage" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="useFinvixyStorage">Use Finvixy Storage</span>
                                <span wire:loading wire:target="useFinvixyStorage">Setting up...</span>
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>

            <flux:text size="xs" class="text-center text-zinc-500 pt-2">
                You can switch between storage options later in Settings → Connected Accounts.
            </flux:text>
        </div>
    @endif
</div>
