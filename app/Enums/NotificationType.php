<?php

namespace App\Enums;

enum NotificationType: string
{
    case RentalRequestSubmitted = 'rental_request_submitted';
    case RequestApproved = 'request_approved';
    case RequestRejected = 'request_rejected';
    case PaymentConfirmed = 'payment_confirmed';
    case PickupConfirmed = 'pickup_confirmed';
    case ReturnConfirmed = 'return_confirmed';
    case ReturnReminder = 'return_reminder';
    case RentalOverdue = 'rental_overdue';
    case DepositUpdate = 'deposit_update';
    case ReviewRequest = 'review_request';
    case DamageClaimFiled = 'damage_claim_filed';
    case DisputeUpdate = 'dispute_update';
    case RentalCancelled = 'rental_cancelled';
    case NewMessage = 'new_message';

    public function icon(): string
    {
        return match ($this) {
            self::RentalRequestSubmitted => '📩',
            self::RequestApproved => '✅',
            self::RequestRejected => '❌',
            self::PaymentConfirmed => '💳',
            self::PickupConfirmed => '📦',
            self::ReturnConfirmed => '↩️',
            self::ReturnReminder => '⏰',
            self::RentalOverdue => '⚠️',
            self::DepositUpdate => '🔒',
            self::ReviewRequest => '⭐',
            self::DamageClaimFiled => '🛠️',
            self::DisputeUpdate => '⚖️',
            self::RentalCancelled => '🚫',
            self::NewMessage => '💬',
        };
    }
}
