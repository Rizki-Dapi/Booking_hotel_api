<?php

namespace App\Exceptions;

use Exception;


class ResourceInUseException extends Exception
{
    public function __construct(string $message = 'This resource cannot be deleted because it is still in use.')
    {
        parent::__construct($message);
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'error' => 'Conflict',
            'message' => $this->getMessage(),
        ], 409);
    }
}
