<?php

namespace App\Repositories;

use App\Models\RoomType;
use App\Repositories\Interfaces\RoomTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class RoomTypeRepository implements RoomTypeRepositoryInterface
{
    public function findById(int $id): ?RoomType
    {
        return RoomType::with('hotel')->find($id);
    }

    public function listByHotel(int $hotelId, ?string $search = null): Collection
    {
        return RoomType::where('hotel_id', $hotelId)
            ->when($search, fn($q, $name) => $q->where('name', 'ilike', "%{$name}%"))
            ->get();
    }

    public function create(array $data): RoomType
    {
        return RoomType::create($data);
    }

    public function update(RoomType $roomType, array $data): RoomType
    {
        $roomType->update($data);

        return $roomType->refresh();
    }

    public function delete(RoomType $roomType): void
    {
        $roomType->delete();
    }

    public function hasRooms(RoomType $roomType): bool
    {
        return $roomType->rooms()->exists();
    }
}
