@props(['items'])

<aside class="hidden lg:block">
    <div class="sticky top-28">
        <flux:text class="text-xs font-semibold uppercase tracking-widest text-zinc-600 mb-5">On this page</flux:text>
        <nav class="space-y-0.5">
            @foreach ($items as $id => $label)
                <a href="#{{ $id }}" class="group flex items-center gap-2.5 rounded-lg py-1.5 px-3 text-sm text-zinc-500 transition hover:text-emerald-400 hover:bg-emerald-500/5">
                    <span class="h-px w-3 bg-zinc-700 group-hover:bg-emerald-500/50 transition shrink-0"></span>
                    {{ $label }}
                </a>
            @endforeach
        </nav>
    </div>
</aside>
