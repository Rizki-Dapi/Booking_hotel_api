<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentAlreadyProcessedException;
use App\Exceptions\PaymentSignatureInvalidException;
use App\Models\Booking;
use App\Repositories\Interfaces\BookingRepositoryInterface;
use App\Repositories\Interfaces\LogRepositoryInterface;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly BookingRepositoryInterface $bookingRepository,
        private readonly MidtransService $midtransService,
        private readonly LogRepositoryInterface $logRepository,
    ) {}

    public function createForBooking(Booking $booking): array
    {
        $payment = $this->paymentRepository->create([
            'booking_id' => $booking->id,
            'midtrans_order_id' => $booking->booking_code,
            'amount' => $booking->total_price,
            'status' => PaymentStatus::PENDING,
        ]);

        $snapToken = $this->midtransService->createSnapToken($booking);

        return ['payment' => $payment, 'snap_token' => $snapToken];
    }

    /**
     * @throws PaymentSignatureInvalidException
     * @throws PaymentAlreadyProcessedException
     */
    public function handleWebhook(array $payload): void
    {
        if (! $this->midtransService->isSignatureValid($payload)) {
            throw new PaymentSignatureInvalidException();
        }

        $payment = $this->paymentRepository->findByOrderId($payload['order_id'] ?? '');

        if (! $payment) {
            throw new ModelNotFoundException('Payment not found for this order_id.');
        }

        $incomingStatus = $payload['transaction_status'] ?? null;

        if ($payment->status->value === $incomingStatus) {
            throw new PaymentAlreadyProcessedException();
        }

        $newStatus = PaymentStatus::from($incomingStatus);

        $this->paymentRepository->updateStatus(
            $payment,
            $newStatus->value,
            $payload['transaction_id'] ?? null,
        );

        $this->syncBookingStatus($payment->booking, $newStatus);

        $this->logRepository->record([
            'user_id' => $payment->booking->user_id,
            'action' => 'payment.webhook_processed',
            'context' => [
                'booking_id' => $payment->booking_id,
                'order_id' => $payload['order_id'],
                'status' => $incomingStatus,
            ],
        ]);
    }

    private function syncBookingStatus(Booking $booking, PaymentStatus $paymentStatus): void
    {
        $bookingStatus = match ($paymentStatus) {
            PaymentStatus::SETTLEMENT => BookingStatus::CONFIRMED,
            PaymentStatus::EXPIRE, PaymentStatus::CANCEL, PaymentStatus::DENY => BookingStatus::CANCELLED,
            default => null,
        };

        if ($bookingStatus) {
            $this->bookingRepository->updateStatus($booking, $bookingStatus->value);
        }
    }

    public function expireForBooking(Booking $booking): void
    {
        $payment = $booking->payment;

        if ($payment && $payment->status === PaymentStatus::PENDING) {
            $this->paymentRepository->updateStatus($payment, PaymentStatus::EXPIRE->value);
        }
    }

    public function regenerateForBooking(Booking $booking): array
    {
        $payment = $booking->payment;

        if (! $payment) {
            return $this->createForBooking($booking);
        }

        $newOrderId = $booking->booking_code . '-R' . strtoupper(Str::random(4));

        $this->paymentRepository->updateOrderDetails($payment, $newOrderId, (float) $booking->total_price);

        $snapToken = $this->midtransService->createSnapToken($booking, $newOrderId);

        return ['payment' => $payment->refresh(), 'snap_token' => $snapToken];
    }
}
