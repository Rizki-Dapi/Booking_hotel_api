<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class BookingCannotBeRescheduledException extends Exception
{
    public function __construct(string $message = 'This booking cannot be rescheduled.')
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'Unprocessable Entity',
            'message' => $this->getMessage(),
        ], 422);
    }
}
