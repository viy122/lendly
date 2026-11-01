@props(['booking'])
@php($rentalDays = $booking->rental_days ?? ((int) $booking->start_date->diffInDays($booking->end_date) + 1))
@if ($booking->starts_at && $booking->ends_at)
    {{ $booking->starts_at->copy()->setTimezone('Asia/Manila')->format('M d, Y g:i A') }} – {{ $booking->ends_at->copy()->setTimezone('Asia/Manila')->format('M d, Y g:i A') }} ({{ $booking->rental_unit === 'hour' ? $booking->rental_hours.' hours' : $rentalDays.' days' }}, Philippine time)
@else
    {{ $booking->start_date->format('M d, Y') }} – {{ $booking->end_date->format('M d, Y') }} ({{ $rentalDays }} days)
@endif
