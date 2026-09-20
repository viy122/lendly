<?php

namespace App\Enums;

enum RentalRequestStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Requested',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Requested => 'amber',
            self::Approved => 'green',
            self::Rejected => 'red',
            self::Cancelled => 'slate',
        };
    }
}
