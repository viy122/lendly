<?php

namespace App\Enums;

enum ReviewType: string
{
    case RenterToOwner = 'renter_to_owner';
    case RenterToListing = 'renter_to_listing';
    case OwnerToRenter = 'owner_to_renter';

    public function label(): string
    {
        return match ($this) {
            self::RenterToOwner => 'Renter rating of owner',
            self::RenterToListing => 'Renter rating of item',
            self::OwnerToRenter => 'Owner rating of renter',
        };
    }
}
