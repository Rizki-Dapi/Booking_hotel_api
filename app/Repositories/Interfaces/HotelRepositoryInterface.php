<?php

namespace App\Repositories\Interfaces;

use App\Models\Hotel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface HotelRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?Hotel;

    public function create(array $data): Hotel;

    public function update(Hotel $hotel, array $data): Hotel;

    public function delete(Hotel $hotel): void;

    public function hasRoomTypes(Hotel $hotel): bool;

    public function recommended(int $limit = 5): Collection;
}
