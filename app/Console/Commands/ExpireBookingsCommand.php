<?php

namespace App\Console\Commands;

use App\Services\BookingService;
use Illuminate\Console\Command;

class ExpireBookingsCommand extends Command
{
    protected $signature = 'bookings:expire {--hours=24 : Payment deadline before the order expires}';

    protected $description = 'Mark unpaid pending bookings as expired after the payment window has passed';

    public function handle(BookingService $bookingService): int
    {
        $hours = (int) $this->option('hours');
        $count = $bookingService->expireUnpaidBookings($hours);

        $this->info("Expired {$count} booking(s) older than {$hours} hour(s).");

        return self::SUCCESS;
    }
}
