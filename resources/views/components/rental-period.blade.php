@props(['booking'])
@if ($booking->rental_unit === 'hour')
    {{ $booking->starts_at->copy()->setTimezone('Asia/Manila')->format('M d, Y g:i A') }} – {{ $booking->ends_at->copy()->setTimezone('Asia/Manila')->format('M d, Y g:i A') }} ({{ $booking->rental_hours }} hours, Philippine time)
@else
    {{ $booking->start_date->format('M d, Y') }} – {{ $booking->end_date->format('M d, Y') }} ({{ $booking->rental_days }} days)
@endif
