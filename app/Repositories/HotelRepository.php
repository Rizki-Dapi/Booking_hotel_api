<?php

namespace App\Repositories;

use App\Models\Hotel;
use App\Repositories\Interfaces\HotelRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class HotelRepository implements HotelRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return Hotel::query()
            ->when($filters['city'] ?? null, fn ($q, $city) => $q->where('city', 'ilike', "%{$city}%"))
            ->when($filters['name'] ?? null, fn ($q, $name) => $q->where('name', 'ilike', "%{$name}%"))
            ->when($filters['min_star_rating'] ?? null, fn ($q, $rating) => $q->where('star_rating', '>=', $rating))
            ->with('roomTypes')
            ->paginate($perPage);
    }

    public function findById(int $id): ?Hotel
    {
        return Hotel::with('roomTypes.rooms')->find($id);
    }

    public function create(array $data): Hotel
    {
        return Hotel::create($data);
    }

    public function update(Hotel $hotel, array $data): Hotel
    {
        $hotel->update($data);

        return $hotel->refresh();
    }

    public function delete(Hotel $hotel): void
    {
        $hotel->delete();
    }

    public function hasRoomTypes(Hotel $hotel): bool
    {
        return $hotel->roomTypes()->exists();
    }

    public function recommended(int $limit = 5): Collection
    {
        return Hotel::withAvg('reviews', 'rating')
            ->whereHas('reviews')
            ->with('roomTypes')
            ->orderByDesc('reviews_avg_rating')
            ->limit($limit)
            ->get();
    }
}
