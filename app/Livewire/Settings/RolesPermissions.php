<?php

namespace App\Livewire\Settings;

use App\Models\Role;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\PermissionRegistrar;

#[Layout('layouts.app')]
class RolesPermissions extends Component
{
    /**
     * MUST match RolePermissionSeeder::$menus exactly (23 menus). If a menu is
     * missing here, save()'s syncPermissions() would STRIP that menu's perms from
     * the role on every save (e.g. the disposal + equipment perms were silently wiped).
     *
     * @var list<string>
     */
    public array $menus = [
        'dashboard', 'inventory', 'borrow', 'deposit', 'request', 'da', 'oga', 'expo', 'disposal',
        'catalog', 'equipment', 'supplier', 'units', 'departments', 'locations', 'buildings', 'rooms',
        'users', 'roles', 'settings', 'reports', 'audit', 'notifications',
    ];

    /** @var list<string> */
    public array $actions = ['view', 'create', 'edit', 'delete', 'activate', 'deactivate'];

    public ?int $selectedRoleId = null;

    public string $selectedRoleName = '';

    public array $grants = [];   // [menu][action] => bool

    public array $scope = ['transactionScope' => 'all', 'inventoryScope' => 'all', 'catalogScope' => 'all', 'equipmentScope' => 'all'];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('roles.view'), 403);
        $first = Role::orderBy('id')->first();
        if ($first) {
            $this->loadRole($first->id);
        }
    }

    public function selectRole(int $id): void
    {
        $this->loadRole($id);
    }

    protected function loadRole(int $id): void
    {
        $role = Role::with('permissions')->findOrFail($id);
        $this->selectedRoleId = $role->id;
        $this->selectedRoleName = $role->name;

        $names = $role->permissions->pluck('name')->all();
        $grants = [];
        foreach ($this->menus as $m) {
            foreach ($this->actions as $a) {
                $grants[$m][$a] = in_array("{$m}.{$a}", $names, true);
            }
        }
        $this->grants = $grants;

        $sr = $role->scope_rules ?? [];
        $this->scope = [
            'transactionScope' => $sr['transactionScope'] ?? 'all',
            'inventoryScope' => $sr['inventoryScope'] ?? 'all',
            'catalogScope' => $sr['catalogScope'] ?? 'all',
            'equipmentScope' => $sr['equipmentScope'] ?? 'all',
        ];
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('roles.edit'), 403);
        if ($this->selectedRoleName === 'super_admin') {
            return;   // read-only — super_admin always has everything (Gate::before)
        }

        $role = Role::findOrFail($this->selectedRoleId);

        $perms = [];
        foreach ($this->menus as $m) {
            foreach ($this->actions as $a) {
                if (! empty($this->grants[$m][$a])) {
                    $perms[] = "{$m}.{$a}";
                }
            }
        }
        $role->syncPermissions($perms);
        // merge → ຮັກສາ key scope ອື່ນ (ຖ້າ ມີ) ທີ່ ຍັງ ບໍ່ ໄດ້ surface ໃນ editor
        $role->scope_rules = array_merge($role->scope_rules ?? [], $this->scope);
        $role->save();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->dispatch('saved');
    }

    public function render(): View
    {
        $canEdit = auth()->user()->can('roles.edit') && $this->selectedRoleName !== 'super_admin';

        return view('livewire.settings.roles-permissions', [
            'roles' => Role::orderBy('id')->get(),
            'canEdit' => $canEdit,
        ]);
    }
}
