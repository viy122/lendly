<?php

namespace App\Enums;

/**
 * The spec's full lifecycle diagram lists PAID and READY FOR PICKUP as
 * separate steps, and RETURNED / INSPECTION / COMPLETED as three separate
 * steps. In this implementation:
 * - "Ready for pickup" isn't a distinct stored state — nothing actionable
 *   happens between payment completing and pickup being confirmed, so a
 *   paid rental simply waits in PAID until both sides confirm pickup.
 * - "Inspection" isn't a distinct stored state either, since without
 *   Phase 7's actual condition/damage recording there is nothing to
 *   distinguish it from RETURNED — the owner's "Complete Inspection"
 *   action goes straight from Returned to Completed. Phase 7 can insert a
 *   real Inspection state later if the condition workflow needs one.
 * DISPUTED is deliberately not added here; it belongs to Phase 8.
 */
enum RentalStatus: string
{
    case PaymentPending = 'payment_pending';
    case Paid = 'paid';
    case Active = 'active';
    case Overdue = 'overdue';
    case Returned = 'returned';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PaymentPending => 'Payment Pending',
            self::Paid => 'Paid',
            self::Active => 'Active Rental',
            self::Overdue => 'Overdue',
            self::Returned => 'Returned',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::PaymentPending => 'amber',
            self::Paid => 'teal',
            self::Active => 'green',
            self::Overdue => 'red',
            self::Returned => 'slate',
            self::Completed => 'slate',
            self::Cancelled => 'slate',
        };
    }
}
