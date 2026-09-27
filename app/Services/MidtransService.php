<?php

namespace App\Services;

use App\Exceptions\MidtransTransactionFailedException;
use App\Models\Booking;
use App\Models\SystemLog;
use Midtrans\Snap;
use Throwable;

class MidtransService
{
    public function createSnapToken(Booking $booking, ?string $orderId = null): string
    {
        $booking->loadMissing('user');

        $params = [
            'transaction_details' => [
                'order_id' => $orderId ?? $booking->booking_code,
                'gross_amount' => (int) $booking->total_price,
            ],
            'customer_details' => [
                'first_name' => $booking->user->name,
                'email' => $booking->user->email,
                'phone' => $booking->user->phone,
            ],
        ];

        try {
            return Snap::getSnapToken($params);
        } catch (Throwable $e) {
            try {
                SystemLog::create([
                    'level' => 'error',
                    'message' => 'Midtrans Snap token creation failed',
                    'context' => [
                        'exception' => get_class($e),
                        'error' => $e->getMessage(),
                        'booking_id' => $booking->id,
                        'order_id' => $params['transaction_details']['order_id'],
                    ],
                    'logged_at' => now(),
                ]);
            } catch (Throwable $loggingError) {
                report($loggingError);
            }

            throw new MidtransTransactionFailedException;
        }
    }

    public function isSignatureValid(array $payload): bool
    {
        $expected = hash(
            'sha512',
            ($payload['order_id'] ?? '')
                .($payload['status_code'] ?? '')
                .($payload['gross_amount'] ?? '')
                .config('midtrans.server_key')
        );

        return hash_equals($expected, $payload['signature_key'] ?? '');
    }
}
