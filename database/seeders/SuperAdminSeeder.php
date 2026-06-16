<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seed the two super_admins (kept across rebuilds). Password from .env
 * SUPER_ADMIN_PASSWORD (never hardcoded); change after first login.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('SUPER_ADMIN_PASSWORD', 'ChangeMe123!');

        $supers = [
            ['email' => 'khamsone@namtheun2.com', 'display_name' => 'Khamsone (NT2)'],
            ['email' => 'khamsone.peng@gmail.com', 'display_name' => 'Khamsone Peng'],
        ];

        foreach ($supers as $s) {
            $user = User::updateOrCreate(
                ['email' => $s['email']],
                [
                    'uuid' => (string) Str::uuid(),
                    'display_name' => $s['display_name'],
                    'password' => Hash::make($password),
                    'status' => 'active',
                    'auth_provider' => 'password',
                    'is_super_admin' => true,
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles(['super_admin']);
        }
    }
}
