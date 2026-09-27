<?php

namespace App\Repositories;

use App\Enums\BookingStatus;
use App\Enums\RoomStatus;
use App\Exceptions\RoomNotAvailableException;
use App\Models\Booking;
use App\Models\Room;
use App\Repositories\Interfaces\BookingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingRepository implements BookingRepositoryInterface
{
    public function createBooking(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            $rooms = Room::where('room_type_id', $data['room_type_id'])
                ->where('status', RoomStatus::AVAILABLE->value)
                ->lockForUpdate()
                ->get();

            if ($rooms->isEmpty()) {
                throw new RoomNotAvailableException('No available rooms for this room type.');
            }

            $bookedRoomIds = Booking::whereIn('room_id', $rooms->pluck('id'))
                ->whereNotIn('status', [BookingStatus::CANCELLED->value, BookingStatus::EXPIRED->value])
                ->where('check_in_date', '<', $data['check_out_date'])
                ->where('check_out_date', '>', $data['check_in_date'])
                ->pluck('room_id');

            $availableRoom = $rooms->first(fn (Room $room) => ! $bookedRoomIds->contains($room->id));

            if (! $availableRoom) {
                throw new RoomNotAvailableException(
                    'All rooms of this type are fully booked for the selected dates.'
                );
            }

            return Booking::create([
                'booking_code' => $this->generateBookingCode(),
                'user_id' => $data['user_id'],
                'room_id' => $availableRoom->id,
                'check_in_date' => $data['check_in_date'],
                'check_out_date' => $data['check_out_date'],
                'total_price' => $data['total_price'],
                'status' => BookingStatus::PENDING,
            ]);
        });
    }

    public function reschedule(Booking $booking, string $checkIn, string $checkOut, float $totalPrice): Booking
    {
        return DB::transaction(function () use ($booking, $checkIn, $checkOut, $totalPrice) {
            Room::where('id', $booking->room_id)->lockForUpdate()->firstOrFail();

            $isOverlapping = Booking::overlapping(
                roomId: $booking->room_id,
                checkIn: $checkIn,
                checkOut: $checkOut,
                excludeBookingId: $booking->id,
            )->lockForUpdate()->get()->isNotEmpty();

            if ($isOverlapping) {
                throw new RoomNotAvailableException(
                    'This room is already booked by someone else for the new dates.'
                );
            }

            $booking->update([
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'total_price' => $totalPrice,
            ]);

            return $booking->refresh();
        });
    }

    public function paginateAll(?string $search = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return Booking::with(['user', 'room.roomType.hotel', 'payment'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('booking_code', 'ilike', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'ilike', "%{$search}%")
                                ->orWhere('email', 'ilike', "%{$search}%");
                        });
                });
            })
            ->when($status, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): ?Booking
    {
        return Booking::with(['room.roomType.hotel', 'payment'])->find($id);
    }

    public function findByCode(string $bookingCode): ?Booking
    {
        return Booking::with(['room.roomType.hotel', 'payment'])
            ->where('booking_code', $bookingCode)
            ->first();
    }

    public function paginateByUser(int $userId, ?array $statuses = null, int $perPage = 10): LengthAwarePaginator
    {
        return Booking::with(['room.roomType.hotel', 'payment'])
            ->where('user_id', $userId)
            ->when($statuses, fn ($q, $statuses) => $q->whereIn('status', $statuses))
            ->latest()
            ->paginate($perPage);
    }

    public function getNonPending(): Collection
    {
        return Booking::whereNotIn('status', [BookingStatus::PENDING->value])->get();
    }

    public function updateStatus(Booking $booking, string $status): Booking
    {
        $booking->update(['status' => $status]);

        return $booking->refresh();
    }

    public function cancel(Booking $booking): Booking
    {
        return $this->updateStatus($booking, BookingStatus::CANCELLED->value);
    }

    private function generateBookingCode(): string
    {
        do {
            $code = 'BK-'.strtoupper(Str::random(8));
        } while (Booking::where('booking_code', $code)->exists());

        return $code;
    }

    public function findExpirablePending(int $hours): Collection
    {
        return Booking::with('payment')
            ->where('status', BookingStatus::PENDING->value)
            ->where('created_at', '<=', now()->subHours($hours))
            ->get();
    }
}
