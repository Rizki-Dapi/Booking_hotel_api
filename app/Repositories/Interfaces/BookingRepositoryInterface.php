<?php

namespace App\Repositories\Interfaces;

use App\Exceptions\RoomNotAvailableException;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface BookingRepositoryInterface
{
    /**
     * @throws RoomNotAvailableException
     */
    public function createBooking(array $data): Booking;

    /**
     * @throws RoomNotAvailableException
     */
    public function reschedule(Booking $booking, string $checkIn, string $checkOut, float $totalPrice): Booking;

    public function paginateAll(?string $search = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?Booking;

    public function findByCode(string $bookingCode): ?Booking;

    public function paginateByUser(int $userId, ?array $statuses = null, int $perPage = 10): LengthAwarePaginator;

    public function getNonPending(): Collection;

    public function updateStatus(Booking $booking, string $status): Booking;

    public function cancel(Booking $booking): Booking;

    public function findExpirablePending(int $hours): Collection;
}
