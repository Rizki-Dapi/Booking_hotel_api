<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class BookingCannotBeCancelledException extends Exception
{
    public function __construct(string $message = 'This booking cannot be cancelled.')
    {
        parent::__construct($message);
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'error' => 'Unprocessable Entity',
            'message' => $this->getMessage(),
        ], 422);
    }
}
