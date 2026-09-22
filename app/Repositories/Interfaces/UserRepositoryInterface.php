<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function create(array $data): User;

    public function update(User $user, array $data): User;

    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator;

    public function delete(User $user): void;

    public function countByRole(string $role): int;

    public function paginateByRole(
        string $role,
        ?string $search = null,
        int $perPage = 10
    ): LengthAwarePaginator;
}
