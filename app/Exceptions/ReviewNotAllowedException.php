<?php

namespace App\Exceptions;

use Exception;


class ReviewNotAllowedException extends Exception
{
    public function __construct(string $message = 'You are not allowed to review this booking.')
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
