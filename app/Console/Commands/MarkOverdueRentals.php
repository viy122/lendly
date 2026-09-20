<?php

namespace App\Console\Commands;

use App\Services\RentalLifecycle;
use Illuminate\Console\Command;

class MarkOverdueRentals extends Command
{
    protected $signature = 'rentals:check-overdue';

    protected $description = 'Flag active rentals past their return date as overdue and calculate late fees';

    public function handle(): int
    {
        $count = RentalLifecycle::markOverdueRentals();

        $this->info("Flagged {$count} rental(s) as newly overdue.");

        return self::SUCCESS;
    }
}
