@props(['destination', 'rental', 'showQr' => true])

<div class="mt-4 space-y-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
    <p class="font-semibold text-slate-900">{{ \App\Models\OfflinePaymentSetting::methodLabel($destination['method']) }}</p>

    @if (($destination['source'] ?? null) === 'owner_wallet')
        <dl class="space-y-2 text-sm">
            <div><dt class="text-slate-500">Rental owner</dt><dd class="break-words font-medium text-slate-800">{{ $rental->owner->name }}</dd></div>
            <div><dt class="text-slate-500">Recipient account name</dt><dd class="break-words font-medium text-slate-800">{{ $destination['account_name'] }}</dd></div>
        </dl>

        @if ($showQr)
            <a href="{{ route('rentals.payment-qr', $rental) }}" target="_blank" rel="noopener" class="block rounded-xl border border-slate-200 bg-white p-4 text-center focus:outline-none focus:ring-4 focus:ring-blue-100">
                <img src="{{ route('rentals.payment-qr', $rental) }}" alt="{{ \App\Models\OfflinePaymentSetting::methodLabel($destination['method']) }} payment QR for {{ $destination['account_name'] }}" class="mx-auto h-auto max-h-80 w-full max-w-80 object-contain" />
                <span class="mt-3 block text-sm font-semibold text-blue-700">Open full-size payment QR <span class="font-normal">(new tab)</span></span>
            </a>
        @endif
    @endif

    @if ($destination['instructions'] ?? null)
        <p class="whitespace-pre-line break-words text-sm text-slate-600">{{ $destination['instructions'] }}</p>
    @endif
</div>
