<?php

namespace App\Enums;

/**
 * ACCEPTED and DISPUTED are the renter's two responses to a claim.
 * REJECTED is set by an admin resolving a dispute in the renter's favor
 * (Phase 8) — a disputed report sits at DISPUTED until then.
 */
enum DamageReportStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Disputed = 'disputed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Response',
            self::Accepted => 'Accepted',
            self::Disputed => 'Disputed',
            self::Rejected => 'Rejected by Admin',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Accepted => 'slate',
            self::Disputed => 'red',
            self::Rejected => 'green',
        };
    }
}
