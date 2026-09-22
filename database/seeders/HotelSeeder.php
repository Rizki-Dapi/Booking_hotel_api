<?php

namespace Database\Seeders;

use App\Enums\RoomStatus;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Seeder;

class HotelSeeder extends Seeder
{
    public function run(): void
    {
        $hotels = [
            ['name' => 'Grand Surabaya Hotel', 'city' => 'Surabaya', 'star_rating' => 5],
            ['name' => 'Malioboro Heritage Hotel', 'city' => 'Yogyakarta', 'star_rating' => 4],
            ['name' => 'Bali Sunset Resort', 'city' => 'Denpasar', 'star_rating' => 5],
            ['name' => 'Simple Stay Surabaya', 'city' => 'Surabaya', 'star_rating' => 2],
        ];

        $roomTypes = [
            ['name' => 'Deluxe Room', 'price_per_night' => 750000, 'capacity' => 2],
            ['name' => 'Suite Room', 'price_per_night' => 1500000, 'capacity' => 4],
        ];

        foreach ($hotels as $hotelData) {
            $hotel = Hotel::firstOrCreate(
                ['name' => $hotelData['name']],
                [
                    'description' => fake()->paragraph(),
                    'address' => fake()->streetAddress(),
                    'city' => $hotelData['city'],
                    'star_rating' => $hotelData['star_rating'],
                ]
            );

            foreach ($roomTypes as $roomTypeData) {
                $roomType = RoomType::firstOrCreate(
                    ['hotel_id' => $hotel->id, 'name' => $roomTypeData['name']],
                    [
                        'description' => fake()->sentence(),
                        'price_per_night' => $roomTypeData['price_per_night'],
                        'capacity' => $roomTypeData['capacity'],
                    ]
                );

                for ($floor = 1; $floor <= 3; $floor++) {
                    Room::firstOrCreate(
                        [
                            'room_type_id' => $roomType->id,
                            'room_number' => "{$floor}0{$roomType->id}",
                        ],
                        [
                            'floor' => $floor,
                            'status' => RoomStatus::AVAILABLE->value,
                        ]
                    );
                }
            }
        }
    }
}
