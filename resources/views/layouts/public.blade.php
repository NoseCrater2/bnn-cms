@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">

            <flux:spacer />

            @auth
                <flux:button :href="route('dashboard')" variant="primary" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:button>
            @else
                <flux:button :href="route('login')" variant="primary" wire:navigate>
                    {{ __('Iniciar sesión') }}
                </flux:button>
            @endauth
        </flux:header>

        <flux:main container>
            {{ $slot }}
        </flux:main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
