<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased bg-zinc-100 dark:bg-zinc-900">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-xl flex-col gap-6">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-3 font-medium" wire:navigate>
                    <x-app-logo-icon class="h-14 w-auto" />
                    <x-finvixy-wordmark variant="dark" size="base" />
                </a>
                <flux:card class="px-10 py-8">
                    {{ $slot }}
                </flux:card>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
