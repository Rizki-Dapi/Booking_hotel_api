<?php

namespace App\Repositories\Interfaces;

use App\Models\RoomType;
use Illuminate\Database\Eloquent\Collection;

interface RoomTypeRepositoryInterface
{
    public function findById(int $id): ?RoomType;

    public function listByHotel(int $hotelId, ?string $search = null): Collection;

    public function create(array $data): RoomType;

    public function update(RoomType $roomType, array $data): RoomType;

    public function delete(RoomType $roomType): void;

    public function hasRooms(RoomType $roomType): bool;
}
