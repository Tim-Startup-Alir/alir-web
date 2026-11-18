<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Akun Tim Startup ALIR (admin).
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@alir.test')],
            [
                'name' => 'Tim Startup ALIR',
                'password' => env('ADMIN_PASSWORD', 'password'),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
                'wajib_ganti_sandi' => false,
                'email_verified_at' => now(),
            ],
        );
    }
}
