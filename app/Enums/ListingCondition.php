<?php

namespace App\Enums;

enum ListingCondition: string
{
    case New = 'new';
    case LikeNew = 'like_new';
    case Excellent = 'excellent';
    case Good = 'good';
    case Fair = 'fair';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::LikeNew => 'Like New',
            self::Excellent => 'Excellent',
            self::Good => 'Good',
            self::Fair => 'Fair',
        };
    }
}
