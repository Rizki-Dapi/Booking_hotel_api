<?php

namespace App\Services;

use App\Exceptions\ResourceInUseException;
use App\Models\RoomType;
use App\Repositories\Interfaces\LogRepositoryInterface;
use App\Repositories\Interfaces\RoomTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RoomTypeService
{
    public function __construct(
        private readonly RoomTypeRepositoryInterface $roomTypeRepository,
        private readonly LogRepositoryInterface $logRepository,
    ) {}

    public function listByHotel(int $hotelId, ?string $search = null): Collection
    {
        return $this->roomTypeRepository->listByHotel($hotelId, $search);
    }

    public function find(int $id): RoomType
    {
        $roomType = $this->roomTypeRepository->findById($id);

        if (! $roomType) {
            throw new ModelNotFoundException('Room type not found.');
        }

        return $roomType;
    }

    public function create(array $data): RoomType
    {
        $roomType = $this->roomTypeRepository->create($data);

        $this->logRepository->record([
            'action' => 'admin.room_type_created',
            'context' => ['room_type_id' => $roomType->id, 'hotel_id' => $roomType->hotel_id],
        ]);

        return $roomType;
    }

    public function update(RoomType $roomType, array $data): RoomType
    {
        $updated = $this->roomTypeRepository->update($roomType, $data);

        $this->logRepository->record([
            'action' => 'admin.room_type_updated',
            'context' => ['room_type_id' => $roomType->id, 'fields' => array_keys($data)],
        ]);

        return $updated;
    }

    /**
     * @throws ResourceInUseException
     */
    public function delete(RoomType $roomType): void
    {
        if ($this->roomTypeRepository->hasRooms($roomType)) {
            throw new ResourceInUseException(
                'Cannot delete this room type because it still has rooms. Delete the rooms first.'
            );
        }

        $this->roomTypeRepository->delete($roomType);

        $this->logRepository->record([
            'action' => 'admin.room_type_deleted',
            'context' => ['room_type_id' => $roomType->id],
        ]);
    }
}
