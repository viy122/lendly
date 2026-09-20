<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Lendly') }} — Rent anything, from anyone nearby</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-white font-sans text-slate-900 antialiased">
        <header class="sticky top-0 z-20 border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <a href="/" class="flex items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold text-white">L</span>
                    <span class="text-lg font-semibold tracking-tight text-slate-900">Lendly</span>
                </a>

                <nav class="flex items-center gap-3">
                    @auth
                        <a href="{{ route(auth()->user()->dashboardRouteName()) }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                            Go to dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Log in</a>
                        <a href="{{ route('register') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                            Get started
                        </a>
                    @endauth
                </nav>
            </div>
        </header>

        <main>
            <section class="mx-auto max-w-7xl px-4 py-20 text-center sm:px-6 lg:px-8">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Peer-to-peer rentals · Batangas</p>
                <h1 class="mx-auto mt-3 max-w-3xl text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">
                    Rent what you need. Earn from what you don't use.
                </h1>
                <p class="mx-auto mt-5 max-w-2xl text-lg text-slate-500">
                    Lendly connects people who own items they rarely use with people who need them temporarily —
                    with smart pricing insight and map-based search to find what's nearby.
                </p>
                <div class="mt-8 flex items-center justify-center gap-4">
                    <a href="{{ route('register') }}" class="rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                        Create a free account
                    </a>
                    <a href="{{ route('login') }}" class="rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                        Log in
                    </a>
                </div>
            </section>

            <section class="border-t border-slate-200 bg-slate-50">
                <div class="mx-auto grid max-w-7xl grid-cols-1 gap-8 px-4 py-16 sm:px-6 md:grid-cols-3 lg:px-8">
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <p class="text-sm font-semibold text-blue-600">For renters</p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900">Need something temporarily?</h3>
                        <p class="mt-2 text-sm text-slate-500">
                            Search nearby listings on the map and filter by distance to find what's closest.
                        </p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <p class="text-sm font-semibold text-blue-600">For item owners</p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900">Have an item you rarely use?</h3>
                        <p class="mt-2 text-sm text-slate-500">
                            List it in minutes, get market-based pricing insight, and manage requests, earnings, and rentals from one dashboard.
                        </p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <p class="text-sm font-semibold text-blue-600">For the platform</p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900">Safe, accountable transactions</h3>
                        <p class="mt-2 text-sm text-slate-500">
                            Role-based access and admin-reviewed dispute resolution keep every rental transaction accountable.
                        </p>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-slate-200 py-8 text-center text-sm text-slate-400">
            &copy; {{ date('Y') }} Lendly. Built as a System Quality and Assurance academic project.
        </footer>
    </body>
</html>
