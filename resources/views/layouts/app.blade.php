<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Lendly') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 font-sans antialiased">
        <div x-data class="min-h-screen lg:flex">
            <livewire:layout.sidebar />

            <div class="min-w-0 flex-1">
                <!-- Top bar -->
                <div class="sticky top-0 z-20 flex items-center justify-between bg-gradient-to-r from-blue-100 to-indigo-100 px-4 py-1 shadow-sm sm:px-6 lg:px-8">
                    <a href="{{ route(auth()->user()->dashboardRouteName()) }}" wire:navigate class="flex items-center gap-2 lg:hidden">
                        <span class="flex h-6 w-6 items-center justify-center rounded-md bg-gradient-to-br from-blue-500 to-indigo-600 text-[10px] font-bold text-white">L</span>
                        <span class="text-sm font-semibold tracking-tight text-slate-900">Lendly</span>
                    </a>

                    <span class="hidden lg:block"></span>

                    <div class="flex items-center gap-1">
                        <livewire:notifications.bell />
                        <a href="{{ route('messages.index') }}" wire:navigate class="relative rounded-md border border-blue-200 bg-white p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                            <x-icon name="chat" class="h-4 w-4" />
                            @if (auth()->user()->unreadMessagesCount() > 0)
                                <span class="absolute -right-0.5 -top-0.5 flex h-3 w-3 items-center justify-center rounded-full bg-rose-600 text-[8px] font-semibold text-white">
                                    {{ auth()->user()->unreadMessagesCount() > 9 ? '9+' : auth()->user()->unreadMessagesCount() }}
                                </span>
                            @endif
                        </a>
                        <button @click="$dispatch('toggle-sidebar')" class="rounded-md p-1 text-slate-500 hover:bg-blue-100/60 hover:text-slate-700 lg:hidden">
                            <x-icon name="menu" class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- Page Content -->
                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
