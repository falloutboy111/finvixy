@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="Finvixy" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md">
            <x-app-logo-icon class="size-5" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Finvixy" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md">
            <x-app-logo-icon class="size-5" />
        </x-slot>
    </flux:brand>
@endif
