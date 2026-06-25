<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-emerald-100 bg-white dark:border-emerald-500/10 dark:bg-zinc-900">
            <flux:sidebar.header>
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2" wire:navigate>
                    <x-app-logo-icon class="h-6 w-6 object-contain shrink-0" />
                    <x-finvixy-wordmark variant="dark" size="base" />
                </a>
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:separator class="border-emerald-100 dark:border-emerald-500/10" />

            <flux:sidebar.nav class="mt-2">
                <flux:sidebar.group :heading="__('Overview')" class="grid">
                    <flux:sidebar.item icon="chart-bar-square" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Scanning')" class="grid">
                    <flux:sidebar.item icon="camera" :href="route('expenses.index')" :current="request()->routeIs('expenses.*')" wire:navigate>
                        {{ __('Expenses') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Reports')" class="grid">
                    <flux:sidebar.item icon="chart-pie" :href="route('reports.spending')" :current="request()->routeIs('reports.spending')" wire:navigate>
                        {{ __('Spending Report') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="light-bulb" :href="route('reports.insights')" :current="request()->routeIs('reports.insights')" wire:navigate>
                        {{ __('Insights') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:separator class="border-emerald-100 dark:border-emerald-500/10" />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="cog-6-tooth" :href="route('profile.edit')" wire:navigate>
                    {{ __('Settings') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <flux:main class="bg-slate-50 dark:bg-zinc-950">
            {{ $slot }}
        </flux:main>

        @fluxScripts
    </body>
</html>
