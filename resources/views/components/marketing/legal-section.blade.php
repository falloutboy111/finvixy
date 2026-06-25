@props([
    'id',
    'number',
    'title',
    'headingMargin' => 'mb-4',
])

<section id="{{ $id }}" @class([
    'pb-10' => $number === '01',
    'border-t border-zinc-800/50 py-10' => $number !== '01',
])>
    <flux:heading level="2" class="text-xl! font-semibold! text-white {{ $headingMargin }} flex items-center gap-3">
        <span class="text-xs font-medium text-zinc-600 tabular-nums">{{ $number }}</span>
        {{ $title }}
    </flux:heading>
    {{ $slot }}
</section>
