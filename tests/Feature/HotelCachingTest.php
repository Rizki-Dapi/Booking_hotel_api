<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use App\Repositories\Interfaces\HotelRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->hotelRepository = app(HotelRepositoryInterface::class);
});

it('caches hotel data when finding by ID', function () {
    $hotel = Hotel::factory()->create();

    expect(Cache::has("hotel:{$hotel->id}"))->toBeFalse();

    $this->hotelRepository->findById($hotel->id);

    expect(Cache::has("hotel:{$hotel->id}"))->toBeTrue();
});

it('returns stale data from cache until explicitly invalidated', function () {
    $hotel = Hotel::factory()->create(['name' => 'Original Name']);

    $this->hotelRepository->findById($hotel->id);

    DB::table('hotels')->where('id', $hotel->id)->update(['name' => 'Changed Directly In DB']);

    $cached = $this->hotelRepository->findById($hotel->id);
    expect($cached->name)->toBe('Original Name');

    $this->hotelRepository->invalidateCache($hotel->id);

    $fresh = $this->hotelRepository->findById($hotel->id);
    expect($fresh->name)->toBe('Changed Directly In DB');
});

it('automatically invalidates the cache when a hotel is updated through the repository', function () {
    $hotel = Hotel::factory()->create(['name' => 'Original Name']);

    $this->hotelRepository->findById($hotel->id);

    $this->hotelRepository->update($hotel, ['name' => 'Updated Name']);

    expect($this->hotelRepository->findById($hotel->id)->name)->toBe('Updated Name');
});

it('invalidates the hotel cache when a review is created for it', function () {
    $hotel = Hotel::factory()->create();
    $roomType = RoomType::factory()->create(['hotel_id' => $hotel->id]);
    $room = Room::factory()->create(['room_type_id' => $roomType->id]);
    $client = createUserWithRole('client');

    $booking = Booking::factory()->create([
        'user_id' => $client->id,
        'room_id' => $room->id,
        'status' => BookingStatus::COMPLETED,
    ]);

    $this->hotelRepository->findById($hotel->id);
    expect(Cache::has("hotel:{$hotel->id}"))->toBeTrue();

    actingAsApi($client)->postJson('/api/reviews', [
        'booking_id' => $booking->id,
        'rating' => 5,
        'comment' => 'Great stay',
    ])->assertCreated();

    expect(Cache::has("hotel:{$hotel->id}"))->toBeFalse();
});
