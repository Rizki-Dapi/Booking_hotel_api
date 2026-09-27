<?php

namespace App\Repositories;

use App\Enums\RoomStatus;
use App\Models\Room;
use App\Repositories\Interfaces\RoomRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class RoomRepository implements RoomRepositoryInterface
{
    public function findAvailable(int $roomTypeId, string $checkIn, string $checkOut): Collection
    {
        return Room::where('room_type_id', $roomTypeId)
            ->where('status', RoomStatus::AVAILABLE->value)
            ->whereDoesntHave('bookings', function ($query) use ($checkIn, $checkOut) {
                $query->whereNotIn('status', ['cancelled', 'expired'])
                    ->where('check_in_date', '<', $checkOut)
                    ->where('check_out_date', '>', $checkIn);
            })
            ->get();
    }

    public function findById(int $id): ?Room
    {
        return Room::with('roomType.hotel')->find($id);
    }

    public function listByRoomType(int $roomTypeId, ?string $search = null, ?string $occupancyDate = null): Collection
    {
        return Room::where('room_type_id', $roomTypeId)
            ->when($search, fn ($q, $number) => $q->where('room_number', 'ilike', "%{$number}%"))
            ->when($occupancyDate, function ($query, $date) {
                $query->with(['bookings' => function ($bookingQuery) use ($date) {
                    $bookingQuery->whereNotIn('status', ['cancelled', 'expired'])
                        ->where('check_in_date', '<=', $date)
                        ->where('check_out_date', '>', $date);
                }]);
            })
            ->get();
    }

    public function create(array $data): Room
    {
        return Room::create($data);
    }

    public function update(Room $room, array $data): Room
    {
        $room->update($data);

        return $room->refresh();
    }

    public function delete(Room $room): void
    {
        $room->delete();
    }

    public function hasActiveBookings(Room $room): bool
    {
        return $room->bookings()
            ->whereNotIn('status', ['cancelled', 'expired', 'completed'])
            ->exists();
    }
}
