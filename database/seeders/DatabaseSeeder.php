<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed FROZEN data only (RBAC + super_admins).
     * Master data (units, departments, users, ...) is managed at runtime by admin.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SuperAdminSeeder::class,
            ConditionStatusSeeder::class,
        ]);
    }
}
