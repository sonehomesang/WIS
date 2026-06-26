<?php

namespace App\Livewire\Settings;

use App\Models\Department;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Users extends Component
{
    use WithPagination;

    public string $search = '';

    /** A-Z group filter (ໂຕອັກສອນຂຶ້ນໜ້າ display_name); ວ່າງ = ທັງໝົດ. */
    public string $letter = '';

    // Modal + form
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $display_name = '';

    public string $email = '';

    public string $password = '';

    public string $role = '';

    public ?int $unit_id = null;

    public ?int $department_id = null;

    /** ສຳລັບ role=supplier — ຜູກ user ກັບ supplier (ໃຊ້ scope catalog/request/oga ຂອງ portal). */
    public ?int $supplier_id = null;

    public string $status = 'active';

    /** ສິດ ເພີ່ມເຕີມ ລາຍ ບຸກຄົນ — menu keys ທີ່ ເປີດ ໃຫ້ ໂດຍກົງ (ນອກ ເໜືອ ບົດບາດ). */
    public array $extraMenus = [];

    /**
     * ເມນູ ປະຕິບັດການ ທີ່ admin ເປີດ ໃຫ້ ລາຍ ບຸກຄົນ ໄດ້ (ເບິ່ງ+ເພີ່ມ+ແກ້).
     * ບໍ່ ລວມ ເມນູ admin (users/roles/settings/audit/reports) — ກັນ ການ ຍົກ ສິດ ຕົນເອງ.
     *
     * @return array<string,string>
     */
    public static function grantableMenus(): array
    {
        return [
            'inventory' => 'WH Inventories',
            'borrow' => 'Borrowing Material/Tools',
            'deposit' => 'Deposit Material/Equipment',
            'request' => 'Request Material',
            'catalog' => 'Shops Material',
            'da' => 'DA Claims',
            'oga' => 'OGA',
            'expo' => 'Expo Info',
        ];
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->can('users.view'), 403);
    }

    public function updatedUnitId(): void
    {
        $this->department_id = null;   // reset department when unit changes
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /** ເລືອກ/ຍົກເລີກ ໂຕອັກສອນ A-Z (ກົດຊ້ຳ = ລ້າງ). */
    public function setLetter(string $l): void
    {
        $this->letter = ($this->letter === $l) ? '' : $l;
        $this->resetPage();
    }

    public function newUser(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function editUser(int $id): void
    {
        $user = User::with('roles')->findOrFail($id);
        $this->editingId = $user->id;
        $this->display_name = $user->display_name;
        $this->email = $user->email;
        $this->password = '';
        $this->role = $user->roles->first()?->name ?? '';
        $this->unit_id = $user->unit_id;
        $this->department_id = $user->department_id;
        $this->supplier_id = $user->supplier_id;
        $this->status = $user->status;

        // ໂຫຼດ ສິດ ເພີ່ມເຕີມ ໂດຍກົງ (ບໍ່ ນັບ ສິດ ທີ່ ມາ ຈาก ບົດບາດ)
        $direct = $user->getDirectPermissions()->pluck('name')->all();
        $this->extraMenus = array_values(array_filter(
            array_keys(self::grantableMenus()),
            fn ($m) => in_array("{$m}.view", $direct, true),
        ));

        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('users.'.($this->editingId ? 'edit' : 'create')), 403);
        // only a super_admin may grant the super_admin role (it carries every permission)
        abort_unless(auth()->user()->is_super_admin || $this->role !== 'super_admin', 403);

        $data = $this->validate([
            'display_name' => ['required', 'string', 'max:256'],
            'email' => ['required', 'email', 'max:256', Rule::unique('users', 'email')->ignore($this->editingId)],
            'password' => $this->editingId ? ['nullable', 'min:8'] : ['required', 'min:8'],
            'role' => ['required', 'exists:roles,name'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'supplier_id' => [$this->role === 'supplier' ? 'required' : 'nullable', 'exists:suppliers,id'],
            'status' => ['required', 'in:active,pending,locked'],
        ], [], ['supplier_id' => 'Supplier']);

        $attrs = [
            'display_name' => $data['display_name'],
            'email' => $data['email'],
            'unit_id' => $data['unit_id'] ?: null,
            'department_id' => $data['department_id'] ?: null,
            // supplier_id ສະເພาະ role=supplier; role ອື່ນ → null (ກັນ scope ຄ້າງ)
            'supplier_id' => $this->role === 'supplier' ? ($data['supplier_id'] ?: null) : null,
            'status' => $data['status'],
        ];

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $user->fill($attrs);
            if (filled($this->password)) {
                $user->password = $this->password;   // 'hashed' cast hashes on save
            }
            $user->save();
        } else {
            $user = User::create($attrs + ['password' => $this->password, 'auth_provider' => 'password']);
        }

        $user->syncRoles([$this->role]);

        // ສິດ ເພີ່ມເຕີມ ລາຍ ບຸກຄົນ (ໂດຍກົງ): view+create+edit ຕໍ່ ເມນູ ທີ່ ໝາຍ.
        // intersect ກັບ grantable → ກັນ ການ ໃຫ້ ສິດ admin (escalation).
        $directPerms = [];
        foreach (array_intersect($this->extraMenus, array_keys(self::grantableMenus())) as $m) {
            foreach (['view', 'create', 'edit'] as $a) {
                $directPerms[] = "{$m}.{$a}";
            }
        }
        $user->syncPermissions($directPerms);

        $this->showModal = false;
        $this->dispatch('saved');
    }

    public function approve(int $id): void
    {
        abort_unless(auth()->user()->can('users.activate'), 403);
        User::whereKey($id)->update(['status' => 'active']);
    }

    public function toggleLock(int $id): void
    {
        $user = User::findOrFail($id);
        $target = $user->status === 'locked' ? 'active' : 'locked';
        abort_unless(auth()->user()->can('users.'.($target === 'locked' ? 'deactivate' : 'activate')), 403);
        $user->update(['status' => $target]);
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->display_name = '';
        $this->email = '';
        $this->password = '';
        $this->role = '';
        $this->unit_id = null;
        $this->department_id = null;
        $this->supplier_id = null;
        $this->status = 'active';
        $this->extraMenus = [];
        $this->resetValidation();
    }

    public function render(): View
    {
        $users = User::with(['roles', 'unit', 'department'])
            ->when($this->search, function ($q) {
                $q->where(fn ($w) => $w->where('display_name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%"));
            })
            ->when($this->letter, fn ($q) => $q->where('display_name', 'like', "{$this->letter}%"))
            ->orderBy('display_name')
            ->paginate(9);

        return view('livewire.settings.users', [
            'users' => $users,
            'letters' => range('A', 'Z'),
            'roles' => Role::when(! auth()->user()->is_super_admin, fn ($q) => $q->where('name', '!=', 'super_admin'))
                ->orderBy('name')->pluck('name'),
            'grantableMenus' => self::grantableMenus(),
            'units' => Unit::where('is_active', true)->orderBy('name')->get(),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'formDepartments' => $this->unit_id
                ? Department::where('unit_id', $this->unit_id)->where('is_active', true)->orderBy('name')->get()
                : collect(),
        ]);
    }
}
