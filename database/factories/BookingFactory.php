<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_code' => 'BK-' . strtoupper(Str::random(8)),
            'user_id' => User::factory(),
            'room_id' => Room::factory(),
            'check_in_date' => now()->addDays(5),
            'check_out_date' => now()->addDays(3),
            'total_price' => 500000,
            'status' => BookingStatus::PENDING,
        ];
    }
}
