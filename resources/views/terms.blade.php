<x-layouts::marketing title="Terms of Service — Finvixy">

    <main class="pt-28 pb-20 lg:pt-36 lg:pb-28">

        <x-marketing.legal-header title="Terms of Service" :links="[
            'privacy' => 'Privacy Policy',
            'refund' => 'Refund Policy',
        ]" />

        {{-- Content with Sidebar TOC --}}
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="lg:grid lg:grid-cols-[240px_1fr] lg:gap-16">

                <x-marketing.legal-toc :items="[
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
                ]" />

                {{-- Article --}}
                <article class="text-zinc-300 leading-relaxed">

                    <x-marketing.legal-section id="agreement" number="01" title="Agreement to Terms">
                        <p>By accessing or using Finvixy ("the Service"), operated by Enclivix ("we", "our", "us"), you agree to be bound by these Terms of Service. If you do not agree, please do not use the Service.</p>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="description" number="02" title="Description of Service">
                        <p>Finvixy is a receipt scanning and expense tracking platform that:</p>
                        <ul class="mt-4 space-y-2.5">
                            <x-marketing.check-item>Accepts receipt images via web upload or WhatsApp</x-marketing.check-item>
                            <x-marketing.check-item>Uses AI-powered OCR to extract receipt data (vendor, amounts, line items, dates)</x-marketing.check-item>
                            <x-marketing.check-item>Automatically categorises and organises expenses</x-marketing.check-item>
                            <x-marketing.check-item>Optionally syncs receipts to your personal Google Drive in organised category folders</x-marketing.check-item>
                            <x-marketing.check-item>Provides spending reports and analytics</x-marketing.check-item>
                        </ul>
                        <flux:callout color="emerald" class="mt-5">
                            <flux:callout.heading>Your data stays yours.</flux:callout.heading>
                            <flux:callout.text>We act as an organiser and processor — all receipt files synced to Google Drive are stored in your own Drive account, under your control.</flux:callout.text>
                        </flux:callout>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="registration" number="03" title="Account Registration">
                        <ul class="space-y-3">
                            <x-marketing.check-item>You must provide accurate information during registration, including your name, email, organisation name, and WhatsApp number</x-marketing.check-item>
                            <x-marketing.check-item>You are responsible for maintaining the security of your account credentials</x-marketing.check-item>
                            <x-marketing.check-item>You must be at least 18 years old to create an account</x-marketing.check-item>
                            <x-marketing.check-item>One person or entity per account — do not share account access</x-marketing.check-item>
                        </ul>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="google-drive" number="04" title="Google Drive Integration">
                        <p>When you connect your Google Drive account:</p>
                        <ul class="mt-4 space-y-3">
                            <x-marketing.check-item>We request the <code class="text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded text-xs">drive.file</code> scope, which only allows us to manage files and folders that Finvixy itself creates</x-marketing.check-item>
                            <x-marketing.check-item>We <strong class="text-white">cannot access, read, or modify</strong> any existing files in your Google Drive</x-marketing.check-item>
                            <x-marketing.check-item>Receipts are organised into category folders (e.g., <code class="text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded text-xs">YourBusiness-finvixy/Travel/</code>)</x-marketing.check-item>
                            <x-marketing.check-item>You can disconnect Google Drive at any time; files already synced remain in your Drive</x-marketing.check-item>
                            <x-marketing.check-item>Our use of Google APIs complies with the <flux:link variant="ghost" href="https://developers.google.com/terms/api-services-user-data-policy" external rel="noopener">Google API Services User Data Policy</flux:link></x-marketing.check-item>
                        </ul>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="whatsapp" number="05" title="WhatsApp Integration">
                        <ul class="space-y-3">
                            <x-marketing.check-item>You may send receipt images to our WhatsApp Business number for processing</x-marketing.check-item>
                            <x-marketing.check-item>By sending messages, you consent to us processing the images through our AI system</x-marketing.check-item>
                            <x-marketing.check-item>We only process image and document messages — other message types are ignored</x-marketing.check-item>
                            <x-marketing.check-item>Standard WhatsApp and mobile data charges from your carrier may apply</x-marketing.check-item>
                        </ul>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="plans-pricing" number="06" title="Plans and Pricing">
                        <ul class="space-y-3">
                            <x-marketing.check-item>Finvixy offers free and paid plans with different monthly receipt limits</x-marketing.check-item>
                            <x-marketing.check-item>Pricing is in South African Rand (ZAR) and subject to change with reasonable notice</x-marketing.check-item>
                            <x-marketing.check-item>Free plan users receive 10 receipt scans per month</x-marketing.check-item>
                            <x-marketing.check-item>Exceeding your plan limit will pause scanning until the next billing period or until you upgrade</x-marketing.check-item>
                        </ul>
                        <p class="mt-4 text-sm text-zinc-400">View full plan details on our <flux:link variant="ghost" href="{{ route('pricing') }}">Pricing page</flux:link>. Refunds are governed by our <flux:link variant="ghost" href="{{ route('refund') }}">Refund Policy</flux:link>.</p>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="ownership" number="07" title="Data Ownership">
                        <p><strong class="text-white">You retain full ownership of all data you upload to Finvixy.</strong></p>
                        <ul class="mt-4 space-y-3">
                            <x-marketing.check-item>Receipt images and extracted data belong to you</x-marketing.check-item>
                            <x-marketing.check-item>Files synced to Google Drive are stored in your personal Drive account</x-marketing.check-item>
                            <x-marketing.check-item>We do not claim any intellectual property rights over your data</x-marketing.check-item>
                            <x-marketing.check-item>We will not sell, share, or use your data for advertising or marketing purposes</x-marketing.check-item>
                        </ul>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="acceptable-use" number="08" title="Acceptable Use">
                        <p>You agree not to:</p>
                        <ul class="mt-4 space-y-3">
                            <x-marketing.check-item icon="x-mark">Use the Service for any unlawful purpose</x-marketing.check-item>
                            <x-marketing.check-item icon="x-mark">Upload content that is harmful, fraudulent, or violates any law</x-marketing.check-item>
                            <x-marketing.check-item icon="x-mark">Attempt to circumvent plan limits or abuse the system</x-marketing.check-item>
                            <x-marketing.check-item icon="x-mark">Reverse-engineer, decompile, or attempt to extract the source code of the Service</x-marketing.check-item>
                            <x-marketing.check-item icon="x-mark">Use automated scripts or bots to access the Service</x-marketing.check-item>
                        </ul>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="ai-disclaimer" number="09" title="AI Processing Disclaimer">
                        <p>Receipt data extraction is performed by AI and may not always be 100% accurate. You should review extracted data before relying on it for financial, tax, or legal purposes. Finvixy is not a substitute for professional accounting or tax advice.</p>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="availability" number="10" title="Service Availability">
                        <p>We strive to maintain high availability but do not guarantee uninterrupted service. We may perform maintenance, updates, or experience outages. We will make reasonable efforts to notify users of planned downtime.</p>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="liability" number="11" title="Limitation of Liability">
                        <p>To the fullest extent permitted by South African law, Enclivix shall not be liable for any indirect, incidental, special, or consequential damages arising from your use of the Service, including but not limited to loss of data, revenue, or business opportunities.</p>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="termination" number="12" title="Account Termination">
                        <ul class="space-y-3">
                            <x-marketing.check-item>You may delete your account at any time from Settings</x-marketing.check-item>
                            <x-marketing.check-item>We may suspend or terminate accounts that violate these Terms</x-marketing.check-item>
                            <x-marketing.check-item>Upon termination, your data will be deleted as described in our <flux:link variant="ghost" href="{{ route('privacy') }}">Privacy Policy</flux:link></x-marketing.check-item>
                        </ul>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="changes" number="13" title="Changes to Terms">
                        <p>We may update these Terms from time to time. We will notify you of material changes via email or in-app notification. Continued use of the Service after changes constitutes acceptance of the updated Terms.</p>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="governing-law" number="14" title="Governing Law">
                        <p>These Terms are governed by and construed in accordance with the laws of the Republic of South Africa. Any disputes shall be resolved in the courts of South Africa.</p>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="contact" number="15" title="Contact Us">
                        <p>For questions about these Terms, contact us at:</p>
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
