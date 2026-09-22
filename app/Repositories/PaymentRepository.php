<?php

namespace App\Repositories;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Repositories\Interfaces\PaymentRepositoryInterface;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function create(array $data): Payment
    {
        return Payment::create($data);
    }

    public function findByOrderId(string $midtransOrderId): ?Payment
    {
        return Payment::with('booking')
            ->where('midtrans_order_id', $midtransOrderId)
            ->first();
    }

    public function updateStatus(Payment $payment, string $status, ?string $transactionId = null): Payment
    {
        $payment->update([
            'status' => $status,
            'midtrans_transaction_id' => $transactionId ?? $payment->midtrans_transaction_id,
            'paid_at' => $status === PaymentStatus::SETTLEMENT->value ? now() : $payment->paid_at,
        ]);

        return $payment->refresh();
    }

    public function updateOrderDetails(Payment $payment, string $orderId, float $amount): Payment
    {
        $payment->update([
            'midtrans_order_id' => $orderId,
            'amount' => $amount,
            'status' => PaymentStatus::PENDING->value,
            'midtrans_transaction_id' => null,
            'paid_at' => null,
        ]);

        return $payment->refresh();
    }
}
