<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin User
        User::updateOrCreate(['email' => 'deepinart@gmail.com'], [
            'name' => 'Alin-Constantin Ivana',
            'password' => bcrypt('adelante'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

    }
}
