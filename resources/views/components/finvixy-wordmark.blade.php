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

<span {{ $attributes->merge(['class' => "font-sans font-extrabold tracking-tight leading-tight inline-flex items-baseline {$sizeClasses} {$textColor} {$glowClass}"]) }}>FIN<span class="relative inline-block text-emerald-400">V<svg class="absolute -top-[0.3em] right-0 w-[0.4em] h-[0.4em]" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><line x1="3" y1="13" x2="11" y2="3" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" /><polyline points="7,3 11,3 11,7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg></span>IXY</span>
