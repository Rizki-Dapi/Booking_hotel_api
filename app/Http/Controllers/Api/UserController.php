<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUpdateUserRequest;
use App\Http\Requests\AssignRoleRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function index(Request $request): JsonResponse
    {
        $users = $this->userService->list(
            search: $request->query('search'),
            perPage: (int) $request->query('per_page', 10),
        );

        return response()->json(ApiFormatter::createJson('Users retrieved successfully', [
            'users' => UserResource::collection($users->items()),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]));
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->userService->find($id);

        return response()->json(ApiFormatter::createJson('User retrieved successfully', [
            'user' => new UserResource($user),
        ]));
    }

    public function update(AdminUpdateUserRequest $request, User $user): JsonResponse
    {
        $updated = $this->userService->update($user, $request->validated());

        return response()->json(ApiFormatter::createJson('User updated successfully', [
            'user' => new UserResource($updated),
        ]));
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->userService->delete($user, $request->user());

        return response()->json(ApiFormatter::createJson('User deleted successfully'));
    }

    public function assignRole(AssignRoleRequest $request, User $user): JsonResponse
    {
        $updated = $this->userService->changeRole($user, $request->validated('role'), $request->user());

        return response()->json(ApiFormatter::createJson('User role updated successfully', [
            'user' => new UserResource($updated),
        ]));
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->userService->updateProfile($request->user(), $request->validated());
        $user->load('roles');

        return response()->json(ApiFormatter::createJson('Profile updated successfully', [
            'user' => new UserResource($user),
        ]));
    }

    public function indexByRole(Request $request, string $role): JsonResponse
    {
        $users = $this->userService->listByRole(
            role: $role,
            search: $request->query('search'),
            perPage: (int) $request->query('per_page', 10),
        );

        return response()->json(ApiFormatter::createJson(
            'Users by role retrieved successfully',
            [
                'users' => UserResource::collection($users->items()),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ],
            ]
        ));
    }
}
