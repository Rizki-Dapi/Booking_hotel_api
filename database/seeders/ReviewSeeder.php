<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $reviewer = User::firstOrCreate(
            ['email' => 'reviewer@hotelbooking.test'],
            ['name' => 'Demo Reviewer', 'password' => bcrypt('password')]
        );

        if (! $reviewer->hasRole('client')) {
            $reviewer->assignRole('client');
        }

        $ratings = [
            'Grand Surabaya Hotel' => 5,
            'Malioboro Heritage Hotel' => 3,
            'Bali Sunset Resort' => 4,
        ];

        foreach ($ratings as $hotelName => $rating) {
            $hotel = Hotel::where('name', $hotelName)->with('roomTypes.rooms')->first();
            $roomType = $hotel?->roomTypes->first();
            $room = $roomType?->rooms->first();

            if (! $room) {
                continue;
            }

            $checkIn = Carbon::now()->subDays(10);
            $checkOut = Carbon::now()->subDays(8);

            $booking = Booking::firstOrCreate(
                ['user_id' => $reviewer->id, 'room_id' => $room->id, 'check_in_date' => $checkIn],
                [
                    'booking_code' => 'BK-SEED' . strtoupper(Str::random(5)),
                    'check_out_date' => $checkOut,
                    'total_price' => $roomType->price_per_night * 2,
                    'status' => BookingStatus::COMPLETED,
                ]
            );

            Review::firstOrCreate(
                ['booking_id' => $booking->id],
                [
                    'user_id' => $reviewer->id,
                    'hotel_id' => $hotel->id,
                    'rating' => $rating,
                    'comment' => fake()->sentence(),
                ]
            );
        }
    }
}
