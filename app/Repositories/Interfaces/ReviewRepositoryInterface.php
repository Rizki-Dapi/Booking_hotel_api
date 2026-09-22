<?php

namespace App\Repositories\Interfaces;

use App\Models\Review;
use Illuminate\Pagination\LengthAwarePaginator;

interface ReviewRepositoryInterface
{
    public function findById(int $id): ?Review;

    public function findByBookingId(int $bookingId): ?Review;

    public function create(array $data): Review;

    public function update(Review $review, array $data): Review;

    public function paginateByHotel(int $hotelId, int $perPage = 10): LengthAwarePaginator;

    public function delete(Review $review): void;
}
