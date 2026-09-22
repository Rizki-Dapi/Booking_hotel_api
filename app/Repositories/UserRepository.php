<?php

namespace App\Repositories;

use App\Models\Role;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function create(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
        ]);
    }

    public function update(User $user, array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update(array_intersect_key($data, array_flip(['name', 'email', 'phone', 'password'])));

        return $user->refresh();
    }

    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return User::query()
            ->with('roles')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function countByRole(string $role): int
    {
        $roleModel = Role::where('name', $role)->first();

        return $roleModel ? $roleModel->users()->count() : 0;
    }

    public function paginateByRole(
        string $role,
        ?string $search = null,
        int $perPage = 10
    ): LengthAwarePaginator {
        return User::query()
            ->with('roles')
            ->whereHas('roles', function ($query) use ($role) {
                $query->where('name', $role);
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage);
    }
}
