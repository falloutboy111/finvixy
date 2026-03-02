{{-- Finvixy V-tick icon: stylised checkmark/arrow V --}}
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" fill="none" {{ $attributes }}>
    <defs>
        <linearGradient id="v-grad" x1="0%" y1="100%" x2="100%" y2="0%">
            <stop offset="0%" stop-color="#34d399" />
            <stop offset="100%" stop-color="#10b981" />
        </linearGradient>
    </defs>
    {{-- The V / checkmark stroke --}}
    <polyline points="4,8 16,26 24,12" stroke="url(#v-grad)" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" />
    {{-- Upward arrow from the right arm --}}
    <line x1="24" y1="12" x2="28" y2="4" stroke="url(#v-grad)" stroke-width="3.5" stroke-linecap="round" />
    <polyline points="23,4 28,4 28,9" stroke="url(#v-grad)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
</svg>
