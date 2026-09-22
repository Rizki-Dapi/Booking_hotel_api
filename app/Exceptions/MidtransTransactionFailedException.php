<?php

namespace App\Exceptions;

use Exception;


class MidtransTransactionFailedException extends Exception
{
    public function __construct(string $message = 'Failed to process transaction with the payment gateway.')
    {
        parent::__construct($message);
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'error' => 'Bad Gateway',
            'message' => $this->getMessage(),
        ], 502);
    }
}
