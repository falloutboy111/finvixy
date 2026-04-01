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
                                    'merchant'       => 'Merchant of Record',
                                    'refunds'        => 'Refund Eligibility',
                                    'how-to-request' => 'How to Request',
                                    'cancellations'  => 'Cancellations',
                                    'free-plan'      => 'Free Plan',
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
                            <p>Finvixy ("the Service") is operated by Enclivix. All paid subscriptions are processed by <strong class="text-white">Paddle</strong>, our authorised merchant of record. This means that all billing, payments, and refunds are handled by Paddle in accordance with their policies.</p>
                            <p class="mt-3">This page summarises how refunds work for Finvixy subscriptions. For the full terms, please refer to <a href="https://www.paddle.com/legal/buyer-terms" class="text-emerald-400 hover:underline" target="_blank" rel="noopener">Paddle's Buyer Terms</a> and <a href="https://www.paddle.com/legal/refund-policy" class="text-emerald-400 hover:underline" target="_blank" rel="noopener">Paddle's Refund Policy</a>.</p>
                        </section>

                        <section id="merchant" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">02</span>
                                Merchant of Record
                            </h2>
                            <p>Paddle acts as the merchant of record for all Finvixy transactions. When you subscribe to a paid plan, you are purchasing through Paddle's platform. Paddle handles:</p>
                            <ul class="mt-4 space-y-3">
                                <li class="flex items-start gap-3">
                                    <svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    Payment processing and recurring billing
                                </li>
                                <li class="flex items-start gap-3">
                                    <svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    Invoicing and receipts
                                </li>
                                <li class="flex items-start gap-3">
                                    <svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    Sales tax and VAT compliance
                                </li>
                                <li class="flex items-start gap-3">
                                    <svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    Refunds and chargebacks
                                </li>
                            </ul>
                            <p class="mt-4 text-sm text-zinc-400">You may see "Paddle" or "Paddle.com" on your bank or card statement for Finvixy charges.</p>
                        </section>

                        <section id="refunds" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">03</span>
                                Refund Eligibility
                            </h2>
                            <p>Refunds for Finvixy subscriptions are governed by <a href="https://www.paddle.com/legal/refund-policy" class="text-emerald-400 hover:underline" target="_blank" rel="noopener">Paddle's Refund Policy</a>. In summary:</p>
                            <ul class="mt-4 space-y-3">
                                <li class="flex items-start gap-3">
                                    <svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    <span>Paddle may issue discretionary refunds for requests submitted within <strong class="text-white">14 days</strong> of a transaction</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    <span>Where local consumer protection laws grant statutory withdrawal rights (e.g. EU/EEA/UK 14-day right), those rights apply and are honoured in full</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    <span>Refunds for technical or product defects are available where there is evidence of a material issue preventing access to advertised features</span>
                                </li>
                            </ul>
                            <div class="mt-5 rounded-xl border border-emerald-500/15 bg-emerald-500/5 p-5">
                                <p class="text-sm text-emerald-300">Nothing in this policy limits any mandatory consumer rights you may have under applicable law in your territory of purchase.</p>
                            </div>
                        </section>

                        <section id="how-to-request" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">04</span>
                                How to Request a Refund
                            </h2>
                            <p>To request a refund, use one of the following methods:</p>
                            <ol class="mt-4 space-y-3 list-decimal list-inside text-zinc-300 marker:text-zinc-500">
                                <li>Use the <strong class="text-white">"View receipt"</strong> or <strong class="text-white">"Manage subscription"</strong> link in your transaction confirmation email from Paddle.</li>
                                <li>Visit <a href="https://paddle.net" class="text-emerald-400 hover:underline" target="_blank" rel="noopener">paddle.net</a> and select <strong class="text-white">"Request refund"</strong>.</li>
                                <li>Contact our team at <a href="mailto:billing@enclivix.com" class="text-emerald-400 hover:underline">billing@enclivix.com</a> and we will assist you through the process.</li>
                            </ol>
                            <p class="mt-4 text-sm text-zinc-400">If eligible, refunds will be processed by Paddle using the same payment method where possible, within 14 days of the request being approved.</p>
                        </section>

                        <section id="cancellations" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">05</span>
                                Cancellations
                            </h2>
                            <p>You can cancel your subscription at any time. Your cancellation takes effect at the end of the current billing period, and you will not be charged again after that.</p>
                            <p class="mt-3">To cancel:</p>
                            <ol class="mt-4 space-y-3 list-decimal list-inside text-zinc-300 marker:text-zinc-500">
                                <li>Log in to your Finvixy account and go to <strong class="text-white">Settings → Billing</strong>.</li>
                                <li>Select <strong class="text-white">Cancel Subscription</strong> and confirm.</li>
                            </ol>
                            <p class="mt-4 text-sm text-zinc-400">Alternatively, use the subscription management link in your Paddle receipt email, or email <a href="mailto:billing@enclivix.com" class="text-emerald-400 hover:underline">billing@enclivix.com</a> and we will cancel on your behalf.</p>
                            <p class="mt-3 text-sm text-zinc-400">After cancellation, your account reverts to the Free plan at the end of the current billing period.</p>
                        </section>

                        <section id="free-plan" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">06</span>
                                Free Plan
                            </h2>
                            <p>The Free plan has no associated cost. No charges are ever made for Free plan usage, so no refunds are applicable.</p>
                        </section>

                        <section id="contact" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">07</span>
                                Contact Us
                            </h2>
                            <p>For billing questions or help with a refund request, contact us at:</p>
                            <div class="mt-5 rounded-xl border border-zinc-800 bg-zinc-900/50 p-5 space-y-2 text-sm">
                                <p><span class="text-zinc-500">Email:</span> <a href="mailto:billing@enclivix.com" class="text-emerald-400 hover:underline">billing@enclivix.com</a></p>
                                <p><span class="text-zinc-500">Company:</span> <span class="text-zinc-300">Enclivix, South Africa</span></p>
                                <p><span class="text-zinc-500">Payments processed by:</span> <a href="https://www.paddle.com" class="text-emerald-400 hover:underline" target="_blank" rel="noopener">Paddle.com</a></p>
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
                        <a href="{{ route('refund') }}" class="hover:text-emerald-400 transition">Refund Policy</a>
                    </nav>
                    <p class="text-xs text-zinc-500">&copy; {{ date('Y') }} Finvixy by Enclivix. All rights reserved.</p>
                </div>
            </div>
        </footer>

    </body>
</html>
