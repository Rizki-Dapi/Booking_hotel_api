<?php

namespace App\Services;

use App\Exceptions\CannotModifySelfException;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Interfaces\LogRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly LogRepositoryInterface $logRepository,
    ) {}

    public function list(?string $search, int $perPage): LengthAwarePaginator
    {
        return $this->userRepository->paginate($search, $perPage);
    }

    public function find(int $id): User
    {
        return User::with('roles')->findOrFail($id);
    }

    public function update(User $user, array $data): User
    {
        $updated = $this->userRepository->update($user, $data);

        $this->logRepository->record([
            'action' => 'admin.user_updated',
            'context' => ['target_user_id' => $user->id, 'fields' => array_keys($data)],
        ]);

        return $updated->load('roles');
    }

    /**
     * @throws CannotModifySelfException
     */
    public function delete(User $target, User $actingAdmin): void
    {
        if ($target->id === $actingAdmin->id) {
            throw new CannotModifySelfException('You cannot delete your own account.');
        }

        $this->userRepository->delete($target);

        $this->logRepository->record([
            'user_id' => $actingAdmin->id,
            'action' => 'admin.user_deleted',
            'context' => ['target_user_id' => $target->id],
        ]);
    }

    /**
     * @throws CannotModifySelfException
     */
    public function changeRole(User $target, string $role, User $actingAdmin): User
    {
        if ($target->id === $actingAdmin->id) {
            throw new CannotModifySelfException('You cannot change your own role.');
        }

        $target->assignRole($role);

        $this->logRepository->record([
            'user_id' => $actingAdmin->id,
            'action' => 'admin.role_changed',
            'context' => ['target_user_id' => $target->id, 'new_role' => $role],
        ]);

        return $target->load('roles');
    }

    public function updateProfile(User $user, array $data): User
    {
        $updated = $this->userRepository->update($user, $data);

        $this->logRepository->record([
            'user_id' => $user->id,
            'action' => 'user.profile_updated',
            'context' => ['fields' => array_keys($data)],
        ]);

        return $updated;
    }

    public function countByRole(string $role): int
    {
        $roleModel = Role::where('name', $role)->first();

        return $roleModel ? $roleModel->users()->count() : 0;
    }

    public function listByRole(
        string $role,
        ?string $search,
        int $perPage
    ): LengthAwarePaginator {
        return $this->userRepository->paginateByRole(
            $role,
            $search,
            $perPage
        );
    }
}
