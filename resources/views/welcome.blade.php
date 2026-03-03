<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Finvixy — Scan. Track. Save.</title>
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
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
                        <x-app-logo-icon class="size-8" />
                        <x-finvixy-wordmark variant="dark" size="lg" />
                    </a>
                    <nav class="flex items-center gap-4">
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-4 py-2 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-400 glow-sm">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-medium text-gray-400 transition hover:text-emerald-400">
                                Log in
                            </a>
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

        {{-- Hero Section --}}
        <section class="relative pt-32 pb-20 lg:pt-44 lg:pb-32 overflow-hidden">
            {{-- Glow background effects --}}
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-emerald-500/5 rounded-full blur-3xl"></div>
                <div class="absolute top-1/3 right-1/4 w-[300px] h-[300px] bg-emerald-400/5 rounded-full blur-3xl"></div>
            </div>

            <div class="relative mx-auto max-w-7xl px-6 lg:px-8 text-center">
                <div class="mx-auto max-w-3xl">
                    <div class="inline-flex items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/5 px-4 py-1.5 text-xs font-medium text-emerald-400 mb-8">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        Receipt scanning powered by AI
                    </div>

                    <h1 class="text-5xl font-bold tracking-tight text-white sm:text-7xl">
                        <span class="gradient-green-text">Scan.</span>
                        <span class="text-white">Track.</span>
                        <span class="gradient-green-text">Save.</span>
                    </h1>

                    <p class="mt-6 text-lg leading-8 text-gray-400 max-w-2xl mx-auto">
                        Snap a photo of your receipt via WhatsApp or upload it. Our AI extracts every detail — vendor, amount, line items — and syncs it all to Google Drive. Your expenses, organised.
                    </p>

                    <div class="mt-10 flex items-center justify-center gap-4">
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-6 py-3 text-base font-semibold text-zinc-950 transition hover:bg-emerald-400 glow-md">
                                Start Free
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638l-3.96-3.96a.75.75 0 111.06-1.06l5.25 5.25a.75.75 0 010 1.06l-5.25 5.25a.75.75 0 11-1.06-1.06l3.96-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
                                </svg>
                            </a>
                        @endif
                        <a href="#features" class="inline-flex items-center gap-2 rounded-lg border border-emerald-500/20 px-6 py-3 text-base font-medium text-emerald-400 transition hover:border-emerald-500/40 hover:bg-emerald-500/5">
                            See How It Works
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- Features Section --}}
        <section id="features" class="relative py-20 lg:py-32">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-white sm:text-4xl">Everything you need to track expenses</h2>
                    <p class="mt-4 text-lg text-gray-400">No spreadsheets. No manual entry. Just scan and go.</p>
                </div>

                <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
                    {{-- Feature 1: WhatsApp Scanning --}}
                    <div class="glow-card rounded-2xl p-6">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-emerald-500/10 mb-5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-2">WhatsApp Scanning</h3>
                        <p class="text-sm text-gray-400 leading-relaxed">
                            Send a photo of your receipt via WhatsApp. Our AI extracts vendor, amount, and every line item — instantly.
                        </p>
                    </div>

                    {{-- Feature 2: Smart Upload --}}
                    <div class="glow-card rounded-2xl p-6">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-emerald-500/10 mb-5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-2">Smart Upload</h3>
                        <p class="text-sm text-gray-400 leading-relaxed">
                            Upload receipts from your browser. Photos, camera capture, and PDFs — all powered by AI-driven OCR.
                        </p>
                    </div>

                    {{-- Feature 3: Google Drive Sync --}}
                    <div class="glow-card rounded-2xl p-6">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-emerald-500/10 mb-5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-2">Google Drive Sync</h3>
                        <p class="text-sm text-gray-400 leading-relaxed">
                            Receipts auto-sync to <strong class="text-gray-300">your own Google Drive</strong>, organised by category. Share a folder with your accountant in one click.
                        </p>
                    </div>

                    {{-- Feature 4: Expense Insights --}}
                    <div class="glow-card rounded-2xl p-6">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-emerald-500/10 mb-5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-2">Expense Insights</h3>
                        <p class="text-sm text-gray-400 leading-relaxed">
                            See where your money goes. Spending breakdowns by category, vendor trends, and smart recommendations.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- How It Works --}}
        <section class="relative py-20 lg:py-32 border-t border-emerald-500/5">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-white sm:text-4xl">How it works</h2>
                    <p class="mt-4 text-lg text-gray-400">Three steps to expense freedom.</p>
                </div>

                <div class="grid gap-12 md:grid-cols-3">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 mb-6 glow-sm">
                            <span class="text-2xl font-bold gradient-green-text">1</span>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-2">Scan</h3>
                        <p class="text-gray-400">Send a receipt photo via WhatsApp or upload through the app. That's it.</p>
                    </div>
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 mb-6 glow-sm">
                            <span class="text-2xl font-bold gradient-green-text">2</span>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-2">Extract</h3>
                        <p class="text-gray-400">Our AI reads the receipt, extracts vendor details, line items, totals, and categorises it.</p>
                    </div>
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 mb-6 glow-sm">
                            <span class="text-2xl font-bold gradient-green-text">3</span>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-2">Organise</h3>
                        <p class="text-gray-400">Everything lands in your dashboard and syncs to Google Drive — neatly filed by date and category.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Your Data, Your Control --}}
        <section class="relative py-20 lg:py-32 border-t border-emerald-500/5">
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute top-1/2 left-1/4 w-[400px] h-[400px] bg-emerald-500/5 rounded-full blur-3xl"></div>
            </div>
            <div class="relative mx-auto max-w-7xl px-6 lg:px-8">
                <div class="grid gap-12 lg:grid-cols-2 items-center">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/5 px-4 py-1.5 text-xs font-medium text-emerald-400 mb-6">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                            Your data stays yours
                        </div>
                        <h2 class="text-3xl font-bold text-white sm:text-4xl">Your data. Your control.</h2>
                        <p class="mt-4 text-lg text-gray-400 leading-relaxed">
                            We don't store your receipts on our servers long-term. Everything syncs straight to <strong class="text-gray-300">your own Google Drive</strong> — organised by category, accessible from anywhere. We only help you organise your data automatically.
                        </p>
                        <ul class="mt-8 space-y-4">
                            <li class="flex items-start gap-3">
                                <svg class="size-5 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                <span class="text-gray-400"><strong class="text-gray-300">You own your storage</strong> — receipts live in your Google Drive, not ours</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="size-5 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                <span class="text-gray-400"><strong class="text-gray-300">Category folders</strong> — auto-organised by Travel, Food, Office, and more</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="size-5 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                <span class="text-gray-400"><strong class="text-gray-300">Limited permissions</strong> — we can only write to our own app folder, nothing else</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="size-5 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                <span class="text-gray-400"><strong class="text-gray-300">Share with your accountant</strong> — just share the Drive folder, done</span>
                            </li>
                        </ul>
                    </div>
                    <div class="flex items-center justify-center">
                        <div class="relative w-full max-w-sm">
                            <div class="glow-card rounded-2xl p-6 space-y-3">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="flex items-center justify-center size-10 rounded-xl bg-emerald-500/10">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" /></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-white">MyBusiness-finvixy</p>
                                        <p class="text-[11px] text-zinc-500">Google Drive</p>
                                    </div>
                                </div>
                                @foreach (['Travel', 'Food & Dining', 'Office Supplies', 'Transport', 'Utilities'] as $folder)
                                    <div class="flex items-center gap-3 py-2 px-3 rounded-lg bg-white/[0.02]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-emerald-400/60" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" /></svg>
                                        <span class="text-xs text-gray-400">{{ $folder }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Pricing Section --}}
        <section id="pricing" class="relative py-20 lg:py-32 border-t border-emerald-500/5">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-white sm:text-4xl">Simple, transparent pricing</h2>
                    <p class="mt-4 text-lg text-gray-400">Start free. Scale as you grow.</p>
                </div>

                <div class="grid gap-6 md:grid-cols-3 lg:grid-cols-5">
                    {{-- Free --}}
                    <div class="glow-card rounded-2xl p-6 flex flex-col">
                        <h3 class="text-sm font-medium text-emerald-400 uppercase tracking-wider">Free</h3>
                        <div class="mt-4 flex items-baseline gap-1">
                            <span class="text-3xl font-bold text-white">R0</span>
                            <span class="text-sm text-gray-500">/mo</span>
                        </div>
                        <p class="mt-3 text-sm text-gray-400">Perfect for trying out Finvixy.</p>
                        <ul class="mt-6 space-y-3 text-sm text-gray-400 flex-1">
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                10 receipts/month
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                WhatsApp scanning
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                Basic reports
                            </li>
                        </ul>
                        <a href="{{ Route::has('register') ? route('register') : '#' }}" class="mt-6 block text-center rounded-lg border border-emerald-500/20 px-4 py-2.5 text-sm font-medium text-emerald-400 transition hover:bg-emerald-500/5">
                            Get Started
                        </a>
                    </div>

                    {{-- Starter --}}
                    <div class="glow-card rounded-2xl p-6 flex flex-col">
                        <h3 class="text-sm font-medium text-emerald-400 uppercase tracking-wider">Starter</h3>
                        <div class="mt-4 flex items-baseline gap-1">
                            <span class="text-3xl font-bold text-white">R99</span>
                            <span class="text-sm text-gray-500">/mo</span>
                        </div>
                        <p class="mt-3 text-sm text-gray-400">For individuals and freelancers.</p>
                        <ul class="mt-6 space-y-3 text-sm text-gray-400 flex-1">
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                50 receipts/month
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                Google Drive sync
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                Full reports
                            </li>
                        </ul>
                        <a href="{{ Route::has('register') ? route('register') : '#' }}" class="mt-6 block text-center rounded-lg border border-emerald-500/20 px-4 py-2.5 text-sm font-medium text-emerald-400 transition hover:bg-emerald-500/5">
                            Get Started
                        </a>
                    </div>

                    {{-- Professional (Featured) --}}
                    <div class="relative rounded-2xl p-6 flex flex-col border-2 border-emerald-500/40 bg-gradient-to-b from-emerald-500/10 to-zinc-950 glow-md">
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                            <span class="inline-flex items-center rounded-full bg-emerald-500 px-3 py-1 text-xs font-semibold text-zinc-950">Most Popular</span>
                        </div>
                        <h3 class="text-sm font-medium text-emerald-400 uppercase tracking-wider">Professional</h3>
                        <div class="mt-4 flex items-baseline gap-1">
                            <span class="text-3xl font-bold text-white">R189</span>
                            <span class="text-sm text-gray-500">/mo</span>
                        </div>
                        <p class="mt-3 text-sm text-gray-400">For growing businesses.</p>
                        <ul class="mt-6 space-y-3 text-sm text-gray-400 flex-1">
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                150 receipts/month
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                Priority processing
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                Spending insights
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                Accountant sharing
                            </li>
                        </ul>
                        <a href="{{ Route::has('register') ? route('register') : '#' }}" class="mt-6 block text-center rounded-lg bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-400 glow-sm">
                            Get Started
                        </a>
                    </div>

                    {{-- Business --}}
                    <div class="glow-card rounded-2xl p-6 flex flex-col">
                        <h3 class="text-sm font-medium text-emerald-400 uppercase tracking-wider">Business</h3>
                        <div class="mt-4 flex items-baseline gap-1">
                            <span class="text-3xl font-bold text-white">R349</span>
                            <span class="text-sm text-gray-500">/mo</span>
                        </div>
                        <p class="mt-3 text-sm text-gray-400">For teams and businesses.</p>
                        <ul class="mt-6 space-y-3 text-sm text-gray-400 flex-1">
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                500 receipts/month
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                Team access
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                Advanced analytics
                            </li>
                        </ul>
                        <a href="{{ Route::has('register') ? route('register') : '#' }}" class="mt-6 block text-center rounded-lg border border-emerald-500/20 px-4 py-2.5 text-sm font-medium text-emerald-400 transition hover:bg-emerald-500/5">
                            Get Started
                        </a>
                    </div>

                    {{-- Enterprise --}}
                    <div class="glow-card rounded-2xl p-6 flex flex-col">
                        <h3 class="text-sm font-medium text-emerald-400 uppercase tracking-wider">Enterprise</h3>
                        <div class="mt-4 flex items-baseline gap-1">
                            <span class="text-3xl font-bold text-white">R599</span>
                            <span class="text-sm text-gray-500">/mo</span>
                        </div>
                        <p class="mt-3 text-sm text-gray-400">Unlimited everything.</p>
                        <ul class="mt-6 space-y-3 text-sm text-gray-400 flex-1">
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                Unlimited receipts
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                Priority support
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                Custom integrations
                            </li>
                        </ul>
                        <a href="{{ Route::has('register') ? route('register') : '#' }}" class="mt-6 block text-center rounded-lg border border-emerald-500/20 px-4 py-2.5 text-sm font-medium text-emerald-400 transition hover:bg-emerald-500/5">
                            Get Started
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- CTA Section --}}
        <section class="relative py-20 lg:py-32 border-t border-emerald-500/5">
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-[800px] h-[400px] bg-emerald-500/5 rounded-full blur-3xl"></div>
            </div>
            <div class="relative mx-auto max-w-3xl px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-bold text-white sm:text-4xl">Ready to simplify your expenses?</h2>
                <p class="mt-4 text-lg text-gray-400">Join Finvixy and stop losing receipts. Start scanning in seconds.</p>
                <div class="mt-8">
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-8 py-4 text-lg font-semibold text-zinc-950 transition hover:bg-emerald-400 glow-lg">
                            Get Started Free
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638l-3.96-3.96a.75.75 0 111.06-1.06l5.25 5.25a.75.75 0 010 1.06l-5.25 5.25a.75.75 0 11-1.06-1.06l3.96-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @endif
                </div>
            </div>
        </section>

        {{-- Footer --}}
        <footer class="border-t border-emerald-500/10 py-10">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="flex flex-col items-center justify-between gap-6 md:flex-row">
                    <div class="flex items-center gap-2">
                        <x-app-logo-icon class="size-6" />
                        <x-finvixy-wordmark variant="dark" size="sm" />
                    </div>
                    <nav class="flex items-center gap-6">
                        <a href="{{ route('privacy') }}" class="text-xs text-gray-500 transition hover:text-emerald-400">Privacy Policy</a>
                        <a href="{{ route('terms') }}" class="text-xs text-gray-500 transition hover:text-emerald-400">Terms of Service</a>
                    </nav>
                    <p class="text-xs text-gray-500">&copy; {{ date('Y') }} Finvixy by Enclivix (Pty) Ltd. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </body>
</html>
