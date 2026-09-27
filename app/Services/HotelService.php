<?php

namespace App\Services;

use App\Exceptions\ResourceInUseException;
use App\Models\Hotel;
use App\Repositories\Interfaces\HotelRepositoryInterface;
use App\Repositories\Interfaces\LogRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

class HotelService
{
    public function __construct(
        private readonly HotelRepositoryInterface $hotelRepository,
        private readonly LogRepositoryInterface $logRepository,
    ) {}

    public function list(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->hotelRepository->paginate($filters, $perPage);
    }

    public function find(int $id): Hotel
    {
        $hotel = $this->hotelRepository->findById($id);

        if (! $hotel) {
            throw new ModelNotFoundException('Hotel not found.');
        }

        return $hotel;
    }

    public function create(array $data): Hotel
    {
        $hotel = $this->hotelRepository->create($data);

        $this->logRepository->record([
            'action' => 'admin.hotel_created',
            'context' => ['hotel_id' => $hotel->id],
        ]);

        return $hotel;
    }

    public function update(Hotel $hotel, array $data): Hotel
    {
        $updated = $this->hotelRepository->update($hotel, $data);

        $this->logRepository->record([
            'action' => 'admin.hotel_updated',
            'context' => ['hotel_id' => $hotel->id, 'fields' => array_keys($data)],
        ]);

        return $updated;
    }

    /**
     * @throws ResourceInUseException
     */
    public function delete(Hotel $hotel): void
    {
        if ($this->hotelRepository->hasRoomTypes($hotel)) {
            throw new ResourceInUseException(
                'Cannot delete this hotel because it still has room types. Delete the room types first.'
            );
        }

        $this->hotelRepository->delete($hotel);

        $this->logRepository->record([
            'action' => 'admin.hotel_deleted',
            'context' => ['hotel_id' => $hotel->id],
        ]);
    }

    public function recommended(int $limit = 5): Collection
    {
        return $this->hotelRepository->recommended($limit);
    }
}
