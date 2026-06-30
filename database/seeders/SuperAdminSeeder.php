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
        // ບໍ່ ມີ default ຄາດເດົາ ໄດ້ — ຖ້າ env ບໍ່ ຕັ້ງ → ສ້າງ ລະຫัດ ສຸ່ມ ແລ້ວ ສະແດງ ເທື່ອ ດຽວ.
        $password = (string) env('SUPER_ADMIN_PASSWORD');
        if (blank($password)) {
            $password = Str::password(16);
            $this->command?->warn("SUPER_ADMIN_PASSWORD not set — generated a random one: {$password}");
        }

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
