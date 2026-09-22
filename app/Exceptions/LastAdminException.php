<?php

namespace App\Exceptions;

use Exception;

class LastAdminException extends Exception
{
    public function __construct(string $message = 'This action would leave the system with no admin accounts.')
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
