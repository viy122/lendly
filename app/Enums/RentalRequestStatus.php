<?php

namespace App\Enums;

enum RentalRequestStatus: string
{
    case Requested = 'requested';
    case AwaitingAgreement = 'awaiting_agreement';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Requested',
            self::AwaitingAgreement => 'Awaiting agreement',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Requested => 'amber',
            self::AwaitingAgreement => 'blue',
            self::Approved => 'green',
            self::Rejected => 'red',
            self::Cancelled => 'slate',
        };
    }
}
