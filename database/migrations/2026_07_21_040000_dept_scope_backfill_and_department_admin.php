<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Backfill dept on existing transactions from the owner's current department.
        $this->backfill('borrow_records', 'borrower_user_id', 'borrower_dept_id');
        $this->backfill('deposit_records', 'owner_user_id', 'owner_dept_id');
        $this->backfill('material_requests', 'requester_user_id', 'requester_dept_id');

        // 2. department_admin: view its department's transactions + department scope.
        //    (Fresh installs get this from the seeder; this updates existing data.)
        $role = Role::where('name', 'department_admin')->first();
        if ($role) {
            $grant = array_filter(
                ['borrow.view', 'deposit.view', 'request.view'],
                fn ($p) => Permission::where('name', $p)->where('guard_name', 'web')->exists(),
            );
            if ($grant) {
                $role->givePermissionTo($grant);
            }
            $sr = $role->scope_rules ?? [];
            $sr['transactionScope'] = 'department';
            $role->scope_rules = $sr;
            $role->save();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    private function backfill(string $table, string $userCol, string $deptCol): void
    {
        if (! Schema::hasColumn($table, $deptCol)) {
            return;
        }
        foreach (DB::table($table)->whereNull($deptCol)->whereNotNull($userCol)->pluck($userCol, 'id') as $id => $userId) {
            $deptId = DB::table('users')->where('id', $userId)->value('department_id');
            if ($deptId) {
                DB::table($table)->where('id', $id)->update([$deptCol => $deptId]);
            }
        }
    }

    public function down(): void
    {
        $role = Role::where('name', 'department_admin')->first();
        if ($role) {
            $sr = $role->scope_rules ?? [];
            $sr['transactionScope'] = 'own';
            $role->scope_rules = $sr;
            $role->save();
        }
    }
};
