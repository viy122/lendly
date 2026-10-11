<?php

namespace App\Enums;

enum RentalAdminActionType: string
{
    case NoteAdded = 'note_added';
    case BookingCancelled = 'booking_cancelled';
    case InspectionCompleted = 'inspection_completed';
    case DepositReleased = 'deposit_released';
    case PaymentVerified = 'payment_verified';
    case PaymentRejected = 'payment_rejected';

    public function label(): string
    {
        return match ($this) {
            self::NoteAdded => 'Admin note added',
            self::BookingCancelled => 'Booking cancelled',
            self::InspectionCompleted => 'Inspection completed and rental archived',
            self::DepositReleased => 'Deposit release recorded',
            self::PaymentVerified => 'Offline payment verified',
            self::PaymentRejected => 'Payment proof rejected',
        };
    }

    public function ability(): string
    {
        return match ($this) {
            self::NoteAdded => 'manage',
            self::BookingCancelled => 'adminCancel',
            self::InspectionCompleted => 'adminCompleteInspection',
            self::DepositReleased => 'adminReleaseDeposit',
            self::PaymentVerified => 'adminVerifyPayment',
            self::PaymentRejected => 'adminRejectPayment',
        };
    }
}
