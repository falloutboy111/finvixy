<?php

use App\Models\Plan;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Billing settings')] #[Layout('layouts.app.sidebar')] class extends Component {
    public bool $showCancelModal = false;

    public function subscribe(string $planCode): void
    {
        $plan = Plan::query()->where('code', $planCode)->firstOrFail();

        if (! $plan->paddle_price_id) {
            return;
        }

        $user = Auth::user();

        $checkout = $user->subscribe($plan->paddle_price_id, 'default')
            ->returnTo(route('billing'));

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

    public function swap(string $planCode): void
    {
        $plan = Plan::query()->where('code', $planCode)->firstOrFail();

        if (! $plan->paddle_price_id) {
            return;
        }

        $user = Auth::user();
        $subscription = $user->subscription();

        if (! $subscription || $subscription->canceled()) {
            $this->subscribe($planCode);

            return;
        }

        $subscription->swap($plan->paddle_price_id);
        $user->update(['plan_id' => $plan->id]);

        $this->dispatch('plan-updated');
    }

    public function cancelSubscription(): void
    {
        $user = Auth::user();
        $subscription = $user->subscription();

        if ($subscription && ! $subscription->canceled()) {
            $subscription->cancel();
        }

        $this->showCancelModal = false;
        $this->dispatch('subscription-cancelled');
    }

    public function resumeSubscription(): void
    {
        $user = Auth::user();
        $subscription = $user->subscription();

        if ($subscription && $subscription->onGracePeriod()) {
            $subscription->resume();
        }
    }

    public function with(): array
    {
        $user = Auth::user();
        $currentPlan = $user->plan;
        $subscription = $user->subscription();
        $plans = Plan::query()->where('is_active', true)->orderBy('price_monthly')->get();
        $transactions = $user->transactions()->latest()->take(10)->get();

        $nextPayment = null;
        $lastPayment = null;

        if ($subscription && $subscription->recurring()) {
            $nextPayment = $subscription->nextPayment();
            $lastPayment = $subscription->lastPayment();
        }

        return [
            'currentPlan' => $currentPlan,
            'subscription' => $subscription,
            'plans' => $plans,
            'transactions' => $transactions,
            'nextPayment' => $nextPayment,
            'lastPayment' => $lastPayment,
            'isSubscribed' => $user->subscribed(),
            'onGracePeriod' => $subscription?->onGracePeriod() ?? false,
        ];
    }
}; ?>

