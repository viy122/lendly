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
    <body class="bg-[#f6f8fb] font-sans text-slate-900 antialiased">
        <header class="sticky top-0 z-20 border-b border-slate-200/80 bg-white/90 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <a href="/" class="flex items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 to-teal-500 text-sm font-bold text-white shadow-sm">L</span>
                    <span class="text-lg font-bold tracking-tight text-slate-900">Lendly</span>
                </a>

                <nav class="flex items-center gap-3">
                    @auth
                        <a href="{{ route(auth()->user()->dashboardRouteName()) }}" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                            Go to dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Log in</a>
                        <a href="{{ route('register') }}" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                            Get started
                        </a>
                    @endauth
                </nav>
            </div>
        </header>

        <main>
            <section class="relative overflow-hidden bg-slate-950 bg-cover bg-center" style="background-image: linear-gradient(90deg, rgba(15,23,42,.96) 0%, rgba(15,23,42,.82) 46%, rgba(15,23,42,.25) 100%), url('{{ asset('images/rental-hero.png') }}');">
                <div class="mx-auto flex min-h-[38rem] max-w-7xl items-center px-4 py-16 sm:px-6 lg:px-8">
                    <div class="max-w-2xl">
                        <p class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold text-indigo-100 backdrop-blur">
                            <span class="h-1.5 w-1.5 rounded-full bg-teal-300"></span>
                            Peer-to-peer rentals in Batangas
                        </p>
                        <h1 class="mt-5 text-4xl font-bold leading-[1.08] tracking-tight text-white sm:text-6xl">
                            Borrow the good stuff. Share what you have.
                        </h1>
                        <p class="mt-6 max-w-lg text-lg leading-8 text-slate-200">
                            Find useful things nearby for the moments that matter, or turn the items sitting at home into extra income.
                        </p>
                        <div class="mt-8 flex flex-wrap items-center gap-3">
                            <a href="{{ route('register') }}" class="rounded-xl bg-white px-6 py-3 text-sm font-semibold text-indigo-700 shadow-lg transition hover:-translate-y-0.5 hover:bg-indigo-50">
                                Create a free account
                            </a>
                            <a href="{{ route('login') }}" class="rounded-xl border border-white/30 bg-white/10 px-6 py-3 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20">
                                Log in
                            </a>
                        </div>
                        <div class="mt-9 flex flex-wrap gap-6 text-sm text-slate-300">
                            <span><strong class="text-white">Nearby</strong> listings</span>
                            <span><strong class="text-white">Flexible</strong> rental periods</span>
                            <span><strong class="text-white">Built-in</strong> accountability</span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-t border-slate-200/80 bg-white">
                <div class="mx-auto grid max-w-7xl grid-cols-1 gap-5 px-4 py-16 sm:px-6 md:grid-cols-3 lg:px-8">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 transition hover:-translate-y-1 hover:shadow-lg hover:shadow-slate-200/60">
                        <p class="text-sm font-semibold text-indigo-600">For renters</p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900">Need something temporarily?</h3>
                        <p class="mt-2 text-sm text-slate-500">
                            Search nearby listings on the map and filter by distance to find what's closest.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 transition hover:-translate-y-1 hover:shadow-lg hover:shadow-slate-200/60">
                        <p class="text-sm font-semibold text-indigo-600">For item owners</p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900">Have an item you rarely use?</h3>
                        <p class="mt-2 text-sm text-slate-500">
                            List it in minutes, get market-based pricing insight, and manage requests, earnings, and rentals from one dashboard.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 transition hover:-translate-y-1 hover:shadow-lg hover:shadow-slate-200/60">
                        <p class="text-sm font-semibold text-indigo-600">For the platform</p>
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
