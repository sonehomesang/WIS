<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * RBAC seed — source of truth: docs/v2/RBAC_MATRIX.md (from seed_roles.ts).
 * 22 menus × 6 actions = 132 permissions; 8 roles + scope_rules.
 */
class RolePermissionSeeder extends Seeder
{
    /** @var list<string> */
    private array $menus = [
        'dashboard', 'inventory', 'borrow', 'deposit', 'request', 'da', 'oga', 'expo',
        'catalog', 'equipment', 'supplier', 'units', 'departments', 'locations', 'buildings', 'rooms',
        'users', 'roles', 'settings', 'reports', 'audit', 'notifications',
    ];

    /** @var list<string> */
    private array $actions = ['view', 'create', 'edit', 'delete', 'activate', 'deactivate'];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Create 126 permissions
        foreach ($this->menus as $menu) {
            foreach ($this->actions as $action) {
                Permission::firstOrCreate(['name' => "{$menu}.{$action}", 'guard_name' => 'web']);
            }
        }

        // 2. Roles + matrix + scope_rules
        $matrix = $this->matrix();
        $scopes = $this->scopes();

        foreach ($matrix as $roleName => $perMenu) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->scope_rules = $scopes[$roleName];
            $role->save();

            $perms = [];
            foreach ($perMenu as $menu => $primitive) {
                foreach ($this->expand($primitive) as $action) {
                    $perms[] = "{$menu}.{$action}";
                }
            }
            $role->syncPermissions($perms);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** Expand a permission-set primitive into its action list. */
    private function expand(string $primitive): array
    {
        return match ($primitive) {
            'allTrue' => ['view', 'create', 'edit', 'delete', 'activate', 'deactivate'],
            'adminPerm' => ['view', 'create', 'edit', 'activate', 'deactivate'], // all minus delete
            'viewOnly' => ['view'],
            'viewCreateEdit' => ['view', 'create', 'edit'],
            'viewCreateActivate' => ['view', 'create', 'activate'],
            'viewCreateEditDeactivate' => ['view', 'create', 'edit', 'deactivate'],
            'allFalse' => [],
        };
    }

    /** Fill all menus with one primitive. */
    private function allMenus(string $primitive): array
    {
        return array_fill_keys($this->menus, $primitive);
    }

    /** role => [menu => primitive] — verbatim from RBAC_MATRIX.md. */
    private function matrix(): array
    {
        $superAdmin = $this->allMenus('allTrue');

        // admin = Full CRUD (ລວມ delete) ທຸກໂມດູລ; ຍົກເວັ້ນ roles (RBAC ແກ້ໄດ້ສະເພาะ super_admin)
        $admin = $this->allMenus('allTrue');
        $admin['roles'] = 'viewOnly';

        $warehouse = $this->allMenus('viewOnly');
        foreach (['inventory', 'borrow', 'deposit', 'da', 'oga', 'expo', 'equipment'] as $m) {
            $warehouse[$m] = 'adminPerm';
        }
        foreach (['roles', 'settings', 'audit'] as $m) {
            $warehouse[$m] = 'allFalse';
        }

        $approver = $this->allMenus('viewOnly');
        foreach (['borrow', 'deposit', 'request', 'da'] as $m) {
            $approver[$m] = 'viewCreateActivate';
        }
        foreach (['users', 'roles', 'settings', 'audit', 'equipment'] as $m) {
            $approver[$m] = 'allFalse';
        }

        $lineManager = $approver; // same permissions; scope differs

        $requester = $this->allMenus('allFalse');
        $requester['dashboard'] = 'viewOnly';
        foreach (['borrow', 'deposit', 'request'] as $m) {
            $requester[$m] = 'viewCreateEditDeactivate';
        }
        // ຜູ້ໃຊ້ທົ່ວໄປ ເຫັນ ສະເພาະ: inventory + catalog (Shops). DA/OGA/Expo ເຊື່ອງ
        // ໂດຍ default — admin ເປີດ ໃຫ້ ລາຍ ບຸກຄົນ ໄດ້ ຜ່ານ ສິດ ໂດຍກົງ (Settings › Users).
        foreach (['inventory', 'catalog'] as $m) {
            $requester[$m] = 'viewOnly';
        }
        $requester['notifications'] = 'viewOnly';

        $supplier = $this->allMenus('allFalse');
        $supplier['dashboard'] = 'viewOnly';
        $supplier['request'] = 'viewOnly';
        $supplier['oga'] = 'viewOnly';
        $supplier['catalog'] = 'viewCreateEdit';
        $supplier['notifications'] = 'viewOnly';

        // department_admin = ບໍລິຫານ Equipment & Tools ຂອງ ພະແນກ ຕົນ ເທົ່ານັ້ນ (ນ້ອຍ ກວ່າ SA).
        // ສິດ equipment = adminPerm (ບໍ່ ມີ delete); ໂມດູລ ອື່ນ ເບິ່ງ ຢ່າງ ດຽວ. scope = ພະແນກ (ເບິ່ງ scopes()).
        $deptAdmin = $this->allMenus('allFalse');
        $deptAdmin['dashboard'] = 'viewOnly';
        $deptAdmin['inventory'] = 'viewOnly';
        $deptAdmin['notifications'] = 'viewOnly';
        $deptAdmin['equipment'] = 'adminPerm';
        // ເບິ່ງ transaction ຂອງ ພະແນກ ຕົນ (scope = department, ເບິ່ງ scopes())
        foreach (['borrow', 'deposit', 'request'] as $m) {
            $deptAdmin[$m] = 'viewOnly';
        }

        return [
            'super_admin' => $superAdmin,
            'admin' => $admin,
            'warehouse_staff' => $warehouse,
            'approver' => $approver,
            'line_manager' => $lineManager,
            'requester' => $requester,
            'supplier' => $supplier,
            'department_admin' => $deptAdmin,
        ];
    }

    /** role => scope_rules. */
    private function scopes(): array
    {
        return [
            'super_admin' => ['transactionScope' => 'all', 'inventoryScope' => 'all', 'catalogScope' => 'all', 'equipmentScope' => 'all'],
            'admin' => ['transactionScope' => 'all', 'inventoryScope' => 'all', 'catalogScope' => 'all', 'equipmentScope' => 'all'],
            'warehouse_staff' => ['transactionScope' => 'all', 'inventoryScope' => 'all', 'catalogScope' => 'all', 'equipmentScope' => 'all'],
            'approver' => ['transactionScope' => 'assigned', 'inventoryScope' => 'all', 'catalogScope' => 'all', 'equipmentScope' => 'all'],
            'line_manager' => ['transactionScope' => 'assigned', 'inventoryScope' => 'all', 'catalogScope' => 'all', 'equipmentScope' => 'all'],
            'requester' => ['transactionScope' => 'own', 'inventoryScope' => 'all', 'catalogScope' => 'all', 'equipmentScope' => 'all'],
            'supplier' => ['transactionScope' => 'own_orders', 'inventoryScope' => 'none', 'catalogScope' => 'own_supplier', 'equipmentScope' => 'none'],
            // department_admin — ເຫັນ/ຈັດການ ສະເພາະ ເຄື່ອງ ຂອງ ພະແນກ ຕົນ.
            'department_admin' => ['transactionScope' => 'department', 'inventoryScope' => 'all', 'catalogScope' => 'all', 'equipmentScope' => 'department'],
        ];
    }
}
