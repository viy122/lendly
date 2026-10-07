@props(['eyebrow' => null, 'title', 'subtitle' => null, 'maxWidth' => 'w-full'])

<header class="sticky top-0 z-20 overflow-visible border-b border-slate-200/70 bg-white/90 shadow-[0_8px_30px_rgba(15,45,95,0.06)] backdrop-blur-xl">
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute inset-0 bg-[linear-gradient(135deg,#ffffff_0%,#eff6ff_45%,#dbeafe_100%)]"></div>
        <div class="absolute -bottom-24 -right-12 h-56 w-80 rounded-full bg-[radial-gradient(circle,rgba(37,99,235,0.36)_0%,rgba(56,189,248,0.24)_38%,rgba(147,197,253,0.12)_56%,transparent_74%)] blur-2xl"></div>
        <div class="absolute right-[18%] top-0 h-full w-44 bg-[linear-gradient(115deg,transparent,rgba(255,255,255,0.72),rgba(96,165,250,0.12),transparent)] opacity-80"></div>
        <svg class="absolute right-8 top-1/2 h-20 w-44 -translate-y-1/2 text-blue-500 opacity-[0.08]" viewBox="0 0 176 80" fill="none" aria-hidden="true">
            <circle cx="8" cy="8" r="2" fill="currentColor"/><circle cx="32" cy="8" r="2" fill="currentColor"/><circle cx="56" cy="8" r="2" fill="currentColor"/><circle cx="80" cy="8" r="2" fill="currentColor"/><circle cx="104" cy="8" r="2" fill="currentColor"/><circle cx="128" cy="8" r="2" fill="currentColor"/><circle cx="152" cy="8" r="2" fill="currentColor"/>
            <circle cx="8" cy="32" r="2" fill="currentColor"/><circle cx="32" cy="32" r="2" fill="currentColor"/><circle cx="56" cy="32" r="2" fill="currentColor"/><circle cx="80" cy="32" r="2" fill="currentColor"/><circle cx="104" cy="32" r="2" fill="currentColor"/><circle cx="128" cy="32" r="2" fill="currentColor"/><circle cx="152" cy="32" r="2" fill="currentColor"/>
            <circle cx="8" cy="56" r="2" fill="currentColor"/><circle cx="32" cy="56" r="2" fill="currentColor"/><circle cx="56" cy="56" r="2" fill="currentColor"/><circle cx="80" cy="56" r="2" fill="currentColor"/><circle cx="104" cy="56" r="2" fill="currentColor"/><circle cx="128" cy="56" r="2" fill="currentColor"/><circle cx="152" cy="56" r="2" fill="currentColor"/>
        </svg>
    </div>

    <div {{ $attributes->merge(['class' => 'relative mx-auto flex min-h-24 ' . $maxWidth . ' items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:min-h-28 lg:px-8']) }}>
        <div class="flex min-w-0 items-center gap-3 sm:gap-4">
            <button
                type="button"
                @click="$dispatch('toggle-sidebar')"
                class="group grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-blue-100 bg-white text-blue-600 shadow-[0_7px_20px_rgba(37,99,235,0.12)] transition duration-200 hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-50 hover:shadow-[0_10px_24px_rgba(37,99,235,0.18)] focus:outline-none focus:ring-4 focus:ring-blue-100 lg:hidden"
                aria-label="Open navigation">
                <x-icon name="menu" class="h-5 w-5 transition-transform duration-200 group-hover:scale-110" />
            </button>

            <div class="min-w-0">
                @if ($eyebrow)
                    <p class="mb-1.5 inline-flex items-center gap-2 rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-blue-700 ring-1 ring-blue-100">
                        <span class="h-1.5 w-1.5 rounded-full bg-blue-600 shadow-[0_0_8px_rgba(37,99,235,0.65)]"></span>
                        {{ $eyebrow }}
                    </p>
                @endif
                <h1 class="truncate text-xl font-extrabold tracking-[-0.035em] text-[#071a3d] sm:text-2xl lg:text-[1.7rem]">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="mt-1 max-w-3xl truncate text-xs leading-5 text-slate-500 sm:text-sm">{{ $subtitle }}</p>
                @endif
            </div>
        </div>

        @auth
            <div class="relative shrink-0">
                <livewire:notifications.bell />
            </div>
        @endauth
    </div>
</header>
