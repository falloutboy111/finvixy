<x-layouts::marketing>

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

                <flux:heading level="1" class="text-5xl font-bold tracking-tight text-white sm:text-7xl">
                    <span class="gradient-green-text">Scan.</span>
                    <span class="text-white">Track.</span>
                    <span class="gradient-green-text">Save.</span>
                </flux:heading>

                <flux:text class="mt-6 text-lg leading-8 text-gray-400 max-w-2xl mx-auto">
                    Snap a photo of your receipt via WhatsApp or upload it. Our AI extracts every detail — vendor, amount, line items — and syncs it all to Google Drive. Your expenses, organised.
                </flux:text>

                <div class="mt-10 flex items-center justify-center gap-4">
                    @if (Route::has('register'))
                        <flux:button href="{{ route('register') }}" variant="primary" icon:trailing="arrow-right" class="h-12 px-6 text-base">
                            Start Free
                        </flux:button>
                    @endif
                    <flux:button href="#features" variant="outline" class="h-12 px-6 text-base bg-transparent! border-emerald-500/20! text-emerald-400! hover:border-emerald-500/40! hover:bg-emerald-500/5!">
                        See How It Works
                    </flux:button>
                </div>
            </div>
        </div>
    </section>

    {{-- Features Section --}}
    <section id="features" class="relative py-20 lg:py-32">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="text-center mb-16">
                <flux:heading level="2" class="text-3xl font-bold text-white sm:text-4xl">Everything you need to track expenses</flux:heading>
                <flux:text class="mt-4 text-lg text-gray-400">No spreadsheets. No manual entry. Just scan and go.</flux:text>
            </div>

            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    [
                        'icon' => 'chat-bubble-oval-left-ellipsis',
                        'title' => 'WhatsApp Scanning',
                        'description' => 'Send a photo of your receipt via WhatsApp. Our AI extracts vendor, amount, and every line item — instantly.',
                    ],
                    [
                        'icon' => 'camera',
                        'title' => 'Smart Upload',
                        'description' => 'Upload receipts from your browser. Photos, camera capture, and PDFs — all powered by AI-driven OCR.',
                    ],
                    [
                        'icon' => 'cloud-arrow-up',
                        'title' => 'Google Drive Sync',
                        'description' => 'Receipts auto-sync to <strong class="text-gray-300">your own Google Drive</strong>, organised by category. Share a folder with your accountant in one click.',
                    ],
                    [
                        'icon' => 'chart-bar',
                        'title' => 'Expense Insights',
                        'description' => 'See where your money goes. Spending breakdowns by category, vendor trends, and smart recommendations.',
                    ],
                ] as $feature)
                    <div class="glow-card rounded-2xl p-6">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-emerald-500/10 mb-5">
                            <flux:icon :name="$feature['icon']" class="size-6 text-emerald-400" />
                        </div>
                        <flux:heading level="3" class="text-lg font-semibold text-white mb-2">{{ $feature['title'] }}</flux:heading>
                        <flux:text class="text-sm text-gray-400 leading-relaxed">
                            {!! $feature['description'] !!}
                        </flux:text>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How It Works --}}
    <section class="relative py-20 lg:py-32 border-t border-emerald-500/5">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="text-center mb-16">
                <flux:heading level="2" class="text-3xl font-bold text-white sm:text-4xl">How it works</flux:heading>
                <flux:text class="mt-4 text-lg text-gray-400">Three steps to expense freedom.</flux:text>
            </div>

            <div class="grid gap-12 md:grid-cols-3">
                @foreach ([
                    ['step' => '1', 'title' => 'Scan', 'description' => "Send a receipt photo via WhatsApp or upload through the app. That's it."],
                    ['step' => '2', 'title' => 'Extract', 'description' => 'Our AI reads the receipt, extracts vendor details, line items, totals, and categorises it.'],
                    ['step' => '3', 'title' => 'Organise', 'description' => 'Everything lands in your dashboard and syncs to Google Drive — neatly filed by date and category.'],
                ] as $step)
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 mb-6 glow-sm">
                            <span class="text-2xl font-bold gradient-green-text">{{ $step['step'] }}</span>
                        </div>
                        <flux:heading level="3" class="text-lg font-semibold text-white mb-2">{{ $step['title'] }}</flux:heading>
                        <flux:text class="text-base text-gray-400">{{ $step['description'] }}</flux:text>
                    </div>
                @endforeach
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
                        <flux:icon name="shield-check" class="size-3.5 [&>path]:stroke-2" />
                        Your data stays yours
                    </div>
                    <flux:heading level="2" class="text-3xl font-bold text-white sm:text-4xl">Your data. Your control.</flux:heading>
                    <flux:text class="mt-4 text-lg text-gray-400 leading-relaxed">
                        We don't store your receipts on our servers long-term. Everything syncs straight to <strong class="text-gray-300">your own Google Drive</strong> — organised by category, accessible from anywhere. We only help you organise your data automatically.
                    </flux:text>
                    <ul class="mt-8 space-y-4 text-gray-400">
                        <x-marketing.check-item icon-class="size-5 text-emerald-500 shrink-0 mt-0.5">
                            <strong class="text-gray-300">You own your storage</strong> — receipts live in your Google Drive, not ours
                        </x-marketing.check-item>
                        <x-marketing.check-item icon-class="size-5 text-emerald-500 shrink-0 mt-0.5">
                            <strong class="text-gray-300">Category folders</strong> — auto-organised by Travel, Food, Office, and more
                        </x-marketing.check-item>
                        <x-marketing.check-item icon-class="size-5 text-emerald-500 shrink-0 mt-0.5">
                            <strong class="text-gray-300">Limited permissions</strong> — we can only write to our own app folder, nothing else
                        </x-marketing.check-item>
                        <x-marketing.check-item icon-class="size-5 text-emerald-500 shrink-0 mt-0.5">
                            <strong class="text-gray-300">Share with your accountant</strong> — just share the Drive folder, done
                        </x-marketing.check-item>
                    </ul>
                </div>
                <div class="flex items-center justify-center">
                    <div class="relative w-full max-w-sm">
                        <div class="glow-card rounded-2xl p-6 space-y-3">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="flex items-center justify-center size-10 rounded-xl bg-emerald-500/10">
                                    <flux:icon name="folder" class="size-5 text-emerald-400" />
                                </div>
                                <div>
                                    <flux:text class="text-sm font-medium text-white">MyBusiness-finvixy</flux:text>
                                    <flux:text class="text-[11px] text-zinc-500">Google Drive</flux:text>
                                </div>
                            </div>
                            @foreach (['Travel', 'Food & Dining', 'Office Supplies', 'Transport', 'Utilities'] as $folder)
                                <div class="flex items-center gap-3 py-2 px-3 rounded-lg bg-white/[0.02]">
                                    <flux:icon name="folder" class="size-4 text-emerald-400/60" />
                                    <span class="text-xs text-gray-400">{{ $folder }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Pricing Teaser --}}
    <section id="pricing" class="relative py-20 lg:py-28 border-t border-emerald-500/5">
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-emerald-500/5 rounded-full blur-3xl"></div>
        </div>
        <div class="relative mx-auto max-w-4xl px-6 lg:px-8 text-center">
            <flux:heading level="2" class="text-3xl font-bold text-white sm:text-4xl">Simple, transparent pricing</flux:heading>
            <flux:text class="mt-4 text-lg text-gray-400">Start free. Upgrade when you're ready. No surprise fees.</flux:text>

            <div class="mt-10 flex flex-wrap items-center justify-center gap-3">
                @foreach ([
                    ['Free', 'R0'],
                    ['Starter', 'R99'],
                    ['Professional', 'R189'],
                    ['Business', 'R349'],
                ] as [$plan, $price])
                    <div class="{{ $plan === 'Professional' ? 'border-emerald-500/40 bg-emerald-500/10 text-white' : 'border-zinc-800 bg-zinc-900/50 text-zinc-400' }} rounded-xl border px-5 py-3 text-sm">
                        <span class="font-medium {{ $plan === 'Professional' ? 'text-white' : 'text-zinc-300' }}">{{ $plan }}</span>
                        <span class="ml-2 text-xs {{ $plan === 'Professional' ? 'text-emerald-400' : 'text-zinc-500' }}">{{ $price }}/mo</span>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                <flux:button href="{{ route('pricing') }}" variant="primary" icon:trailing="arrow-right" class="px-7">
                    View All Plans
                </flux:button>
            </div>
        </div>
    </section>

</x-layouts::marketing>
