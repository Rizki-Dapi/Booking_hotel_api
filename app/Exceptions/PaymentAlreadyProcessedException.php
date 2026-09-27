<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class PaymentAlreadyProcessedException extends Exception
{
    public function __construct(string $message = 'This payment notification has already been processed.')
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], 200);
    }
}
