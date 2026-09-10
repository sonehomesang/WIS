<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Brings the Satisfaction Survey module into RBAC as its own menu. Creates the
 * survey.* permissions and grants survey.view / survey.edit to whichever roles
 * already hold reports.view / reports.edit — so current access is preserved
 * exactly while the module becomes independently grantable (per-role and
 * per-person). Surgical: uses givePermissionTo (adds), never syncs, so no other
 * role permission is touched. Safe to run on production.
 */
class SurveyPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['view', 'create', 'edit', 'delete', 'activate', 'deactivate'] as $a) {
            Permission::firstOrCreate(['name' => "survey.{$a}", 'guard_name' => 'web']);
        }

        // Mirror existing reports access onto the new survey permission.
        foreach (Role::with('permissions')->get() as $role) {
            $has = $role->permissions->pluck('name');
            if ($has->contains('reports.view') && ! $has->contains('survey.view')) {
                $role->givePermissionTo('survey.view');
            }
            if ($has->contains('reports.edit') && ! $has->contains('survey.edit')) {
                $role->givePermissionTo('survey.edit');
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
