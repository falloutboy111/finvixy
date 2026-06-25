@props(['title', 'links' => []])

<div class="mx-auto max-w-7xl px-6 lg:px-8 mb-12">
    <div class="border-b border-zinc-800/60 pb-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading level="1" class="text-4xl! font-bold! text-white">{{ $title }}</flux:heading>
                <flux:text class="mt-2 text-sm text-zinc-500">Last updated: {{ now()->format('j F Y') }}</flux:text>
            </div>
            <div class="flex items-center gap-3">
                @foreach ($links as $routeName => $label)
                    @if (! $loop->first)
                        <span class="text-zinc-800">·</span>
                    @endif
                    <a href="{{ route($routeName) }}" class="text-xs text-zinc-500 hover:text-emerald-400 transition">{{ $label }}</a>
                @endforeach
            </div>
        </div>
    </div>
</div>
