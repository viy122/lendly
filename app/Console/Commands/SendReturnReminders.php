<?php

namespace App\Console\Commands;

use App\Services\RentalLifecycle;
use Illuminate\Console\Command;

class SendReturnReminders extends Command
{
    protected $signature = 'rentals:send-return-reminders';

    protected $description = 'Notify renters whose active rental is due back tomorrow';

    public function handle(): int
    {
        $count = RentalLifecycle::sendReturnReminders();

        $this->info("Sent {$count} return reminder(s).");

        return self::SUCCESS;
    }
}
