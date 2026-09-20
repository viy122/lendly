<?php

namespace App\Enums;

enum DisputeStatus: string
{
    case Open = 'open';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Resolved => 'Resolved',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Open => 'amber',
            self::Resolved => 'slate',
        };
    }
}
