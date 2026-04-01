{{-- Privacy Policy — Improved two-column layout with sticky TOC sidebar --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Privacy Policy — Finvixy</title>
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
                            <h1 class="text-4xl font-bold text-white">Privacy Policy</h1>
                            <p class="mt-2 text-sm text-zinc-500">Last updated: {{ now()->format('j F Y') }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('terms') }}" class="text-xs text-zinc-500 hover:text-emerald-400 transition">Terms of Service</a>
                            <span class="text-zinc-800">·</span>
                            <a href="{{ route('refund') }}" class="text-xs text-zinc-500 hover:text-emerald-400 transition">Refund Policy</a>
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
                                    'introduction'    => '1. Introduction',
                                    'info-collected'  => '2. Information We Collect',
                                    'how-we-use'      => '3. How We Use Your Data',
                                    'your-control'    => '4. Your Data, Your Control',
                                    'security'        => '5. Data Storage & Security',
                                    'third-parties'   => '6. Third-Party Services',
                                    'google-policy'   => '7. Google API Services',
                                    'retention'       => '8. Data Retention',
                                    'cookies'         => '9. Cookies',
                                    'children'        => '10. Children\'s Privacy',
                                    'changes'         => '11. Policy Changes',
                                    'contact'         => '12. Contact Us',
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
                    <article class="text-zinc-300 leading-relaxed">

                        <section id="introduction" class="pb-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">01</span>
                                Introduction
                            </h2>
                            <p>Finvixy ("we", "our", "us") is a product of Enclivix (Pty) Ltd, a South African company. This Privacy Policy explains how we collect, use, store, and protect your information when you use our receipt scanning and expense tracking service at <a href="https://finvixy.co.za" class="text-emerald-400 hover:underline">finvixy.co.za</a>.</p>
                            <p class="mt-3">By using Finvixy, you agree to the collection and use of information in accordance with this policy.</p>
                        </section>

                        <section id="info-collected" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-6 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">02</span>
                                Information We Collect
                            </h2>

                            <h3 class="text-base font-medium text-zinc-200 mb-3">Account Information</h3>
                            <ul class="space-y-2.5 mb-6">
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Name, email address, and WhatsApp number (provided at registration)</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Organisation or business name</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Account credentials (passwords are hashed and never stored in plain text)</li>
                            </ul>

                            <h3 class="text-base font-medium text-zinc-200 mb-3">Receipt and Expense Data</h3>
                            <ul class="space-y-2.5 mb-6">
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Receipt images and PDF documents you upload or send via WhatsApp</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Extracted data: vendor names, dates, amounts, line items, and categories</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>This data is processed by AI (AWS Textract and Amazon Bedrock) to extract receipt information</li>
                            </ul>

                            <h3 class="text-base font-medium text-zinc-200 mb-3">Google Account Data</h3>
                            <ul class="space-y-2.5">
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>If you connect Google Drive, we request access to the <code class="text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded text-xs">drive.file</code> scope only</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>This allows us to <strong class="text-white">create and manage files and folders that Finvixy creates</strong> in your Google Drive — nothing else</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>We <strong class="text-white">cannot</strong> read, modify, or delete any other files in your Google Drive</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>We store your Google OAuth token (encrypted) to maintain the connection</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Your Google email address is stored to identify the connected account</li>
                            </ul>
                        </section>

                        <section id="how-we-use" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">03</span>
                                How We Use Your Information
                            </h2>
                            <ul class="space-y-3">
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg><span><strong class="text-white">Receipt processing:</strong> We use AI services (AWS Textract and Amazon Bedrock) to extract text and categorise your receipts</span></li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg><span><strong class="text-white">Google Drive sync:</strong> Receipts are automatically organised into category folders in your own Google Drive. We only write to a dedicated Finvixy folder — your data stays in your Drive, under your control</span></li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg><span><strong class="text-white">WhatsApp scanning:</strong> When you send a receipt photo via WhatsApp, we process it and return the results to you</span></li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg><span><strong class="text-white">Reports and insights:</strong> We generate spending charts and analytics from your expense data</span></li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg><span><strong class="text-white">Account notifications:</strong> We send transactional emails (verification, password reset) via Postmark</span></li>
                            </ul>
                        </section>

                        <section id="your-control" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">04</span>
                                Your Data, Your Control
                            </h2>
                            <p><strong class="text-white">You own your data.</strong> Finvixy is designed as an organiser — we help you structure and store your receipts, but the data belongs to you.</p>
                            <ul class="mt-4 space-y-3">
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg><span><strong class="text-white">Google Drive:</strong> All synced receipts live in your own Google Drive. If you disconnect Finvixy, the files remain in your Drive</span></li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg><span><strong class="text-white">Export:</strong> Your receipts are always accessible through our app or directly in your Google Drive</span></li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg><span><strong class="text-white">Deletion:</strong> You can request full deletion of your account and all associated data by contacting us</span></li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg><span><strong class="text-white">Disconnect:</strong> You can disconnect Google Drive at any time from Settings → Connected Accounts</span></li>
                            </ul>
                        </section>

                        <section id="security" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">05</span>
                                Data Storage and Security
                            </h2>
                            <ul class="space-y-3">
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Receipt images are stored in encrypted Amazon S3 buckets (EU region)</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Application data is stored in a secured database with encryption at rest</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Google OAuth credentials are encrypted using Laravel's encryption (AES-256-CBC)</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>All connections use HTTPS/TLS encryption in transit</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>We implement two-factor authentication (2FA) for additional account security</li>
                            </ul>
                        </section>

                        <section id="third-parties" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">06</span>
                                Third-Party Services
                            </h2>
                            <p class="mb-5">We use the following third-party services to operate Finvixy:</p>
                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach ([
                                    ['name' => 'Amazon Web Services', 'desc' => 'S3 storage, Textract OCR, Bedrock AI processing'],
                                    ['name' => 'Google APIs', 'desc' => 'Google Drive sync (drive.file scope only)'],
                                    ['name' => 'Meta / WhatsApp Business', 'desc' => 'Receiving and sending WhatsApp messages'],
                                    ['name' => 'Postmark', 'desc' => 'Transactional email delivery'],
                                ] as $service)
                                    <div class="rounded-xl border border-zinc-800/60 bg-zinc-900/30 p-4">
                                        <p class="text-sm font-medium text-zinc-200">{{ $service['name'] }}</p>
                                        <p class="mt-1 text-xs text-zinc-500">{{ $service['desc'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                            <p class="mt-4 text-sm text-zinc-400">Each of these services has their own privacy policies. We encourage you to review them.</p>
                        </section>

                        <section id="google-policy" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">07</span>
                                Google API Services User Data Policy
                            </h2>
                            <p>Finvixy's use and transfer to any other app of information received from Google APIs will adhere to the <a href="https://developers.google.com/terms/api-services-user-data-policy" class="text-emerald-400 hover:underline" target="_blank" rel="noopener">Google API Services User Data Policy</a>, including the Limited Use requirements.</p>
                            <ul class="mt-4 space-y-3">
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>We only request the <code class="text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded text-xs">drive.file</code> scope — the minimum needed</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>We do not use Google data for advertising purposes</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>We do not transfer Google data to third parties except as needed to provide the service</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>We do not use Google data to develop a surveillance tool or product</li>
                            </ul>
                        </section>

                        <section id="retention" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">08</span>
                                Data Retention
                            </h2>
                            <p>We retain your data for as long as your account is active. If you delete your account:</p>
                            <ul class="mt-4 space-y-3">
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>All personal data and expense records are permanently deleted from our systems</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Receipt images in S3 are deleted</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Files already synced to your Google Drive remain there (they are in your account)</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Google OAuth tokens are revoked and deleted</li>
                            </ul>
                        </section>

                        <section id="cookies" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">09</span>
                                Cookies
                            </h2>
                            <p>We use essential session cookies to maintain your login state. We do not use third-party tracking cookies or advertising cookies.</p>
                        </section>

                        <section id="children" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">10</span>
                                Children's Privacy
                            </h2>
                            <p>Finvixy is not intended for use by children under 18. We do not knowingly collect personal information from children.</p>
                        </section>

                        <section id="changes" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">11</span>
                                Policy Changes
                            </h2>
                            <p>We may update this Privacy Policy from time to time. We will notify you of significant changes by email or in-app notification. Continued use of Finvixy after changes constitutes acceptance.</p>
                        </section>

                        <section id="contact" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">12</span>
                                Contact Us
                            </h2>
                            <p>If you have questions about this Privacy Policy or your data, contact us at:</p>
                            <div class="mt-5 rounded-xl border border-zinc-800 bg-zinc-900/50 p-5 space-y-2 text-sm">
                                <p><span class="text-zinc-500">Email:</span> <a href="mailto:info@enclivix.com" class="text-emerald-400 hover:underline">info@enclivix.com</a></p>
                                <p><span class="text-zinc-500">Company:</span> <span class="text-zinc-300">Enclivix (Pty) Ltd, South Africa</span></p>
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
                        <a href="{{ route('privacy') }}" class="hover:text-emerald-400 transition font-medium text-emerald-400">Privacy Policy</a>
                        <span class="text-zinc-800">·</span>
                        <a href="{{ route('refund') }}" class="hover:text-emerald-400 transition">Refund Policy</a>
                    </nav>
                    <p class="text-xs text-zinc-500">&copy; {{ date('Y') }} Finvixy by Enclivix (Pty) Ltd.</p>
                </div>
            </div>
        </footer>
    </body>
</html>
