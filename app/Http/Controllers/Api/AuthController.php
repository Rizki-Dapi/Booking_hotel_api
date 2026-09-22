<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());
        $result['user']->load('roles');

        return response()->json(ApiFormatter::createJson('Registration successful', [
            'user' => new UserResource($result['user']),
            'access_token' => $result['token'],
            'token_type' => 'bearer',
        ]), 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());
        $result['user']->load('roles');

        return response()->json(ApiFormatter::createJson('Login successful', [
            'user' => new UserResource($result['user']),
            'access_token' => $result['token'],
            'token_type' => 'bearer',
        ]));
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return response()->json(ApiFormatter::createJson('Logout successful'));
    }

    public function me(): JsonResponse
    {
        $user = $this->authService->me();
        $user->load('roles');

        return response()->json(ApiFormatter::createJson('User retrieved successfully', [
            'user' => new UserResource($user),
        ]));
    }

    public function verifyEmail(int $id, string $hash): JsonResponse
    {
        $result = $this->authService->verifyEmail($id, $hash);

        if (! $result['user']) {
            return response()->json(['error' => 'Forbidden', 'message' => 'Invalid verification link.'], 403);
        }

        $message = $result['already_verified']
            ? 'Email already verified.'
            : 'Email verified successfully.';

        return response()->json(ApiFormatter::createJson($message));
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $sent = $this->authService->resendVerification($request->user());

        $message = $sent
            ? 'Verification link sent.'
            : 'Email already verified.';

        return response()->json(ApiFormatter::createJson($message));
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->sendPasswordResetLink($request->validated('email'));

        return response()->json(ApiFormatter::createJson(
            'A password reset link has been sent.'
        ));
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authService->resetPassword($request->validated());

        return response()->json(ApiFormatter::createJson('Password reset successfully.'));
    }

    public function refresh(): JsonResponse
    {
        $result = $this->authService->refresh();
        $result['user']->load('roles');

        return response()->json(ApiFormatter::createJson('Token refreshed successfully', [
            'user' => new UserResource($result['user']),
            'access_token' => $result['token'],
            'token_type' => 'bearer',
        ]));
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        $this->authService->deleteOwnAccount($request->user());

        return response()->json(ApiFormatter::createJson('Account deleted successfully.'));
    }
}
