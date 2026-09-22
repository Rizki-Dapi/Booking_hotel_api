<?php

namespace App\Exceptions;

use Exception;

class RoomNotAvailableException extends Exception
{
    public function __construct(string $message = 'This room is not available for the selected date range.')
    {
        parent::__construct($message);
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'error' => 'Room Not Available',
            'message' => $this->getMessage(),
        ], 409);
    }
}
