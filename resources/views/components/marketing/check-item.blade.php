@props([
    'icon' => 'check',
    'iconClass' => 'size-4 text-emerald-500 shrink-0 mt-1',
])

<li {{ $attributes->merge(['class' => 'flex items-start gap-3']) }}>
    @if ($icon === 'x-mark')
        <span class="size-4 shrink-0 mt-1 flex items-center justify-center rounded-full bg-zinc-800 text-zinc-500">
            <flux:icon name="x-mark" variant="micro" class="size-2.5" />
        </span>
    @else
        <flux:icon :name="$icon" class="{{ $iconClass }} [&>path]:stroke-2" />
    @endif
    <span>{{ $slot }}</span>
</li>
