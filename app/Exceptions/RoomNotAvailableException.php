<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class RoomNotAvailableException extends Exception
{
    public function __construct(string $message = 'This room is not available for the selected date range.')
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'Room Not Available',
            'message' => $this->getMessage(),
        ], 409);
    }
}
