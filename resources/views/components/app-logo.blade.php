@props([
    'sidebar' => false,
])

@if($sidebar)
    <a {{ $attributes->merge(['class' => 'flex items-center gap-1 group']) }}>
        <x-app-logo-icon class="h-14 w-auto shrink-0" />
        <div class="flex flex-col">
            <x-finvixy-wordmark variant="dark" size="base" />
            <span class="text-[10px] font-medium tracking-widest uppercase text-zinc-500">Expense Tracker</span>
        </div>
    </a>
@else
    <flux:brand name="Finvixy" {{ $attributes }}>
        <x-slot name="logo" class="flex size-12 items-center justify-center rounded-md">
            <x-app-logo-icon class="h-10 w-auto" />
        </x-slot>
    </flux:brand>
@endif
