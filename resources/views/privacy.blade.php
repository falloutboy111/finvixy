<x-layouts::marketing title="Privacy Policy — Finvixy">

    <main class="pt-28 pb-20 lg:pt-36 lg:pb-28">

        <x-marketing.legal-header title="Privacy Policy" :links="[
            'terms' => 'Terms of Service',
            'refund' => 'Refund Policy',
        ]" />

        {{-- Content with Sidebar TOC --}}
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="lg:grid lg:grid-cols-[240px_1fr] lg:gap-16">

                <x-marketing.legal-toc :items="[
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
                ]" />

                {{-- Article --}}
                <article class="text-zinc-300 leading-relaxed">

                    <x-marketing.legal-section id="introduction" number="01" title="Introduction">
                        <p>Finvixy ("we", "our", "us") is a product of Enclivix, a South African company. This Privacy Policy explains how we collect, use, store, and protect your information when you use our receipt scanning and expense tracking service at <flux:link variant="ghost" href="https://finvixy.co.za">finvixy.co.za</flux:link>.</p>
                        <p class="mt-3">By using Finvixy, you agree to the collection and use of information in accordance with this policy.</p>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="info-collected" number="02" title="Information We Collect" heading-margin="mb-6">
                        <flux:heading level="3" class="text-base! font-medium! text-zinc-200 mb-3">Account Information</flux:heading>
                        <ul class="space-y-2.5 mb-6">
                            <x-marketing.check-item>Name, email address, and WhatsApp number (provided at registration)</x-marketing.check-item>
                            <x-marketing.check-item>Organisation or business name</x-marketing.check-item>
                            <x-marketing.check-item>Account credentials (passwords are hashed and never stored in plain text)</x-marketing.check-item>
                        </ul>

                        <flux:heading level="3" class="text-base! font-medium! text-zinc-200 mb-3">Receipt and Expense Data</flux:heading>
                        <ul class="space-y-2.5 mb-6">
                            <x-marketing.check-item>Receipt images and PDF documents you upload or send via WhatsApp</x-marketing.check-item>
                            <x-marketing.check-item>Extracted data: vendor names, dates, amounts, line items, and categories</x-marketing.check-item>
                            <x-marketing.check-item>This data is processed by AI (AWS Textract and Amazon Bedrock) to extract receipt information</x-marketing.check-item>
                        </ul>

                        <flux:heading level="3" class="text-base! font-medium! text-zinc-200 mb-3">Google Account Data</flux:heading>
                        <ul class="space-y-2.5">
                            <x-marketing.check-item>If you connect Google Drive, we request access to the <code class="text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded text-xs">drive.file</code> scope only</x-marketing.check-item>
                            <x-marketing.check-item>This allows us to <strong class="text-white">create and manage files and folders that Finvixy creates</strong> in your Google Drive — nothing else</x-marketing.check-item>
                            <x-marketing.check-item>We <strong class="text-white">cannot</strong> read, modify, or delete any other files in your Google Drive</x-marketing.check-item>
                            <x-marketing.check-item>We store your Google OAuth token (encrypted) to maintain the connection</x-marketing.check-item>
                            <x-marketing.check-item>Your Google email address is stored to identify the connected account</x-marketing.check-item>
                        </ul>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="how-we-use" number="03" title="How We Use Your Information">
                        <ul class="space-y-3">
                            <x-marketing.check-item><strong class="text-white">Receipt processing:</strong> We use AI services (AWS Textract and Amazon Bedrock) to extract text and categorise your receipts</x-marketing.check-item>
                            <x-marketing.check-item><strong class="text-white">Google Drive sync:</strong> Receipts are automatically organised into category folders in your own Google Drive. We only write to a dedicated Finvixy folder — your data stays in your Drive, under your control</x-marketing.check-item>
                            <x-marketing.check-item><strong class="text-white">WhatsApp scanning:</strong> When you send a receipt photo via WhatsApp, we process it and return the results to you</x-marketing.check-item>
                            <x-marketing.check-item><strong class="text-white">Reports and insights:</strong> We generate spending charts and analytics from your expense data</x-marketing.check-item>
                            <x-marketing.check-item><strong class="text-white">Account notifications:</strong> We send transactional emails (verification, password reset) via Postmark</x-marketing.check-item>
                        </ul>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="your-control" number="04" title="Your Data, Your Control">
                        <p><strong class="text-white">You own your data.</strong> Finvixy is designed as an organiser — we help you structure and store your receipts, but the data belongs to you.</p>
                        <ul class="mt-4 space-y-3">
                            <x-marketing.check-item><strong class="text-white">Google Drive:</strong> All synced receipts live in your own Google Drive. If you disconnect Finvixy, the files remain in your Drive</x-marketing.check-item>
                            <x-marketing.check-item><strong class="text-white">Export:</strong> Your receipts are always accessible through our app or directly in your Google Drive</x-marketing.check-item>
                            <x-marketing.check-item><strong class="text-white">Deletion:</strong> You can request full deletion of your account and all associated data by contacting us</x-marketing.check-item>
                            <x-marketing.check-item><strong class="text-white">Disconnect:</strong> You can disconnect Google Drive at any time from Settings → Connected Accounts</x-marketing.check-item>
                        </ul>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="security" number="05" title="Data Storage and Security">
                        <ul class="space-y-3">
                            <x-marketing.check-item>Receipt images are stored in encrypted Amazon S3 buckets (EU region)</x-marketing.check-item>
                            <x-marketing.check-item>Application data is stored in a secured database with encryption at rest</x-marketing.check-item>
                            <x-marketing.check-item>Google OAuth credentials are encrypted using Laravel's encryption (AES-256-CBC)</x-marketing.check-item>
                            <x-marketing.check-item>All connections use HTTPS/TLS encryption in transit</x-marketing.check-item>
                            <x-marketing.check-item>We implement two-factor authentication (2FA) for additional account security</x-marketing.check-item>
                        </ul>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="third-parties" number="06" title="Third-Party Services">
                        <p class="mb-5">We use the following third-party services to operate Finvixy:</p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ([
                                ['name' => 'Amazon Web Services', 'desc' => 'S3 storage, Textract OCR, Bedrock AI processing'],
                                ['name' => 'Google APIs', 'desc' => 'Google Drive sync (drive.file scope only)'],
                                ['name' => 'Meta / WhatsApp Business', 'desc' => 'Receiving and sending WhatsApp messages'],
                                ['name' => 'Postmark', 'desc' => 'Transactional email delivery'],
                            ] as $service)
                                <div class="rounded-xl border border-zinc-800/60 bg-zinc-900/30 p-4">
                                    <flux:text class="text-sm font-medium text-zinc-200">{{ $service['name'] }}</flux:text>
                                    <flux:text class="mt-1 text-xs text-zinc-500">{{ $service['desc'] }}</flux:text>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-4 text-sm text-zinc-400">Each of these services has their own privacy policies. We encourage you to review them.</p>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="google-policy" number="07" title="Google API Services User Data Policy">
                        <p>Finvixy's use and transfer to any other app of information received from Google APIs will adhere to the <flux:link variant="ghost" href="https://developers.google.com/terms/api-services-user-data-policy" external rel="noopener">Google API Services User Data Policy</flux:link>, including the Limited Use requirements.</p>
                        <ul class="mt-4 space-y-3">
                            <x-marketing.check-item>We only request the <code class="text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded text-xs">drive.file</code> scope — the minimum needed</x-marketing.check-item>
                            <x-marketing.check-item>We do not use Google data for advertising purposes</x-marketing.check-item>
                            <x-marketing.check-item>We do not transfer Google data to third parties except as needed to provide the service</x-marketing.check-item>
                            <x-marketing.check-item>We do not use Google data to develop a surveillance tool or product</x-marketing.check-item>
                        </ul>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="retention" number="08" title="Data Retention">
                        <p>We retain your data for as long as your account is active. If you delete your account:</p>
                        <ul class="mt-4 space-y-3">
                            <x-marketing.check-item>All personal data and expense records are permanently deleted from our systems</x-marketing.check-item>
                            <x-marketing.check-item>Receipt images in S3 are deleted</x-marketing.check-item>
                            <x-marketing.check-item>Files already synced to your Google Drive remain there (they are in your account)</x-marketing.check-item>
                            <x-marketing.check-item>Google OAuth tokens are revoked and deleted</x-marketing.check-item>
                        </ul>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="cookies" number="09" title="Cookies">
                        <p>We use essential session cookies to maintain your login state. We do not use third-party tracking cookies or advertising cookies.</p>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="children" number="10" title="Children's Privacy">
                        <p>Finvixy is not intended for use by children under 18. We do not knowingly collect personal information from children.</p>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="changes" number="11" title="Policy Changes">
                        <p>We may update this Privacy Policy from time to time. We will notify you of significant changes by email or in-app notification. Continued use of Finvixy after changes constitutes acceptance.</p>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="contact" number="12" title="Contact Us">
                        <p>If you have questions about this Privacy Policy or your data, contact us at:</p>
                        <div class="mt-5 rounded-xl border border-zinc-800 bg-zinc-900/50 p-5 space-y-2 text-sm">
                            <p><span class="text-zinc-500">Email:</span> <flux:link variant="ghost" href="mailto:info@enclivix.com">info@enclivix.com</flux:link></p>
                            <p><span class="text-zinc-500">Company:</span> <span class="text-zinc-300">Enclivix, South Africa</span></p>
                        </div>
                    </x-marketing.legal-section>

                </article>
            </div>
        </div>
    </main>

</x-layouts::marketing>
