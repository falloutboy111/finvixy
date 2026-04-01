<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Refund Policy — Finvixy</title>
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
                        <a href="{{ route('pricing') }}" class="text-sm font-medium text-gray-400 transition hover:text-emerald-400">Pricing</a>
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-4 py-2 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-400">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-medium text-gray-400 transition hover:text-emerald-400">Log in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-4 py-2 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-400">Get Started</a>
                            @endif
                        @endauth
                    </nav>
                </div>
            </div>
        </header>

        <main class="pt-28 pb-20 lg:pt-36 lg:pb-28">
            {{-- Page Header --}}
            <div class="mx-auto max-w-7xl px-6 lg:px-8 mb-12">
                <div class="border-b border-zinc-800/60 pb-8">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h1 class="text-4xl font-bold text-white">Refund Policy</h1>
                            <p class="mt-2 text-sm text-zinc-500">Last updated: {{ now()->format('j F Y') }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('terms') }}" class="text-xs text-zinc-500 hover:text-emerald-400 transition">Terms of Service</a>
                            <span class="text-zinc-800">·</span>
                            <a href="{{ route('privacy') }}" class="text-xs text-zinc-500 hover:text-emerald-400 transition">Privacy Policy</a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Content with Sidebar TOC --}}
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="lg:grid lg:grid-cols-[240px_1fr] lg:gap-16">

                    {{-- TOC Sidebar --}}
                    <aside class="hidden lg:block">
                        <div class="sticky top-28">
                            <p class="text-xs font-semibold uppercase tracking-widest text-zinc-600 mb-5">On this page</p>
                            <nav class="space-y-0.5">
                                @foreach ([
                                    'overview'       => 'Overview',
                                    'free-plan'      => 'Free Plan',
                                    'paid-plans'     => 'Paid Plans',
                                    'money-back'     => '14-Day Guarantee',
                                    'exceptions'     => 'Exceptions',
                                    'how-to-cancel'  => 'How to Cancel',
                                    'billing-cycles' => 'Billing Cycles',
                                    'contact'        => 'Contact Us',
                                ] as $id => $label)
                                    <a href="#{{ $id }}" class="group flex items-center gap-2.5 rounded-lg py-1.5 px-3 text-sm text-zinc-500 transition hover:text-emerald-400 hover:bg-emerald-500/5">
                                        <span class="h-px w-3 bg-zinc-700 group-hover:bg-emerald-500/50 transition shrink-0"></span>
                                        {{ $label }}
                                    </a>
                                @endforeach
                            </nav>
                        </div>
                    </aside>

                    {{-- Article --}}
                    <article class="text-zinc-300 leading-relaxed space-y-0">

                        <section id="overview" class="pb-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">01</span>
                                Overview
                            </h2>
                            <p>Finvixy ("the Service"), operated by Enclivix (Pty) Ltd, is a monthly subscription product. This Refund Policy explains your rights and our obligations regarding charges and cancellations.</p>
                            <p class="mt-3">We aim to be fair and transparent. If something goes wrong, <a href="mailto:billing@enclivix.com" class="text-emerald-400 hover:underline">contact us</a> — we'll do our best to make it right.</p>
                        </section>

                        <section id="free-plan" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">02</span>
                                Free Plan
                            </h2>
                            <p>The Free plan has no associated cost. No charges are ever made for Free plan usage, so no refunds are applicable.</p>
                        </section>

                        <section id="paid-plans" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">03</span>
                                Paid Plans (Starter, Professional, Business)
                            </h2>
                            <p>Paid subscriptions are billed monthly at the start of each billing period.</p>
                            <ul class="mt-4 space-y-3">
                                <li class="flex items-start gap-3">
                                    <svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    <span><strong class="text-white">No refunds for the current billing period</strong> once payment has been processed. Your access continues until the end of the period.</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    <span><strong class="text-white">Cancellation is immediate</strong> and stops renewal — you are not charged for the next period.</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    <span><strong class="text-white">Downgrades</strong> take effect at the start of the next billing cycle.</span>
                                </li>
                            </ul>
                        </section>

                        <section id="money-back" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">04</span>
                                14-Day Money-Back Guarantee
                            </h2>
                            <p>New customers upgrading to a paid plan for the <strong class="text-white">first time</strong> are eligible for a full refund within <strong class="text-white">14 days</strong> of the first charge — no questions asked.</p>
                            <div class="mt-5 rounded-xl border border-emerald-500/15 bg-emerald-500/5 p-5">
                                <p class="text-sm text-emerald-300">To request a refund under this guarantee, email <a href="mailto:billing@enclivix.com" class="underline hover:text-white">billing@enclivix.com</a> within 14 days of your first charge, quoting your account email address.</p>
                            </div>
                            <p class="mt-4 text-sm text-zinc-500">This guarantee applies once per customer account. It does not apply to plan upgrades after the initial purchase.</p>
                        </section>

                        <section id="exceptions" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">05</span>
                                Exceptions &amp; Special Circumstances
                            </h2>
                            <ul class="space-y-3">
                                <li class="flex items-start gap-3">
                                    <svg class="size-4 text-zinc-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    <span><strong class="text-white">Service outages:</strong> Refunds or credits may be issued at our discretion for outages exceeding 24 consecutive hours that substantially impair your use of the Service.</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <svg class="size-4 text-zinc-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    <span><strong class="text-white">Duplicate charges:</strong> If you are charged twice for the same period due to a technical error, we will refund the duplicate charge immediately upon verification.</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <svg class="size-4 text-zinc-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    <span><strong class="text-white">Unauthorised charges:</strong> If you believe a charge was made without your authorisation, contact your bank to initiate a dispute and notify us at <a href="mailto:billing@enclivix.com" class="text-emerald-400 hover:underline">billing@enclivix.com</a>.</span>
                                </li>
                            </ul>
                        </section>

                        <section id="how-to-cancel" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">06</span>
                                How to Cancel
                            </h2>
                            <p>You can cancel your subscription at any time:</p>
                            <ol class="mt-4 space-y-3 list-decimal list-inside text-zinc-300 marker:text-zinc-500">
                                <li>Log in to your Finvixy account.</li>
                                <li>Go to <strong class="text-white">Settings → Billing</strong>.</li>
                                <li>Select <strong class="text-white">Cancel Subscription</strong> and confirm.</li>
                            </ol>
                            <p class="mt-4 text-sm text-zinc-400">Alternatively, email <a href="mailto:billing@enclivix.com" class="text-emerald-400 hover:underline">billing@enclivix.com</a> and we will cancel on your behalf within one business day.</p>
                            <p class="mt-3 text-sm text-zinc-400">After cancellation, your account reverts to the Free plan at the end of the current billing period. Your data is retained for 90 days before deletion.</p>
                        </section>

                        <section id="billing-cycles" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">07</span>
                                Billing Cycles
                            </h2>
                            <ul class="space-y-3">
                                <li class="flex items-start gap-3">
                                    <svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    Subscriptions renew automatically on the same calendar date each month.
                                </li>
                                <li class="flex items-start gap-3">
                                    <svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    All prices are in South African Rand (ZAR) and are VAT exclusive.
                                </li>
                                <li class="flex items-start gap-3">
                                    <svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    We will notify you by email at least 7 days before any price increase takes effect.
                                </li>
                            </ul>
                        </section>

                        <section id="contact" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">08</span>
                                Contact Us
                            </h2>
                            <p>For billing questions or refund requests, contact us at:</p>
                            <div class="mt-5 rounded-xl border border-zinc-800 bg-zinc-900/50 p-5 space-y-2 text-sm">
                                <p><span class="text-zinc-500">Email:</span> <a href="mailto:billing@enclivix.com" class="text-emerald-400 hover:underline">billing@enclivix.com</a></p>
                                <p><span class="text-zinc-500">Company:</span> <span class="text-zinc-300">Enclivix (Pty) Ltd, South Africa</span></p>
                                <p class="text-zinc-500 pt-1">We respond to all billing enquiries within 2 business days.</p>
                            </div>
                        </section>

                    </article>
                </div>
            </div>
        </main>

        {{-- Footer --}}
        <footer class="border-t border-emerald-500/10 py-8">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="flex flex-col items-center gap-6 md:flex-row md:justify-between">
                    <div class="flex items-center gap-2">
                        <x-app-logo-icon class="h-7 w-auto" />
                        <x-finvixy-wordmark variant="dark" size="sm" />
                    </div>
                    <nav class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-xs text-zinc-500">
                        <a href="{{ route('pricing') }}" class="hover:text-emerald-400 transition">Pricing</a>
                        <span class="text-zinc-800">·</span>
                        <a href="{{ route('terms') }}" class="hover:text-emerald-400 transition">Terms of Service</a>
                        <span class="text-zinc-800">·</span>
                        <a href="{{ route('privacy') }}" class="hover:text-emerald-400 transition">Privacy Policy</a>
                        <span class="text-zinc-800">·</span>
                        <a href="{{ route('refund') }}" class="hover:text-emerald-400 transition font-medium text-emerald-400">Refund Policy</a>
                    </nav>
                    <p class="text-xs text-zinc-500">&copy; {{ date('Y') }} Finvixy by Enclivix (Pty) Ltd.</p>
                </div>
            </div>
        </footer>

    </body>
</html>
