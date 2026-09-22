<?php

namespace App\Repositories;

use App\Models\Review;
use App\Repositories\Interfaces\ReviewRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ReviewRepository implements ReviewRepositoryInterface
{
    public function findById(int $id): ?Review
    {
        return Review::find($id);
    }

    public function findByBookingId(int $bookingId): ?Review
    {
        return Review::where('booking_id', $bookingId)->first();
    }

    public function create(array $data): Review
    {
        return Review::create($data);
    }

    public function update(Review $review, array $data): Review
    {
        $review->update($data);

        return $review->refresh();
    }

    public function paginateByHotel(int $hotelId, int $perPage = 10): LengthAwarePaginator
    {
        return Review::with('user')
            ->where('hotel_id', $hotelId)
            ->latest()
            ->paginate($perPage);
    }

    public function delete(Review $review): void
    {
        $review->delete();
    }
}
