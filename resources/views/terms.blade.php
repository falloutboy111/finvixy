{{-- Terms of Service — Improved two-column layout with sticky TOC sidebar --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Terms of Service — Finvixy</title>
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
                            <h1 class="text-4xl font-bold text-white">Terms of Service</h1>
                            <p class="mt-2 text-sm text-zinc-500">Last updated: {{ now()->format('j F Y') }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('privacy') }}" class="text-xs text-zinc-500 hover:text-emerald-400 transition">Privacy Policy</a>
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
                                    'agreement'      => '1. Agreement to Terms',
                                    'description'    => '2. Description of Service',
                                    'registration'   => '3. Account Registration',
                                    'google-drive'   => '4. Google Drive Integration',
                                    'whatsapp'       => '5. WhatsApp Integration',
                                    'plans-pricing'  => '6. Plans and Pricing',
                                    'ownership'      => '7. Data Ownership',
                                    'acceptable-use' => '8. Acceptable Use',
                                    'ai-disclaimer'  => '9. AI Processing',
                                    'availability'   => '10. Service Availability',
                                    'liability'      => '11. Limitation of Liability',
                                    'termination'    => '12. Account Termination',
                                    'changes'        => '13. Changes to Terms',
                                    'governing-law'  => '14. Governing Law',
                                    'contact'        => '15. Contact Us',
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

                        <section id="agreement" class="pb-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">01</span>
                                Agreement to Terms
                            </h2>
                            <p>By accessing or using Finvixy ("the Service"), operated by Enclivix (Pty) Ltd ("we", "our", "us"), you agree to be bound by these Terms of Service. If you do not agree, please do not use the Service.</p>
                        </section>

                        <section id="description" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">02</span>
                                Description of Service
                            </h2>
                            <p>Finvixy is a receipt scanning and expense tracking platform that:</p>
                            <ul class="mt-4 space-y-2.5">
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Accepts receipt images via web upload or WhatsApp</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Uses AI-powered OCR to extract receipt data (vendor, amounts, line items, dates)</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Automatically categorises and organises expenses</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Optionally syncs receipts to your personal Google Drive in organised category folders</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Provides spending reports and analytics</li>
                            </ul>
                            <div class="mt-5 rounded-xl border border-emerald-500/15 bg-emerald-500/5 p-4 text-sm">
                                <strong class="text-emerald-300">Your data stays yours.</strong>
                                <span class="text-zinc-400 ml-1">We act as an organiser and processor — all receipt files synced to Google Drive are stored in your own Drive account, under your control.</span>
                            </div>
                        </section>

                        <section id="registration" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">03</span>
                                Account Registration
                            </h2>
                            <ul class="space-y-3">
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>You must provide accurate information during registration, including your name, email, organisation name, and WhatsApp number</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>You are responsible for maintaining the security of your account credentials</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>You must be at least 18 years old to create an account</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>One person or entity per account — do not share account access</li>
                            </ul>
                        </section>

                        <section id="google-drive" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">04</span>
                                Google Drive Integration
                            </h2>
                            <p>When you connect your Google Drive account:</p>
                            <ul class="mt-4 space-y-3">
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>We request the <code class="text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded text-xs">drive.file</code> scope, which only allows us to manage files and folders that Finvixy itself creates</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>We <strong class="text-white">cannot access, read, or modify</strong> any existing files in your Google Drive</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Receipts are organised into category folders (e.g., <code class="text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded text-xs">YourBusiness-finvixy/Travel/</code>)</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>You can disconnect Google Drive at any time; files already synced remain in your Drive</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Our use of Google APIs complies with the <a href="https://developers.google.com/terms/api-services-user-data-policy" class="text-emerald-400 hover:underline" target="_blank" rel="noopener">Google API Services User Data Policy</a></li>
                            </ul>
                        </section>

                        <section id="whatsapp" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">05</span>
                                WhatsApp Integration
                            </h2>
                            <ul class="space-y-3">
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>You may send receipt images to our WhatsApp Business number for processing</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>By sending messages, you consent to us processing the images through our AI system</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>We only process image and document messages — other message types are ignored</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Standard WhatsApp and mobile data charges from your carrier may apply</li>
                            </ul>
                        </section>

                        <section id="plans-pricing" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">06</span>
                                Plans and Pricing
                            </h2>
                            <ul class="space-y-3">
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Finvixy offers free and paid plans with different monthly receipt limits</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Pricing is in South African Rand (ZAR) and subject to change with reasonable notice</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Free plan users receive 10 receipt scans per month</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Exceeding your plan limit will pause scanning until the next billing period or until you upgrade</li>
                            </ul>
                            <p class="mt-4 text-sm text-zinc-400">View full plan details on our <a href="{{ route('pricing') }}" class="text-emerald-400 hover:underline">Pricing page</a>. Refunds are governed by our <a href="{{ route('refund') }}" class="text-emerald-400 hover:underline">Refund Policy</a>.</p>
                        </section>

                        <section id="ownership" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">07</span>
                                Data Ownership
                            </h2>
                            <p><strong class="text-white">You retain full ownership of all data you upload to Finvixy.</strong></p>
                            <ul class="mt-4 space-y-3">
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Receipt images and extracted data belong to you</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Files synced to Google Drive are stored in your personal Drive account</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>We do not claim any intellectual property rights over your data</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>We will not sell, share, or use your data for advertising or marketing purposes</li>
                            </ul>
                        </section>

                        <section id="acceptable-use" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">08</span>
                                Acceptable Use
                            </h2>
                            <p>You agree not to:</p>
                            <ul class="mt-4 space-y-3">
                                <li class="flex items-start gap-3"><span class="size-4 shrink-0 mt-1 flex items-center justify-center rounded-full bg-zinc-800 text-zinc-500"><svg class="size-2.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></span>Use the Service for any unlawful purpose</li>
                                <li class="flex items-start gap-3"><span class="size-4 shrink-0 mt-1 flex items-center justify-center rounded-full bg-zinc-800 text-zinc-500"><svg class="size-2.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></span>Upload content that is harmful, fraudulent, or violates any law</li>
                                <li class="flex items-start gap-3"><span class="size-4 shrink-0 mt-1 flex items-center justify-center rounded-full bg-zinc-800 text-zinc-500"><svg class="size-2.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></span>Attempt to circumvent plan limits or abuse the system</li>
                                <li class="flex items-start gap-3"><span class="size-4 shrink-0 mt-1 flex items-center justify-center rounded-full bg-zinc-800 text-zinc-500"><svg class="size-2.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></span>Reverse-engineer, decompile, or attempt to extract the source code of the Service</li>
                                <li class="flex items-start gap-3"><span class="size-4 shrink-0 mt-1 flex items-center justify-center rounded-full bg-zinc-800 text-zinc-500"><svg class="size-2.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></span>Use automated scripts or bots to access the Service</li>
                            </ul>
                        </section>

                        <section id="ai-disclaimer" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">09</span>
                                AI Processing Disclaimer
                            </h2>
                            <p>Receipt data extraction is performed by AI and may not always be 100% accurate. You should review extracted data before relying on it for financial, tax, or legal purposes. Finvixy is not a substitute for professional accounting or tax advice.</p>
                        </section>

                        <section id="availability" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">10</span>
                                Service Availability
                            </h2>
                            <p>We strive to maintain high availability but do not guarantee uninterrupted service. We may perform maintenance, updates, or experience outages. We will make reasonable efforts to notify users of planned downtime.</p>
                        </section>

                        <section id="liability" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">11</span>
                                Limitation of Liability
                            </h2>
                            <p>To the fullest extent permitted by South African law, Enclivix (Pty) Ltd shall not be liable for any indirect, incidental, special, or consequential damages arising from your use of the Service, including but not limited to loss of data, revenue, or business opportunities.</p>
                        </section>

                        <section id="termination" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">12</span>
                                Account Termination
                            </h2>
                            <ul class="space-y-3">
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>You may delete your account at any time from Settings</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>We may suspend or terminate accounts that violate these Terms</li>
                                <li class="flex items-start gap-3"><svg class="size-4 text-emerald-500 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Upon termination, your data will be deleted as described in our <a href="{{ route('privacy') }}" class="text-emerald-400 hover:underline">Privacy Policy</a></li>
                            </ul>
                        </section>

                        <section id="changes" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">13</span>
                                Changes to Terms
                            </h2>
                            <p>We may update these Terms from time to time. We will notify you of material changes via email or in-app notification. Continued use of the Service after changes constitutes acceptance of the updated Terms.</p>
                        </section>

                        <section id="governing-law" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">14</span>
                                Governing Law
                            </h2>
                            <p>These Terms are governed by and construed in accordance with the laws of the Republic of South Africa. Any disputes shall be resolved in the courts of South Africa.</p>
                        </section>

                        <section id="contact" class="border-t border-zinc-800/50 py-10">
                            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                                <span class="text-xs font-medium text-zinc-600 tabular-nums">15</span>
                                Contact Us
                            </h2>
                            <p>For questions about these Terms, contact us at:</p>
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
                        <a href="{{ route('privacy') }}" class="hover:text-emerald-400 transition">Privacy Policy</a>
                        <span class="text-zinc-800">·</span>
                        <a href="{{ route('refund') }}" class="hover:text-emerald-400 transition">Refund Policy</a>
                    </nav>
                    <p class="text-xs text-zinc-500">&copy; {{ date('Y') }} Finvixy by Enclivix (Pty) Ltd. All rights reserved.</p>
                </div>
            </div>
        </footer>

    </body>
</html>

