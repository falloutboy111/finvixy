<x-layouts::marketing title="Refund Policy — Finvixy">

    <main class="pt-28 pb-20 lg:pt-36 lg:pb-28">

        <x-marketing.legal-header title="Refund Policy" :links="[
            'terms' => 'Terms of Service',
            'privacy' => 'Privacy Policy',
        ]" />

        {{-- Content with Sidebar TOC --}}
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="lg:grid lg:grid-cols-[240px_1fr] lg:gap-16">

                <x-marketing.legal-toc :items="[
                    'overview'       => 'Overview',
                    'merchant'       => 'Merchant of Record',
                    'refunds'        => 'Refund Eligibility',
                    'how-to-request' => 'How to Request',
                    'cancellations'  => 'Cancellations',
                    'free-plan'      => 'Free Plan',
                    'contact'        => 'Contact Us',
                ]" />

                {{-- Article --}}
                <article class="text-zinc-300 leading-relaxed space-y-0">

                    <x-marketing.legal-section id="overview" number="01" title="Overview">
                        <p>Finvixy ("the Service") is operated by Enclivix. All paid subscriptions are processed by <strong class="text-white">Paddle</strong>, our authorised merchant of record. This means that all billing, payments, and refunds are handled by Paddle in accordance with their policies.</p>
                        <p class="mt-3">This page summarises how refunds work for Finvixy subscriptions. For the full terms, please refer to <flux:link variant="ghost" href="https://www.paddle.com/legal/buyer-terms" external rel="noopener">Paddle's Buyer Terms</flux:link> and <flux:link variant="ghost" href="https://www.paddle.com/legal/refund-policy" external rel="noopener">Paddle's Refund Policy</flux:link>.</p>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="merchant" number="02" title="Merchant of Record">
                        <p>Paddle acts as the merchant of record for all Finvixy transactions. When you subscribe to a paid plan, you are purchasing through Paddle's platform. Paddle handles:</p>
                        <ul class="mt-4 space-y-3">
                            <x-marketing.check-item>Payment processing and recurring billing</x-marketing.check-item>
                            <x-marketing.check-item>Invoicing and receipts</x-marketing.check-item>
                            <x-marketing.check-item>Sales tax and VAT compliance</x-marketing.check-item>
                            <x-marketing.check-item>Refunds and chargebacks</x-marketing.check-item>
                        </ul>
                        <p class="mt-4 text-sm text-zinc-400">You may see "Paddle" or "Paddle.com" on your bank or card statement for Finvixy charges.</p>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="refunds" number="03" title="Refund Eligibility">
                        <p>Refunds for Finvixy subscriptions are governed by <flux:link variant="ghost" href="https://www.paddle.com/legal/refund-policy" external rel="noopener">Paddle's Refund Policy</flux:link>. In summary:</p>
                        <ul class="mt-4 space-y-3">
                            <x-marketing.check-item>Paddle may issue discretionary refunds for requests submitted within <strong class="text-white">14 days</strong> of a transaction</x-marketing.check-item>
                            <x-marketing.check-item>Where local consumer protection laws grant statutory withdrawal rights (e.g. EU/EEA/UK 14-day right), those rights apply and are honoured in full</x-marketing.check-item>
                            <x-marketing.check-item>Refunds for technical or product defects are available where there is evidence of a material issue preventing access to advertised features</x-marketing.check-item>
                        </ul>
                        <flux:callout color="emerald" class="mt-5">
                            <flux:callout.text>Nothing in this policy limits any mandatory consumer rights you may have under applicable law in your territory of purchase.</flux:callout.text>
                        </flux:callout>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="how-to-request" number="04" title="How to Request a Refund">
                        <p>To request a refund, use one of the following methods:</p>
                        <ol class="mt-4 space-y-3 list-decimal list-inside text-zinc-300 marker:text-zinc-500">
                            <li>Use the <strong class="text-white">"View receipt"</strong> or <strong class="text-white">"Manage subscription"</strong> link in your transaction confirmation email from Paddle.</li>
                            <li>Visit <flux:link variant="ghost" href="https://paddle.net" external rel="noopener">paddle.net</flux:link> and select <strong class="text-white">"Request refund"</strong>.</li>
                            <li>Contact our team at <flux:link variant="ghost" href="mailto:billing@enclivix.com">billing@enclivix.com</flux:link> and we will assist you through the process.</li>
                        </ol>
                        <p class="mt-4 text-sm text-zinc-400">If eligible, refunds will be processed by Paddle using the same payment method where possible, within 14 days of the request being approved.</p>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="cancellations" number="05" title="Cancellations">
                        <p>You can cancel your subscription at any time. Your cancellation takes effect at the end of the current billing period, and you will not be charged again after that.</p>
                        <p class="mt-3">To cancel:</p>
                        <ol class="mt-4 space-y-3 list-decimal list-inside text-zinc-300 marker:text-zinc-500">
                            <li>Log in to your Finvixy account and go to <strong class="text-white">Settings → Billing</strong>.</li>
                            <li>Select <strong class="text-white">Cancel Subscription</strong> and confirm.</li>
                        </ol>
                        <p class="mt-4 text-sm text-zinc-400">Alternatively, use the subscription management link in your Paddle receipt email, or email <flux:link variant="ghost" href="mailto:billing@enclivix.com">billing@enclivix.com</flux:link> and we will cancel on your behalf.</p>
                        <p class="mt-3 text-sm text-zinc-400">After cancellation, your account reverts to the Free plan at the end of the current billing period.</p>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="free-plan" number="06" title="Free Plan">
                        <p>The Free plan has no associated cost. No charges are ever made for Free plan usage, so no refunds are applicable.</p>
                    </x-marketing.legal-section>

                    <x-marketing.legal-section id="contact" number="07" title="Contact Us">
                        <p>For billing questions or help with a refund request, contact us at:</p>
                        <div class="mt-5 rounded-xl border border-zinc-800 bg-zinc-900/50 p-5 space-y-2 text-sm">
                            <p><span class="text-zinc-500">Email:</span> <flux:link variant="ghost" href="mailto:billing@enclivix.com">billing@enclivix.com</flux:link></p>
                            <p><span class="text-zinc-500">Company:</span> <span class="text-zinc-300">Enclivix, South Africa</span></p>
                            <p><span class="text-zinc-500">Payments processed by:</span> <flux:link variant="ghost" href="https://www.paddle.com" external rel="noopener">Paddle.com</flux:link></p>
                        </div>
                    </x-marketing.legal-section>

                </article>
            </div>
        </div>
    </main>

</x-layouts::marketing>
