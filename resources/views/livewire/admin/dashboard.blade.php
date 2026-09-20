<div>
    <x-page-header eyebrow="Administration" title="Admin dashboard" subtitle="Platform overview and user management." />

    <div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <x-stat-card label="Total users" :value="$totalUsers" hint="{{ $suspendedUsers }} suspended" icon="user-circle" accent="blue" />
            <x-stat-card label="Active listings" :value="$activeListingsCount" hint="{{ $pendingListingsCount }} pending approval" icon="list" accent="blue" />
            <x-stat-card label="Active rentals" :value="$activeRentalsCount" hint="{{ $overdueRentalsCount }} overdue" icon="archive" accent="emerald" />
            <x-stat-card label="Completed rentals" :value="$completedRentalsCount" icon="archive" accent="emerald" />
            <x-stat-card label="Platform revenue" :value="'₱' . number_format($platformRevenue, 2)" hint="₱{{ number_format($transactionValue, 0) }} total transacted" icon="archive" accent="indigo" />
            <x-stat-card label="Open disputes" :value="$openDisputesCount" icon="scale" accent="rose" />
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Trend</p>
                <h2 class="mt-0.5 text-base font-bold text-slate-900">Platform revenue, last 6 months</h2>
                <div class="mt-5">
                    <x-bar-chart :data="$monthlyRevenue" />
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Breakdown</p>
                <h2 class="mt-0.5 text-base font-bold text-slate-900">Member activity</h2>
                <div class="mt-5">
                    <x-bar-chart :data="$usersByRole" />
                </div>
            </div>
        </div>

        @if ($rentalsByStatus->isNotEmpty())
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Pipeline</p>
                <h2 class="mt-0.5 text-base font-bold text-slate-900">Rentals by status</h2>
                <div class="mt-5">
                    <x-bar-chart :data="$rentalsByStatus" />
                </div>
            </div>
        @endif

        <div>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-slate-700">Users</h2>
                <div class="relative w-full max-w-xs">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search by name or email..."
                        class="w-full rounded-lg border-slate-300 pl-9 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>
            </div>

            <div class="mt-3 overflow-hidden overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">Name</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">Email</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">Role</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">Status</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">Joined</th>
                            <th class="px-4 py-3 text-right font-medium text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($users as $user)
                            <tr wire:key="user-{{ $user->id }}" class="hover:bg-slate-50/75">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">
                                            {{ Str::of($user->name)->substr(0, 1)->upper() }}
                                        </span>
                                        <span class="font-medium text-slate-800">{{ $user->name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-500">{{ $user->email }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $user->role->label() }}</td>
                                <td class="px-4 py-3">
                                    @if ($user->isSuspended())
                                        <x-badge color="red">Suspended</x-badge>
                                    @else
                                        <x-badge color="green">Active</x-badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-500">{{ $user->created_at->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if (! $user->isAdmin() && $user->id !== auth()->id())
                                        <button
                                            type="button"
                                            wire:click="toggleSuspension({{ $user->id }})"
                                            wire:confirm="Are you sure you want to {{ $user->isSuspended() ? 'reactivate' : 'suspend' }} {{ $user->name }}?"
                                            class="text-xs font-medium {{ $user->isSuspended() ? 'text-blue-600 hover:text-blue-800' : 'text-rose-600 hover:text-rose-800' }}"
                                        >
                                            {{ $user->isSuspended() ? 'Reactivate' : 'Suspend' }}
                                        </button>
                                    @else
                                        <span class="text-xs text-slate-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-400">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</div>
