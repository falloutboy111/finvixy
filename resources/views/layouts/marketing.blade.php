@props(['title' => null])

{{-- Public marketing pages are dark-only by design, so the dark class is fixed
     and @fluxAppearance (user-controlled theming) is intentionally omitted. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Finvixy — Scan. Track. Save.' }}</title>
        <link rel="icon" href="/logoFinvixy.png" type="image/png">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-zinc-950 text-zinc-100 antialiased min-h-screen">

        {{-- Navigation --}}
        <header class="fixed top-0 left-0 right-0 z-50 border-b border-zinc-800/50 bg-zinc-950/90 backdrop-blur-xl">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                        <x-app-logo-icon class="h-16 w-auto" />
                        <x-finvixy-wordmark variant="dark" size="lg" />
                    </a>
                    <nav class="flex items-center gap-4">
                        <a href="{{ route('pricing') }}" @class([
                            'text-sm font-medium transition',
                            'text-emerald-400' => request()->routeIs('pricing'),
                            'text-gray-400 hover:text-emerald-400' => ! request()->routeIs('pricing'),
                        ])>Pricing</a>
                        @auth
                            <flux:button href="{{ route('dashboard') }}" variant="primary" size="sm">Dashboard</flux:button>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-medium text-gray-400 transition hover:text-emerald-400">Log in</a>
                            @if (Route::has('register'))
                                <flux:button href="{{ route('register') }}" variant="primary" size="sm">Get Started</flux:button>
                            @endif
                        @endauth
                    </nav>
                </div>
            </div>
        </header>

        {{ $slot }}

        {{-- Footer --}}
        <footer class="border-t border-emerald-500/10 py-8">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="flex flex-col items-center gap-6 md:flex-row md:justify-between">
                    <div class="flex items-center gap-2">
                        <x-app-logo-icon class="h-7 w-auto" />
                        <x-finvixy-wordmark variant="dark" size="sm" />
                    </div>
                    <nav class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-xs text-zinc-500">
                        @foreach ([
                            'pricing' => 'Pricing',
                            'terms' => 'Terms of Service',
                            'privacy' => 'Privacy Policy',
                            'refund' => 'Refund Policy',
                        ] as $routeName => $label)
                            @if (! $loop->first)
                                <span class="text-zinc-800">·</span>
                            @endif
                            <a href="{{ route($routeName) }}" @class([
                                'transition hover:text-emerald-400',
                                'font-medium text-emerald-400' => request()->routeIs($routeName),
                            ])>{{ $label }}</a>
                        @endforeach
                    </nav>
                    <p class="text-xs text-zinc-500">&copy; {{ date('Y') }} Finvixy by Enclivix. All rights reserved.</p>
                </div>
            </div>
        </footer>

        @fluxScripts
    </body>
</html>
