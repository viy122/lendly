<?php

namespace App\Enums;

enum ListingStatus: string
{
    case PendingApproval = 'pending_approval';
    case Published = 'published';
    case Rejected = 'rejected';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::PendingApproval => 'Pending Approval',
            self::Published => 'Published',
            self::Rejected => 'Rejected',
            self::Inactive => 'Inactive',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::PendingApproval => 'amber',
            self::Published => 'green',
            self::Rejected => 'red',
            self::Inactive => 'slate',
        };
    }
}
