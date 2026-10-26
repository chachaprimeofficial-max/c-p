<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) && is_string($password) && strlen($password) >= 12) {
            User::updateOrCreate(
                ['email' => $email],
                ['name' => 'ChachaPrime Administrator', 'password' => Hash::make($password), 'is_admin' => true, 'email_verified_at' => now()]
            );
        }
    }
}
