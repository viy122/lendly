<?php

use App\Services\RentalPayments;
use App\Services\RentalWaitlist;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('rentals:check-overdue')->everyMinute()->withoutOverlapping();
Schedule::command('rentals:send-return-reminders')->daily();
Schedule::command('identity:prune-documents')->hourly()->withoutOverlapping();

Artisan::command('rentals:process-reservations', function () {
    $expired = RentalPayments::expireReservations();
    $notified = RentalWaitlist::notifyAvailable();
    $this->info("Expired {$expired} unpaid reservations; sent {$notified} availability alerts.");
})->purpose('Release expired reservations and notify rental waitlists');

Schedule::command('rentals:process-reservations')->everyMinute()->withoutOverlapping();
