<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Applies ONLY the "department_admin" preset (permissions + department scope),
 * without touching any other role — safe to run on production where roles may
 * have been customised through the UI. The full RolePermissionSeeder would
 * reset every role to its seed definition; this one is surgical.
 *
 * department_admin: create/edit borrow·deposit·request for their own department,
 * manage their department's equipment, read inventory/catalog — but NEVER add to
 * the master inventory (no inventory.create/edit), never approve (no activate),
 * never touch admin menus.
 */
class DepartmentAdminPresetSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $perms = [
            'dashboard.view', 'notifications.view',
            'inventory.view', 'catalog.view', 'disposal.view',
            'equipment.view', 'equipment.create', 'equipment.edit', 'equipment.activate', 'equipment.deactivate',
            'borrow.view', 'borrow.create', 'borrow.edit',
            'deposit.view', 'deposit.create', 'deposit.edit',
            'request.view', 'request.create', 'request.edit',
        ];

        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => 'department_admin', 'guard_name' => 'web']);
        $role->scope_rules = [
            'transactionScope' => 'department',
            'inventoryScope' => 'all',
            'catalogScope' => 'all',
            'equipmentScope' => 'department',
        ];
        $role->save();
        $role->syncPermissions($perms);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
