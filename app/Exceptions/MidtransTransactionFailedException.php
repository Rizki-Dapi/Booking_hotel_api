<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class MidtransTransactionFailedException extends Exception
{
    public function __construct(string $message = 'Failed to process transaction with the payment gateway.')
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'Bad Gateway',
            'message' => $this->getMessage(),
        ], 502);
    }
}
