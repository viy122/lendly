<?php

namespace App\Enums;

enum SecurityDepositStatus: string
{
    case Held = 'held';
    case ReturnEligible = 'return_eligible';
    case DamageClaim = 'damage_claim';
    case Deducted = 'deducted';
    case Released = 'released';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Held => 'Held',
            self::ReturnEligible => 'Return Eligible',
            self::DamageClaim => 'Damage Claim',
            self::Deducted => 'Deducted',
            self::Released => 'Released',
            self::Refunded => 'Refunded',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Held => 'slate',
            self::ReturnEligible => 'teal',
            self::DamageClaim => 'amber',
            self::Deducted => 'red',
            self::Released => 'green',
            self::Refunded => 'green',
        };
    }
}
