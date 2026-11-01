@props(['interface', 'requestProgress', 'memberRentalsByStatus', 'awaitingAgreementCount', 'identityProgress', 'proofsUnderReviewCount'])
<section aria-label="Account and request progress" class="space-y-4">
    <div class="grid gap-4 sm:grid-cols-3">
        <a href="{{ route('identity.verify') }}" wire:navigate class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-medium text-slate-500">Identity verification</p><p class="mt-2 font-semibold capitalize text-slate-900">{{ $identityProgress }}</p><p class="mt-1 text-xs text-blue-600">View verification progress →</p></a>
        <a href="{{ route($interface.'.rental-requests.index', ['filter' => 'awaiting_agreement']) }}" wire:navigate class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-medium text-slate-500">Awaiting agreement</p><p class="mt-2 text-xl font-semibold text-slate-900">{{ $awaitingAgreementCount }}</p><p class="mt-1 text-xs text-blue-600">Review agreement requests →</p></a>
        <a href="{{ route($interface.'.rentals.index') }}" wire:navigate class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-medium text-slate-500">Payment proofs under review</p><p class="mt-2 text-xl font-semibold text-slate-900">{{ $proofsUnderReviewCount }}</p><p class="mt-1 text-xs text-blue-600">{{ $interface === 'owner' ? 'Check proof and wallet receipt' : 'View payment review progress' }} →</p></a>
    </div>
    <div class="grid gap-4 {{ $interface === 'renter' ? 'lg:grid-cols-2' : '' }}">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="mb-4 font-semibold text-slate-900">Rental request progress</h2><x-pie-chart :data="$requestProgress" /><p class="mt-3 text-xs text-slate-500">All your requests, grouped by current status.</p></div>
        @if ($interface === 'renter')<div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="mb-4 font-semibold text-slate-900">Your rentals by status</h2><x-pie-chart :data="$memberRentalsByStatus" /></div>@endif
    </div>
</section>
