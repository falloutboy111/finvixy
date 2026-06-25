<x-layouts::marketing title="Pricing — Finvixy">

    <main class="pt-32 pb-20 lg:pt-44 lg:pb-32">

        {{-- Page Header --}}
        <div class="relative overflow-hidden">
            <div class="absolute inset-0 pointer-events-none">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-emerald-500/5 rounded-full blur-3xl"></div>
            </div>
            <div class="relative mx-auto max-w-3xl px-6 lg:px-8 text-center mb-16 lg:mb-20">
                <flux:heading level="1" class="text-4xl font-bold text-white sm:text-5xl">Simple, transparent pricing</flux:heading>
                <flux:text class="mt-4 text-lg text-gray-400">Start free. Upgrade when you need more. No surprise fees.</flux:text>
            </div>
        </div>

        {{-- Pricing Cards --}}
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    [
                        'name' => 'Free',
                        'price' => 'R0',
                        'tagline' => 'Perfect for trying out Finvixy.',
                        'features' => ['10 receipts per month', 'WhatsApp & web upload', 'AI-powered extraction'],
                        'cta' => 'Get Started Free',
                        'featured' => false,
                    ],
                    [
                        'name' => 'Starter',
                        'price' => 'R99',
                        'tagline' => 'For individuals and freelancers.',
                        'features' => ['50 receipts per month', 'All free features'],
                        'cta' => 'Get Started',
                        'featured' => false,
                    ],
                    [
                        'name' => 'Professional',
                        'price' => 'R189',
                        'tagline' => 'For growing businesses.',
                        'features' => ['150 receipts per month', 'Priority processing', 'Accountant sharing', 'All starter features'],
                        'cta' => 'Get Started',
                        'featured' => true,
                    ],
                    [
                        'name' => 'Business',
                        'price' => 'R349',
                        'tagline' => 'For teams and larger organisations.',
                        'features' => ['500 receipts per month', 'All professional features'],
                        'cta' => 'Get Started',
                        'featured' => false,
                    ],
                ] as $plan)
                    <div @class([
                        'rounded-2xl p-7 flex flex-col',
                        'glow-card' => ! $plan['featured'],
                        'relative border-2 border-emerald-500/40 bg-gradient-to-b from-emerald-500/10 to-zinc-950 glow-md' => $plan['featured'],
                    ])>
                        <div>
                            <flux:heading level="2" class="text-xs! font-semibold! text-emerald-400 uppercase tracking-widest">{{ $plan['name'] }}</flux:heading>
                            <div class="mt-4 flex items-baseline gap-1">
                                <span class="text-4xl font-bold text-white">{{ $plan['price'] }}</span>
                                <span class="text-sm text-zinc-500">/mo</span>
                            </div>
                            <flux:text class="mt-2 text-sm text-zinc-400">{{ $plan['tagline'] }}</flux:text>
                        </div>
                        <ul class="mt-7 space-y-3 text-sm text-zinc-400 flex-1">
                            @foreach ($plan['features'] as $feature)
                                <x-marketing.check-item class="gap-2.5" icon-class="size-4 text-emerald-500 shrink-0 mt-0.5">
                                    {{ $feature }}
                                </x-marketing.check-item>
                            @endforeach
                        </ul>
                        @if ($plan['featured'])
                            <flux:button href="{{ Route::has('register') ? route('register') : '#' }}" variant="primary" class="mt-8 w-full">
                                {{ $plan['cta'] }}
                            </flux:button>
                        @else
                            <flux:button href="{{ Route::has('register') ? route('register') : '#' }}" variant="outline" class="mt-8 w-full bg-transparent! border-emerald-500/20! text-emerald-400! hover:border-emerald-500/40! hover:bg-emerald-500/5!">
                                {{ $plan['cta'] }}
                            </flux:button>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Custom / Enterprise Contact --}}
            <div class="mt-12 rounded-2xl border border-zinc-800 bg-zinc-900/40 p-8 lg:p-10">
                <div class="flex flex-col items-start gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <flux:badge color="zinc" size="sm" class="mb-3">Custom</flux:badge>
                        <flux:heading level="2" class="text-xl! font-semibold! text-white">Need something larger?</flux:heading>
                        <flux:text class="mt-2 text-sm text-zinc-400 max-w-xl">
                            Looking for higher receipt volumes, custom integrations, white-label options, or a tailored contract? Reach out — we'll put together a plan that fits.
                        </flux:text>
                        <ul class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm text-zinc-400">
                            @foreach (['Unlimited receipts', 'Dedicated support', 'Custom integrations', 'SLA & invoiced billing'] as $perk)
                                <li class="flex items-center gap-2">
                                    <flux:icon name="check" class="size-4 text-zinc-500 shrink-0 [&>path]:stroke-2" />
                                    {{ $perk }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <flux:button href="mailto:hello@enclivix.com?subject=Finvixy%20Custom%20Plan%20Enquiry" variant="filled" icon:trailing="envelope" class="shrink-0 h-12 px-6">
                        Contact Us
                    </flux:button>
                </div>
            </div>

            {{-- FAQ / reassurance strip --}}
            <div class="mt-12 grid gap-6 sm:grid-cols-3 text-center">
                @foreach ([
                    ['No credit card required', "Start free and upgrade only when you're ready."],
                    ['Cancel anytime', 'No long-term contracts. Stop or switch plans whenever.'],
                    ['Pricing in ZAR', 'All prices are in South African Rand, VAT exclusive.'],
                ] as [$title, $description])
                    <div class="rounded-xl bg-zinc-900/30 border border-zinc-800/60 px-6 py-5">
                        <flux:text class="text-sm font-medium text-white">{{ $title }}</flux:text>
                        <flux:text class="mt-1 text-xs text-zinc-500">{{ $description }}</flux:text>
                    </div>
                @endforeach
            </div>
        </div>

    </main>

</x-layouts::marketing>
