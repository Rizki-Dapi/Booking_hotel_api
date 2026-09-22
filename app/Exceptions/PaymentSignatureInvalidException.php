<?php

namespace App\Exceptions;

use Exception;


class PaymentSignatureInvalidException extends Exception
{
    public function __construct(string $message = 'Invalid payment notification signature.')
    {
        parent::__construct($message);
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'error' => 'Forbidden',
            'message' => $this->getMessage(),
        ], 403);
    }
}
