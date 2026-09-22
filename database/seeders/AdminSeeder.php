<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@hotelbooking.test'],
            [
                'name' => 'Admin',
                'phone' => null,
                'password' => bcrypt('password'),
            ]
        );

        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        $adminTest = User::firstOrCreate(
            [
                'email' => 'admin.test@hotelbooking.test'
            ],
            [
                'name' => 'Admin Test',
                'phone' => '081234567890',
                'password' => bcrypt('password'),
            ]
        );
        if (! $adminTest->hasRole('admin')) {
            $adminTest->assignRole('admin');
        }
    }
}
