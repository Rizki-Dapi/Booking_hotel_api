<?php

namespace App\Repositories\Interfaces;

use App\Models\Payment;

interface PaymentRepositoryInterface
{
    public function create(array $data): Payment;

    public function findByOrderId(string $midtransOrderId): ?Payment;

    public function updateStatus(Payment $payment, string $status, ?string $transactionId = null): Payment;

    public function updateOrderDetails(Payment $payment, string $orderId, float $amount): Payment;
}
