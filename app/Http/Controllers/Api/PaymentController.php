<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService) {}

    public function webhook(Request $request): JsonResponse
    {
        $this->paymentService->handleWebhook($request->all());

        return response()->json(['message' => 'Notification processed']);
    }
}
