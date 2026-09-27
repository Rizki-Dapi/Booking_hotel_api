<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class CannotModifySelfException extends Exception
{
    public function __construct(string $message = 'You cannot perform this action on your own account.')
    {
        parent::__construct($message);
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'error' => 'Forbidden',
            'message' => $this->getMessage(),
        ], 422);
    }
}
