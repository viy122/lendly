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
    <body class="font-sans text-slate-900 antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center bg-slate-50 px-4 pt-6 sm:pt-0">
            <a href="/" wire:navigate class="flex items-center gap-2">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-600 text-lg font-bold text-white">L</span>
                <span class="text-2xl font-bold tracking-tight text-slate-900">Lendly</span>
            </a>

            <div class="mt-6 w-full rounded-xl border border-slate-200 bg-white px-6 py-8 shadow-sm sm:max-w-md">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
