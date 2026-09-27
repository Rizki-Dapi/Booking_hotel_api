<?php

namespace Database\Factories;

use App\Models\Hotel;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomType>
 */
class RoomTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    protected $model = RoomType::class;

    public function definition(): array
    {
        return [
            'hotel_id' => Hotel::factory(),
            'name' => fake()->randomElement(['Deluxe Room', 'Suite Room', 'Standard Room', 'Superior Room']),
            'description' => fake()->sentence(),
            'price_per_night' => fake()->numberBetween(300000, 2000000),
            'capacity' => fake()->numberBetween(1, 4)
        ];
    }
}
