<?php

namespace App\Repositories\Interfaces;

use App\Models\Room;
use Illuminate\Database\Eloquent\Collection;

interface RoomRepositoryInterface
{
    /**
     * Cari kamar yang benar-benar kosong untuk rentang tanggal tertentu,
     * di sebuah room_type. Dipakai untuk listing "kamar tersedia".
     */
    public function findAvailable(int $roomTypeId, string $checkIn, string $checkOut): Collection;

    public function findById(int $id): ?Room;

    public function listByRoomType(int $roomTypeId, ?string $search = null, ?string $occupancyDate = null): Collection;

    public function create(array $data): Room;

    public function update(Room $room, array $data): Room;

    public function delete(Room $room): void;

    public function hasActiveBookings(Room $room): bool;
}
