<?php

namespace App\Services;

use App\Exceptions\ResourceInUseException;
use App\Models\Room;
use App\Repositories\Interfaces\LogRepositoryInterface;
use App\Repositories\Interfaces\RoomRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RoomService
{
    public function __construct(
        private readonly RoomRepositoryInterface $roomRepository,
        private readonly LogRepositoryInterface $logRepository,
    ) {}

    public function listByRoomType(int $roomTypeId, ?string $search = null, ?string $occupancyDate = null): Collection
    {
        return $this->roomRepository->listByRoomType($roomTypeId, $search, $occupancyDate);
    }

    public function findAvailable(int $roomTypeId, string $checkIn, string $checkOut): Collection
    {
        return $this->roomRepository->findAvailable($roomTypeId, $checkIn, $checkOut);
    }

    public function find(int $id): Room
    {
        $room = $this->roomRepository->findById($id);

        if (! $room) {
            throw new ModelNotFoundException('Room not found.');
        }

        return $room;
    }

    public function create(array $data): Room
    {
        $room = $this->roomRepository->create($data);

        $this->logRepository->record([
            'action' => 'admin.room_created',
            'context' => ['room_id' => $room->id, 'room_type_id' => $room->room_type_id],
        ]);

        return $room;
    }

    public function update(Room $room, array $data): Room
    {
        $updated = $this->roomRepository->update($room, $data);

        $this->logRepository->record([
            'action' => 'admin.room_updated',
            'context' => ['room_id' => $room->id, 'fields' => array_keys($data)],
        ]);

        return $updated;
    }

    /**
     * @throws ResourceInUseException
     */
    public function delete(Room $room): void
    {
        if ($this->roomRepository->hasActiveBookings($room)) {
            throw new ResourceInUseException(
                'Cannot delete this room because it still has active bookings.'
            );
        }

        $this->roomRepository->delete($room);

        $this->logRepository->record([
            'action' => 'admin.room_deleted',
            'context' => ['room_id' => $room->id],
        ]);
    }
}
