<?php

namespace App\Enums;

enum ListingAvailabilityStatus: string
{
    case Available = 'available';
    case OnHold = 'on_hold';
    case Reserved = 'reserved';
    case Rented = 'rented';
    case Unavailable = 'unavailable';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::OnHold => 'On hold',
            self::Reserved => 'Reserved',
            self::Rented => 'Rented',
            self::Unavailable => 'Unavailable',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Available => 'green',
            self::OnHold => 'amber',
            self::Reserved => 'amber',
            self::Rented => 'teal',
            self::Unavailable => 'red',
        };
    }
}
