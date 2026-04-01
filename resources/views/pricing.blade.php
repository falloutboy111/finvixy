<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Pricing — Finvixy</title>
        <link rel="icon" href="/logoFinvixy.png" type="image/png">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-zinc-950 text-gray-200 antialiased min-h-screen">

        {{-- Navigation --}}
        <header class="fixed top-0 left-0 right-0 z-50 border-b border-emerald-500/10 bg-zinc-950/80 backdrop-blur-xl">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <a href="/" class="flex items-center gap-2.5">
                        <x-app-logo-icon class="h-16 w-auto" />
                        <x-finvixy-wordmark variant="dark" size="lg" />
                    </a>
                    <nav class="flex items-center gap-4">
                        <a href="{{ route('pricing') }}" class="text-sm font-medium text-emerald-400">Pricing</a>
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-4 py-2 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-400 glow-sm">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-medium text-gray-400 transition hover:text-emerald-400">Log in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-4 py-2 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-400 glow-sm">
                                    Get Started
                                </a>
                            @endif
                        @endauth
                    </nav>
                </div>
            </div>
        </header>

        <main class="pt-32 pb-20 lg:pt-44 lg:pb-32">

            {{-- Page Header --}}
            <div class="relative overflow-hidden">
                <div class="absolute inset-0 pointer-events-none">
                    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-emerald-500/5 rounded-full blur-3xl"></div>
                </div>
                <div class="relative mx-auto max-w-3xl px-6 lg:px-8 text-center mb-16 lg:mb-20">
                    <h1 class="text-4xl font-bold text-white sm:text-5xl">Simple, transparent pricing</h1>
                    <p class="mt-4 text-lg text-gray-400">Start free. Upgrade when you need more. No surprise fees.</p>
                </div>
            </div>

            {{-- Pricing Cards --}}
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">

                    {{-- Free --}}
                    <div class="glow-card rounded-2xl p-7 flex flex-col">
                        <div>
                            <h2 class="text-xs font-semibold text-emerald-400 uppercase tracking-widest">Free</h2>
                            <div class="mt-4 flex items-baseline gap-1">
                                <span class="text-4xl font-bold text-white">R0</span>
                                <span class="text-sm text-zinc-500">/mo</span>
                            </div>
                            <p class="mt-2 text-sm text-zinc-400">Perfect for trying out Finvixy.</p>
                        </div>
                        <ul class="mt-7 space-y-3 text-sm text-zinc-400 flex-1">
                            <li class="flex items-start gap-2.5">
                                <svg class="size-4 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                10 receipts per month
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="size-4 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                WhatsApp &amp; web upload
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="size-4 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                Basic spending reports
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="size-4 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                AI-powered extraction
                            </li>
                        </ul>
                        <a href="{{ Route::has('register') ? route('register') : '#' }}" class="mt-8 block text-center rounded-xl border border-emerald-500/20 px-4 py-2.5 text-sm font-medium text-emerald-400 transition hover:bg-emerald-500/5 hover:border-emerald-500/40">
                            Get Started Free
                        </a>
                    </div>

                    {{-- Starter --}}
                    <div class="glow-card rounded-2xl p-7 flex flex-col">
                        <div>
                            <h2 class="text-xs font-semibold text-emerald-400 uppercase tracking-widest">Starter</h2>
                            <div class="mt-4 flex items-baseline gap-1">
                                <span class="text-4xl font-bold text-white">R99</span>
                                <span class="text-sm text-zinc-500">/mo</span>
                            </div>
                            <p class="mt-2 text-sm text-zinc-400">For individuals and freelancers.</p>
                        </div>
                        <ul class="mt-7 space-y-3 text-sm text-zinc-400 flex-1">
                            <li class="flex items-start gap-2.5">
                                <svg class="size-4 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                50 receipts per month
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="size-4 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                Google Drive sync
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="size-4 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                Full spending reports
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="size-4 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                All free features
                            </li>
                        </ul>
                        <a href="{{ Route::has('register') ? route('register') : '#' }}" class="mt-8 block text-center rounded-xl border border-emerald-500/20 px-4 py-2.5 text-sm font-medium text-emerald-400 transition hover:bg-emerald-500/5 hover:border-emerald-500/40">
                            Get Started
                        </a>
                    </div>

                    {{-- Professional (Featured) --}}
                    <div class="relative rounded-2xl p-7 flex flex-col border-2 border-emerald-500/40 bg-gradient-to-b from-emerald-500/10 to-zinc-950 glow-md">
                        <div>
                            <h2 class="text-xs font-semibold text-emerald-400 uppercase tracking-widest">Professional</h2>
                            <div class="mt-4 flex items-baseline gap-1">
                                <span class="text-4xl font-bold text-white">R189</span>
                                <span class="text-sm text-zinc-500">/mo</span>
                            </div>
                            <p class="mt-2 text-sm text-zinc-400">For growing businesses.</p>
                        </div>
                        <ul class="mt-7 space-y-3 text-sm text-zinc-400 flex-1">
                            <li class="flex items-start gap-2.5">
                                <svg class="size-4 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                150 receipts per month
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="size-4 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                Priority processing
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="size-4 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                Spending insights &amp; analytics
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="size-4 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                Accountant sharing
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="size-4 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                All starter features
                            </li>
                        </ul>
                        <a href="{{ Route::has('register') ? route('register') : '#' }}" class="mt-8 block text-center rounded-xl bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-400 glow-sm">
                            Get Started
                        </a>
                    </div>

                    {{-- Business --}}
                    <div class="glow-card rounded-2xl p-7 flex flex-col">
                        <div>
                            <h2 class="text-xs font-semibold text-emerald-400 uppercase tracking-widest">Business</h2>
                            <div class="mt-4 flex items-baseline gap-1">
                                <span class="text-4xl font-bold text-white">R349</span>
                                <span class="text-sm text-zinc-500">/mo</span>
                            </div>
                            <p class="mt-2 text-sm text-zinc-400">For teams and larger organisations.</p>
                        </div>
                        <ul class="mt-7 space-y-3 text-sm text-zinc-400 flex-1">
                            <li class="flex items-start gap-2.5">
                                <svg class="size-4 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                500 receipts per month
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="size-4 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                Team member access
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="size-4 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                Advanced analytics dashboard
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="size-4 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                All professional features
                            </li>
                        </ul>
                        <a href="{{ Route::has('register') ? route('register') : '#' }}" class="mt-8 block text-center rounded-xl border border-emerald-500/20 px-4 py-2.5 text-sm font-medium text-emerald-400 transition hover:bg-emerald-500/5 hover:border-emerald-500/40">
                            Get Started
                        </a>
                    </div>

                </div>

                {{-- Custom / Enterprise Contact --}}
                <div class="mt-12 rounded-2xl border border-zinc-800 bg-zinc-900/40 p-8 lg:p-10">
                    <div class="flex flex-col items-start gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div class="inline-flex items-center gap-2 rounded-full border border-zinc-700 bg-zinc-800/60 px-3 py-1 text-xs font-medium text-zinc-400 mb-3">
                                Custom
                            </div>
                            <h2 class="text-xl font-semibold text-white">Need something larger?</h2>
                            <p class="mt-2 text-sm text-zinc-400 max-w-xl">
                                Looking for higher receipt volumes, custom integrations, white-label options, or a tailored contract? Reach out — we'll put together a plan that fits.
                            </p>
                            <ul class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm text-zinc-400">
                                <li class="flex items-center gap-2">
                                    <svg class="size-4 text-zinc-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    Unlimited receipts
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="size-4 text-zinc-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    Dedicated support
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="size-4 text-zinc-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    Custom integrations
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="size-4 text-zinc-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    SLA &amp; invoiced billing
                                </li>
                            </ul>
                        </div>
                        <a href="mailto:hello@enclivix.com?subject=Finvixy%20Custom%20Plan%20Enquiry" class="shrink-0 inline-flex items-center gap-2 rounded-xl border border-zinc-700 bg-zinc-800 px-6 py-3 text-sm font-medium text-white transition hover:border-emerald-500/30 hover:bg-zinc-700">
                            Contact Us
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                        </a>
                    </div>
                </div>

                {{-- FAQ / reassurance strip --}}
                <div class="mt-12 grid gap-6 sm:grid-cols-3 text-center">
                    <div class="rounded-xl bg-zinc-900/30 border border-zinc-800/60 px-6 py-5">
                        <p class="text-sm font-medium text-white">No credit card required</p>
                        <p class="mt-1 text-xs text-zinc-500">Start free and upgrade only when you're ready.</p>
                    </div>
                    <div class="rounded-xl bg-zinc-900/30 border border-zinc-800/60 px-6 py-5">
                        <p class="text-sm font-medium text-white">Cancel anytime</p>
                        <p class="mt-1 text-xs text-zinc-500">No long-term contracts. Stop or switch plans whenever.</p>
                    </div>
                    <div class="rounded-xl bg-zinc-900/30 border border-zinc-800/60 px-6 py-5">
                        <p class="text-sm font-medium text-white">Pricing in ZAR</p>
                        <p class="mt-1 text-xs text-zinc-500">All prices are in South African Rand, VAT exclusive.</p>
                    </div>
                </div>
            </div>

        </main>

        {{-- Footer --}}
        <footer class="border-t border-emerald-500/10 py-8 mt-12">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="flex flex-col items-center gap-6 md:flex-row md:justify-between">
                    <div class="flex items-center gap-2">
                        <x-app-logo-icon class="h-7 w-auto" />
                        <x-finvixy-wordmark variant="dark" size="sm" />
                    </div>
                    <nav class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-xs text-zinc-500">
                        <a href="{{ route('pricing') }}" class="hover:text-emerald-400 transition font-medium text-emerald-400">Pricing</a>
                        <span class="text-zinc-800">·</span>
                        <a href="{{ route('terms') }}" class="hover:text-emerald-400 transition">Terms of Service</a>
                        <span class="text-zinc-800">·</span>
                        <a href="{{ route('privacy') }}" class="hover:text-emerald-400 transition">Privacy Policy</a>
                        <span class="text-zinc-800">·</span>
                        <a href="{{ route('refund') }}" class="hover:text-emerald-400 transition">Refund Policy</a>
                    </nav>
                    <p class="text-xs text-zinc-500">&copy; {{ date('Y') }} Finvixy by Enclivix.</p>
                </div>
            </div>
        </footer>

    </body>
</html>
