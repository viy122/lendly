<?php

namespace App\Enums;

enum DisputeReason: string
{
    case ItemDamaged = 'item_damaged';
    case ItemNotAsDescribed = 'item_not_as_described';
    case OwnerDidNotProvideItem = 'owner_did_not_provide_item';
    case RenterDidNotReturnItem = 'renter_did_not_return_item';
    case IncorrectCharge = 'incorrect_charge';
    case DepositDisagreement = 'deposit_disagreement';

    public function label(): string
    {
        return match ($this) {
            self::ItemDamaged => 'Item damaged',
            self::ItemNotAsDescribed => 'Item not as described',
            self::OwnerDidNotProvideItem => 'Owner did not provide item',
            self::RenterDidNotReturnItem => 'Renter did not return item',
            self::IncorrectCharge => 'Incorrect charge',
            self::DepositDisagreement => 'Deposit disagreement',
        };
    }
}
