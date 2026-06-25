@props([
    'sidebar' => false,
])

@if($sidebar)
    <a {{ $attributes->merge(['class' => 'flex items-center group']) }}>
        <x-finvixy-wordmark variant="dark" size="base" />
    </a>
@else
    <flux:brand name="Finvixy" {{ $attributes }}>
        <x-slot name="logo" class="flex size-12 items-center justify-center rounded-md">
            <x-app-logo-icon class="h-10 w-auto" />
        </x-slot>
    </flux:brand>
@endif