<section
    class="w-full"
    x-data
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
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Billing')" :subheading="__('Manage your subscription and billing details')">
        <div class="space-y-8 max-w-none w-full">

            {{-- Current Plan --}}
            <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <flux:heading size="lg">Current Plan</flux:heading>
                        <div class="mt-2 flex items-center gap-3">
                            <span class="text-2xl font-bold text-white">{{ $currentPlan?->name ?? 'Free' }}</span>
                            @if ($isSubscribed && ! $onGracePeriod)
                                <flux:badge color="emerald" size="sm">Active</flux:badge>
                            @elseif ($onGracePeriod)
                                <flux:badge color="yellow" size="sm">Cancelling</flux:badge>
                            @else
                                <flux:badge size="sm">Free</flux:badge>
                            @endif
                        </div>

                        @if ($currentPlan)
                            <flux:text class="mt-1">
                                {{ $currentPlan->hasReceiptLimit() ? $currentPlan->receipts_limit . ' receipts/month' : 'Unlimited receipts' }}
                            </flux:text>
                        @endif
                    </div>

                    <div class="text-right">
                        @if ($currentPlan && $currentPlan->price_monthly > 0)
                            <p class="text-2xl font-bold text-white">R{{ number_format($currentPlan->price_monthly, 0) }}</p>
                            <flux:text size="sm">/month</flux:text>
                        @else
                            <p class="text-2xl font-bold text-white">R0</p>
                            <flux:text size="sm">forever free</flux:text>
                        @endif
                    </div>
                </div>

                @if ($nextPayment)
                    <div class="mt-4 pt-4 border-t border-zinc-800">
                        <flux:text size="sm">
                            Next payment: <strong class="text-white">{{ $nextPayment->amount() }}</strong>
                            on <strong class="text-white">{{ $nextPayment->date()->format('j M Y') }}</strong>
                        </flux:text>
                    </div>
                @endif

                @if ($onGracePeriod)
                    <div class="mt-4 pt-4 border-t border-zinc-800">
                        <flux:callout variant="warning" icon="exclamation-triangle">
                            <flux:callout.heading>Subscription ending</flux:callout.heading>
                            <flux:callout.text>
                                Your plan will revert to Free at the end of the current billing period.
                            </flux:callout.text>
                            <x-slot:actions>
                                <flux:button variant="filled" size="sm" wire:click="resumeSubscription" wire:loading.attr="disabled">
                                    Resume subscription
                                </flux:button>
                            </x-slot:actions>
                        </flux:callout>
                    </div>
                @endif
            </div>

            {{-- Available Plans --}}
            <div>
                <flux:heading size="lg" class="mb-4">{{ $isSubscribed ? 'Change Plan' : 'Upgrade Your Plan' }}</flux:heading>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($plans as $plan)
                        @if ($plan->code === 'enterprise')
                            @continue
                        @endif
                        <div class="rounded-xl border {{ $currentPlan?->id === $plan->id ? 'border-emerald-500/30 bg-emerald-500/5' : 'border-zinc-800 bg-zinc-900/50' }} p-5 transition">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <h3 class="font-semibold text-white">{{ $plan->name }}</h3>
                                    <flux:text size="sm">{{ $plan->description }}</flux:text>
                                </div>
                                <div class="text-right">
                                    @if ($plan->price_monthly > 0)
                                        <p class="font-bold text-white">R{{ number_format($plan->price_monthly, 0) }}</p>
                                        <flux:text size="xs">/mo</flux:text>
                                    @else
                                        <p class="font-bold text-white">Free</p>
                                    @endif
                                </div>
                            </div>
                            <flux:text size="sm" class="mb-4">
                                {{ $plan->hasReceiptLimit() ? $plan->receipts_limit . ' receipts/month' : ($plan->is_unlimited ? 'Unlimited receipts' : '10 receipts/month') }}
                            </flux:text>

                            @if ($currentPlan?->id === $plan->id)
                                <flux:button variant="filled" size="sm" disabled class="w-full">
                                    Current plan
                                </flux:button>
                            @elseif ($plan->code === 'free')
                                {{-- Can't "subscribe" to free — they cancel --}}
                            @elseif ($isSubscribed && ! $onGracePeriod)
                                <flux:button variant="primary" size="sm" class="w-full" wire:click="swap('{{ $plan->code }}')" wire:loading.attr="disabled">
                                    Switch to {{ $plan->name }}
                                </flux:button>
                            @elseif ($plan->paddle_price_id)
                                <flux:button variant="primary" size="sm" class="w-full" wire:click="subscribe('{{ $plan->code }}')" wire:loading.attr="disabled">
                                    Subscribe to {{ $plan->name }}
                                </flux:button>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Enterprise CTA --}}
                <div class="mt-4 rounded-xl border border-zinc-800 bg-zinc-900/50 p-5 text-center">
                    <flux:heading>Need more?</flux:heading>
                    <flux:text size="sm" class="mt-1">Contact us for a custom Enterprise plan with unlimited receipts and dedicated support.</flux:text>
                    <a href="mailto:hello@enclivix.com" class="mt-3 inline-flex items-center gap-2 rounded-lg border border-zinc-700 px-4 py-2 text-sm font-medium text-zinc-300 transition hover:border-emerald-500/30 hover:text-emerald-400">
                        Contact Sales
                    </a>
                </div>
            </div>

            {{-- Cancel Subscription --}}
            @if ($isSubscribed && ! $onGracePeriod)
                <div class="rounded-xl border border-red-500/15 bg-red-500/5 p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <flux:heading>Cancel Subscription</flux:heading>
                            <flux:text size="sm" class="mt-1">
                                Your plan will remain active until the end of the current billing period.
                            </flux:text>
                        </div>
                        <flux:button variant="danger" size="sm" wire:click="$set('showCancelModal', true)">
                            Cancel plan
                        </flux:button>
                    </div>
                </div>

                <flux:modal wire:model="showCancelModal">
                    <div class="space-y-4">
                        <flux:heading size="lg">Cancel your subscription?</flux:heading>
                        <flux:text>
                            Your subscription will remain active until the end of the current billing period. After that, your account will revert to the Free plan (10 receipts/month).
                        </flux:text>
                        <div class="flex justify-end gap-3 pt-2">
                            <flux:button variant="ghost" wire:click="$set('showCancelModal', false)">Keep subscription</flux:button>
                            <flux:button variant="danger" wire:click="cancelSubscription" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="cancelSubscription">Yes, cancel</span>
                                <span wire:loading wire:target="cancelSubscription">Cancelling...</span>
                            </flux:button>
                        </div>
                    </div>
                </flux:modal>
            @endif

            {{-- Transaction History --}}
            @if ($transactions->isNotEmpty())
                <div>
                    <flux:heading size="lg" class="mb-4">Transaction History</flux:heading>
                    <div class="rounded-xl border border-zinc-800 overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-900/80">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase">Amount</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase">Tax</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 uppercase">Invoice</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-800">
                                @foreach ($transactions as $transaction)
                                    <tr class="hover:bg-zinc-900/50">
                                        <td class="px-4 py-3 text-zinc-300">{{ $transaction->billed_at->format('j M Y') }}</td>
                                        <td class="px-4 py-3 text-white font-medium">{{ $transaction->total() }}</td>
                                        <td class="px-4 py-3 text-zinc-400">{{ $transaction->tax() }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('billing.invoice', $transaction->id) }}" target="_blank" class="text-emerald-400 hover:underline text-xs">
                                                Download
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    </x-pages::settings.layout>
</section>
