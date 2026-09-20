<?php

namespace App\Enums;

enum DisputeResolution: string
{
    case ApprovedClaim = 'approved_claim';
    case RejectedClaim = 'rejected_claim';
    case PartialCompensation = 'partial_compensation';
    case FullRefund = 'full_refund';
    case AccountWarning = 'account_warning';

    public function label(): string
    {
        return match ($this) {
            self::ApprovedClaim => 'Approve claim (uphold owner)',
            self::RejectedClaim => 'Reject claim / dismiss (favor renter)',
            self::PartialCompensation => 'Partial compensation',
            self::FullRefund => 'Full refund to renter',
            self::AccountWarning => 'Account warning only',
        };
    }
}
