@props([
    'variant' => 'dark', // 'dark' = white text on dark bg, 'light' = dark text on light bg
    'glow' => false,
    'size' => 'base', // 'xs', 'sm', 'base', 'lg', 'xl', '2xl'
])

@php
    $sizeClasses = match($size) {
        'xs' => 'text-sm',
        'sm' => 'text-base',
        'base' => 'text-lg',
        'lg' => 'text-xl',
        'xl' => 'text-2xl',
        '2xl' => 'text-4xl',
        default => 'text-lg',
    };

    $textColor = $variant === 'dark' ? 'text-neutral-50' : 'text-[#050505]';
    $glowClass = ($glow || $variant === 'dark') ? 'drop-shadow-[0_0_10px_rgba(16,185,129,0.6)]' : '';
@endphp

<span {{ $attributes->merge(['class' => "font-sans font-extrabold tracking-tight leading-tight {$sizeClasses} {$textColor} {$glowClass}"]) }}>FIN<span class="text-emerald-400">V</span>IXY</span>
