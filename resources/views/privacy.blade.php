<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Privacy Policy — Finvixy</title>
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
                <h1 class="text-4xl font-bold text-white mb-2">Privacy Policy</h1>
                <p class="text-sm text-zinc-500 mb-10">Last updated: {{ now()->format('j F Y') }}</p>

                <div class="prose prose-invert prose-emerald max-w-none space-y-8 text-gray-300 leading-relaxed">

                    <section>
                        <h2 class="text-xl font-semibold text-white">1. Introduction</h2>
                        <p>Finvixy ("we", "our", "us") is a product of Enclivix (Pty) Ltd, a South African company. This Privacy Policy explains how we collect, use, store, and protect your information when you use our receipt scanning and expense tracking service at <a href="https://finvixy.co.za" class="text-emerald-400 hover:underline">finvixy.co.za</a>.</p>
                        <p>By using Finvixy, you agree to the collection and use of information in accordance with this policy.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white">2. Information We Collect</h2>
                        <h3 class="text-lg font-medium text-zinc-300">Account Information</h3>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Name, email address, and WhatsApp number (provided at registration)</li>
                            <li>Organisation or business name</li>
                            <li>Account credentials (passwords are hashed and never stored in plain text)</li>
                        </ul>

                        <h3 class="text-lg font-medium text-zinc-300 mt-4">Receipt and Expense Data</h3>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Receipt images and PDF documents you upload or send via WhatsApp</li>
                            <li>Extracted data: vendor names, dates, amounts, line items, and categories</li>
                            <li>This data is processed by AI (AWS Textract and Amazon Bedrock) to extract receipt information</li>
                        </ul>

                        <h3 class="text-lg font-medium text-zinc-300 mt-4">Google Account Data</h3>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>If you connect Google Drive, we request access to the <code class="text-emerald-400">drive.file</code> scope only</li>
                            <li>This allows us to <strong>create and manage files and folders that Finvixy creates</strong> in your Google Drive — nothing else</li>
                            <li>We <strong>cannot</strong> read, modify, or delete any other files in your Google Drive</li>
                            <li>We store your Google OAuth token (encrypted) to maintain the connection</li>
                            <li>Your Google email address is stored to identify the connected account</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white">3. How We Use Your Information</h2>
                        <ul class="list-disc pl-5 space-y-1">
                            <li><strong>Receipt processing:</strong> We use AI services (AWS Textract and Amazon Bedrock) to extract text and categorise your receipts</li>
                            <li><strong>Google Drive sync:</strong> Receipts are automatically organised into category folders in your own Google Drive. We only write to a dedicated Finvixy folder — your data stays in your Drive, under your control</li>
                            <li><strong>WhatsApp scanning:</strong> When you send a receipt photo via WhatsApp, we process it and return the results to you</li>
                            <li><strong>Reports and insights:</strong> We generate spending charts and analytics from your expense data</li>
                            <li><strong>Account notifications:</strong> We send transactional emails (verification, password reset) via Postmark</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white">4. Your Data, Your Control</h2>
                        <p><strong>You own your data.</strong> Finvixy is designed as an organiser — we help you structure and store your receipts, but the data belongs to you.</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li><strong>Google Drive:</strong> All synced receipts live in your own Google Drive. If you disconnect Finvixy, the files remain in your Drive</li>
                            <li><strong>Export:</strong> Your receipts are always accessible through our app or directly in your Google Drive</li>
                            <li><strong>Deletion:</strong> You can request full deletion of your account and all associated data by contacting us</li>
                            <li><strong>Disconnect:</strong> You can disconnect Google Drive at any time from Settings → Connected Accounts</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white">5. Data Storage and Security</h2>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Receipt images are stored in encrypted Amazon S3 buckets (EU region)</li>
                            <li>Application data is stored in a secured database with encryption at rest</li>
                            <li>Google OAuth credentials are encrypted using Laravel's encryption (AES-256-CBC)</li>
                            <li>All connections use HTTPS/TLS encryption in transit</li>
                            <li>We implement two-factor authentication (2FA) for additional account security</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white">6. Third-Party Services</h2>
                        <p>We use the following third-party services to operate Finvixy:</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li><strong>Amazon Web Services (AWS):</strong> S3 for file storage, Textract for OCR, Bedrock for AI processing</li>
                            <li><strong>Google APIs:</strong> Google Drive API (drive.file scope only) for receipt sync</li>
                            <li><strong>Meta (WhatsApp Business API):</strong> For receiving and responding to WhatsApp messages</li>
                            <li><strong>Postmark:</strong> For transactional email delivery</li>
                        </ul>
                        <p>Each of these services has their own privacy policies. We encourage you to review them.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white">7. Google API Services User Data Policy</h2>
                        <p>Finvixy's use and transfer to any other app of information received from Google APIs will adhere to the <a href="https://developers.google.com/terms/api-services-user-data-policy" class="text-emerald-400 hover:underline" target="_blank" rel="noopener">Google API Services User Data Policy</a>, including the Limited Use requirements.</p>
                        <p>Specifically:</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>We only request the <code class="text-emerald-400">drive.file</code> scope — the minimum needed to create and manage receipt files in your Drive</li>
                            <li>We do not use Google data for advertising purposes</li>
                            <li>We do not transfer Google data to third parties except as needed to provide the service</li>
                            <li>We do not use Google data to develop a surveillance tool or product</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white">8. Data Retention</h2>
                        <p>We retain your data for as long as your account is active. If you delete your account:</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>All personal data and expense records are permanently deleted from our systems</li>
                            <li>Receipt images in S3 are deleted</li>
                            <li>Files already synced to your Google Drive remain there (they are in your account)</li>
                            <li>Google OAuth tokens are revoked and deleted</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white">9. Cookies</h2>
                        <p>We use essential session cookies to maintain your login state. We do not use third-party tracking cookies or advertising cookies.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white">10. Children's Privacy</h2>
                        <p>Finvixy is not intended for use by children under 18. We do not knowingly collect personal information from children.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white">11. Changes to This Policy</h2>
                        <p>We may update this Privacy Policy from time to time. We will notify you of significant changes by email or in-app notification. Continued use of Finvixy after changes constitutes acceptance.</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-white">12. Contact Us</h2>
                        <p>If you have questions about this Privacy Policy or your data, contact us at:</p>
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
