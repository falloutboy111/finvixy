<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Terms of Service — Finvixy</title>
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

        <main class="pt-32 pb-20 lg:pt-40 lg:pb-28">
            <div class="mx-auto max-w-3xl px-6 lg:px-8">
                <h1 class="text-4xl font-bold text-white mb-2">Terms of Service</h1>
                <p class="text-sm text-zinc-500 mb-10">Last updated: {{ now()->format('j F Y') }}</p>

                <div class="prose prose-invert prose-emerald max-w-none space-y-8 text-gray-300 leading-relaxed">

                    <section>
                        <h2 class="text-xl font-semibold text-white">1. Agreement to Terms</h2>
                        <p>By accessing or using Finvixy ("the Service"), operated by Enclivix (Pty) Ltd ("we", "our", "us"), you agree to be bound by these Terms of Service. If you do not agree, please do not use the Service.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white">2. Description of Service</h2>
                        <p>Finvixy is a receipt scanning and expense tracking platform that:</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Accepts receipt images via web upload or WhatsApp</li>
                            <li>Uses AI-powered OCR to extract receipt data (vendor, amounts, line items, dates)</li>
                            <li>Automatically categorises and organises expenses</li>
                            <li>Optionally syncs receipts to your personal Google Drive in organised category folders</li>
                            <li>Provides spending reports and analytics</li>
                        </ul>
                        <p><strong>Your data stays yours.</strong> We act as an organiser and processor — all receipt files synced to Google Drive are stored in your own Drive account, under your control.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white">3. Account Registration</h2>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>You must provide accurate information during registration, including your name, email, organisation name, and WhatsApp number</li>
                            <li>You are responsible for maintaining the security of your account credentials</li>
                            <li>You must be at least 18 years old to create an account</li>
                            <li>One person or entity per account — do not share account access</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white">4. Google Drive Integration</h2>
                        <p>When you connect your Google Drive account:</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>We request the <code class="text-emerald-400">drive.file</code> scope, which only allows us to manage files and folders that Finvixy itself creates</li>
                            <li>We <strong>cannot access, read, or modify</strong> any existing files in your Google Drive</li>
                            <li>Receipts are organised into category folders within a dedicated Finvixy folder (e.g., <code class="text-emerald-400">YourBusiness-finvixy/Travel/</code>)</li>
                            <li>You can disconnect Google Drive at any time; files already synced remain in your Drive</li>
                            <li>Our use of Google APIs complies with the <a href="https://developers.google.com/terms/api-services-user-data-policy" class="text-emerald-400 hover:underline" target="_blank" rel="noopener">Google API Services User Data Policy</a></li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white">5. WhatsApp Integration</h2>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>You may send receipt images to our WhatsApp Business number for processing</li>
                            <li>By sending messages, you consent to us processing the images through our AI system</li>
                            <li>We only process image and document messages — other message types are ignored</li>
                            <li>Standard WhatsApp and mobile data charges from your carrier may apply</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white">6. Plans and Pricing</h2>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Finvixy offers free and paid plans with different monthly receipt limits</li>
                            <li>Pricing is in South African Rand (ZAR) and is subject to change with reasonable notice</li>
                            <li>Free plan users receive 10 receipt scans per month</li>
                            <li>Exceeding your plan limit will pause scanning until the next billing period or until you upgrade</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white">7. Data Ownership</h2>
                        <p><strong>You retain full ownership of all data you upload to Finvixy.</strong></p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Receipt images and extracted data belong to you</li>
                            <li>Files synced to Google Drive are stored in your personal Drive account</li>
                            <li>We do not claim any intellectual property rights over your data</li>
                            <li>We will not sell, share, or use your data for advertising or marketing purposes</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white">8. Acceptable Use</h2>
                        <p>You agree not to:</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Use the Service for any unlawful purpose</li>
                            <li>Upload content that is harmful, fraudulent, or violates any law</li>
                            <li>Attempt to circumvent plan limits or abuse the system</li>
                            <li>Reverse-engineer, decompile, or attempt to extract the source code of the Service</li>
                            <li>Use automated scripts or bots to access the Service</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white">9. AI Processing Disclaimer</h2>
                        <p>Receipt data extraction is performed by AI and may not always be 100% accurate. You should review extracted data before relying on it for financial, tax, or legal purposes. Finvixy is not a substitute for professional accounting or tax advice.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white">10. Service Availability</h2>
                        <p>We strive to maintain high availability but do not guarantee uninterrupted service. We may perform maintenance, updates, or experience outages. We will make reasonable efforts to notify users of planned downtime.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white">11. Limitation of Liability</h2>
                        <p>To the fullest extent permitted by South African law, Enclivix (Pty) Ltd shall not be liable for any indirect, incidental, special, or consequential damages arising from your use of the Service, including but not limited to loss of data, revenue, or business opportunities.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white">12. Account Termination</h2>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>You may delete your account at any time from Settings</li>
                            <li>We may suspend or terminate accounts that violate these Terms</li>
                            <li>Upon termination, your data will be deleted as described in our <a href="{{ route('privacy') }}" class="text-emerald-400 hover:underline">Privacy Policy</a></li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white">13. Changes to Terms</h2>
                        <p>We may update these Terms from time to time. We will notify you of material changes via email or in-app notification. Continued use of the Service after changes constitutes acceptance of the updated Terms.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white">14. Governing Law</h2>
                        <p>These Terms are governed by and construed in accordance with the laws of the Republic of South Africa. Any disputes shall be resolved in the courts of South Africa.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white">15. Contact Us</h2>
                        <p>For questions about these Terms, contact us at:</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Email: <a href="mailto:info@enclivix.com" class="text-emerald-400 hover:underline">info@enclivix.com</a></li>
                            <li>Company: Enclivix (Pty) Ltd, South Africa</li>
                        </ul>
                    </section>
                </div>
            </div>
        </main>

        {{-- Footer --}}
        <footer class="border-t border-emerald-500/10 py-8">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="flex flex-col items-center justify-between gap-4 md:flex-row">
                    <div class="flex items-center gap-2">
                        <x-app-logo-icon class="size-6" />
                        <x-finvixy-wordmark variant="dark" size="sm" />
                    </div>
                    <div class="flex items-center gap-6 text-xs text-gray-500">
                        <a href="{{ route('privacy') }}" class="hover:text-emerald-400 transition">Privacy Policy</a>
                        <a href="{{ route('terms') }}" class="hover:text-emerald-400 transition">Terms of Service</a>
                    </div>
                    <p class="text-xs text-gray-500">&copy; {{ date('Y') }} Finvixy by Enclivix (Pty) Ltd. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </body>
</html>
