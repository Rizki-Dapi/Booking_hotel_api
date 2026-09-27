<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Exceptions\BookingCannotBeCancelledException;
use App\Exceptions\BookingCannotBeRescheduledException;
use App\Exceptions\RoomNotAvailableException;
use App\Models\Booking;
use App\Models\User;
use App\Repositories\Interfaces\BookingRepositoryInterface;
use App\Repositories\Interfaces\LogRepositoryInterface;
use App\Repositories\Interfaces\RoomTypeRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

class BookingService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookingRepository,
        private readonly RoomTypeRepositoryInterface $roomTypeRepository,
        private readonly PaymentService $paymentService,
        private readonly LogRepositoryInterface $logRepository,
    ) {}

    /**
     * @throws ModelNotFoundException
     * @throws RoomNotAvailableException
     */
    public function createBooking(array $data, User $user): array
    {
        $roomType = $this->roomTypeRepository->findById($data['room_type_id']);

        if (! $roomType) {
            throw new ModelNotFoundException('Room type not found.');
        }

        $totalPrice = $this->calculatePrice(
            $data['check_in_date'],
            $data['check_out_date'],
            (float) $roomType->price_per_night,
        );

        $booking = $this->bookingRepository->createBooking([
            ...$data,
            'user_id' => $user->id,
            'total_price' => $totalPrice,
        ]);

        $paymentResult = $this->paymentService->createForBooking($booking);

        $this->logRepository->record([
            'user_id' => $user->id,
            'action' => 'booking.created',
            'context' => [
                'booking_id' => $booking->id,
                'room_id' => $booking->room_id,
                'total_price' => $totalPrice,
            ],
        ]);

        return ['booking' => $booking, 'snap_token' => $paymentResult['snap_token']];
    }

    /**
     * @throws BookingCannotBeRescheduledException
     * @throws RoomNotAvailableException
     */
    public function reschedule(Booking $booking, array $data): array
    {
        if (! in_array($booking->status, [BookingStatus::PENDING, BookingStatus::CONFIRMED], true)) {
            throw new BookingCannotBeRescheduledException(
                'Only pending or confirmed bookings can be rescheduled.'
            );
        }

        if (Carbon::today()->greaterThan($booking->check_in_date)) {
            throw new BookingCannotBeRescheduledException(
                'Cannot reschedule a booking after the check-in date has passed.'
            );
        }

        $booking->loadMissing('room.roomType');

        $newTotalPrice = $this->calculatePrice(
            $data['check_in_date'],
            $data['check_out_date'],
            (float) $booking->room->roomType->price_per_night,
        );

        $priceChanged = abs($newTotalPrice - (float) $booking->total_price) > 0.001;

        if ($booking->status === BookingStatus::CONFIRMED && $priceChanged) {
            throw new BookingCannotBeRescheduledException(
                'A paid booking can only be moved to dates with the same total price. '
                    .'Cancel this booking and create a new one instead.'
            );
        }

        $updated = $this->bookingRepository->reschedule(
            $booking,
            $data['check_in_date'],
            $data['check_out_date'],
            $newTotalPrice,
        );

        $snapToken = null;

        if ($booking->status === BookingStatus::PENDING && $priceChanged) {
            $snapToken = $this->paymentService->regenerateForBooking($updated)['snap_token'];
        }

        $this->logRepository->record([
            'user_id' => $booking->user_id,
            'action' => 'booking.rescheduled',
            'context' => [
                'booking_id' => $booking->id,
                'new_check_in' => $data['check_in_date'],
                'new_check_out' => $data['check_out_date'],
                'price_changed' => $priceChanged,
            ],
        ]);

        return ['booking' => $updated, 'snap_token' => $snapToken];
    }

    public function listAll(?string $search, ?string $status, int $perPage = 10): LengthAwarePaginator
    {
        return $this->bookingRepository->paginateAll($search, $status, $perPage);
    }

    private function calculatePrice(string $checkIn, string $checkOut, float $pricePerNight): float
    {
        $nights = (int) Carbon::parse($checkIn)->startOfDay()
            ->diffInDays(Carbon::parse($checkOut)->startOfDay());

        return $nights * $pricePerNight;
    }

    public function listActiveForUser(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return $this->bookingRepository->paginateByUser(
            $user->id,
            [BookingStatus::PENDING->value, BookingStatus::CONFIRMED->value],
            $perPage,
        );
    }

    public function listHistoryForUser(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return $this->bookingRepository->paginateByUser(
            $user->id,
            [BookingStatus::COMPLETED->value, BookingStatus::CANCELLED->value, BookingStatus::EXPIRED->value],
            $perPage,
        );
    }

    public function findByCode(string $bookingCode): Booking
    {
        $booking = $this->bookingRepository->findByCode($bookingCode);

        if (! $booking) {
            throw new ModelNotFoundException('Booking not found.');
        }

        return $booking;
    }

    /**
     * @throws BookingCannotBeCancelledException
     */
    public function cancel(Booking $booking): Booking
    {
        if (! in_array($booking->status, [BookingStatus::PENDING, BookingStatus::CONFIRMED], true)) {
            throw new BookingCannotBeCancelledException(
                'Only pending or confirmed bookings can be cancelled.'
            );
        }

        if (Carbon::today()->greaterThan($booking->check_in_date)) {
            throw new BookingCannotBeCancelledException(
                'Cannot cancel a booking after the check-in date has passed.'
            );
        }

        $cancelled = $this->bookingRepository->cancel($booking);

        $this->logRepository->record([
            'user_id' => $booking->user_id,
            'action' => 'booking.cancelled',
            'context' => ['booking_id' => $booking->id],
        ]);

        return $cancelled;
    }

    public function expireUnpaidBookings(int $hours = 24): int
    {
        $bookings = $this->bookingRepository->findExpirablePending($hours);

        foreach ($bookings as $booking) {
            $this->bookingRepository->updateStatus($booking, BookingStatus::EXPIRED->value);
            $this->paymentService->expireForBooking($booking);

            $this->logRepository->record([
                'user_id' => $booking->user_id,
                'action' => 'booking.auto_expired',
                'context' => ['booking_id' => $booking->id, 'hours' => $hours],
            ]);
        }

        return $bookings->count();
    }
}
