<?php

namespace App\Livewire\Settings;

use App\Models\Department;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
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

    /** A-Z group filter (ໂຕອັກສອນ ຂຶ້ນ ໜ້າ display_name / username / email); ວ່າງ = ທັງໝົດ. */
    public string $letter = '';

    // Modal + form
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $display_name = '';

    public string $email = '';

    public string $role = '';

    /** ລິ້ງ ຕັ້ງ ລະຫັດ ທີ່ ສ້າງ ໃໝ່ (ໃຫ້ admin ກ໋ອບປີ້ ສົ່ງ ມື ຖ້າ ອີເມລ ຍັງ ບໍ່ ພ້ອມ). */
    public string $setPasswordLink = '';

    public string $setLinkEmail = '';

    public ?int $unit_id = null;

    public ?int $department_id = null;

    /** ສຳລັບ role=supplier — ຜູກ user ກັບ supplier (ໃຊ້ scope catalog/request/oga ຂອງ portal). */
    public ?int $supplier_id = null;

    public string $status = 'active';

    /** ສິດ ເພີ່ມເຕີມ ລາຍ ບຸກຄົນ — menu keys ທີ່ ເປີດ ໃຫ້ ໂດຍກົງ (ນອກ ເໜືອ ບົດບາດ). */
    public array $extraPerms = [];

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
            'equipment' => 'Equipment & Tools',
            'da' => 'DA Claims',
            'oga' => 'OGA',
            'expo' => 'Expo Info',
        ];
    }

    /**
     * Action ທີ່ ເປີດ ໃຫ້ ຕິກ ຕໍ່ ເມນູ (ຕໍ່ ບຸກຄົນ). label = Lao.
     *
     * @return array<string,string>
     */
    public static function grantableActions(): array
    {
        return ['view' => 'ເບິ່ງ', 'create' => 'ເພີ່ມ', 'edit' => 'ແກ້'];
    }

    /** ຕັ້ງ ໂຄງ extraPerms ໃຫ້ ທຸກ ເມນູ/action = false. */
    protected function initExtraPerms(): void
    {
        $this->extraPerms = [];
        foreach (array_keys(self::grantableMenus()) as $m) {
            foreach (array_keys(self::grantableActions()) as $a) {
                $this->extraPerms[$m][$a] = false;
            }
        }
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
        $this->role = $user->roles->first()?->name ?? '';
        $this->unit_id = $user->unit_id;
        $this->department_id = $user->department_id;
        $this->supplier_id = $user->supplier_id;
        $this->status = $user->status;

        // ໂຫຼດ ສິດ ເພີ່ມເຕີມ ໂດຍກົງ (ບໍ່ ນັບ ສິດ ທີ່ ມາ ຈາກ ບົດບາດ)
        $direct = $user->getDirectPermissions()->pluck('name')->all();
        $this->initExtraPerms();
        foreach (array_keys(self::grantableMenus()) as $m) {
            foreach (array_keys(self::grantableActions()) as $a) {
                $this->extraPerms[$m][$a] = in_array("{$m}.{$a}", $direct, true);
            }
        }

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
            // ບໍ່ ມີ ຊ່ອງ password — ຜູ້ໃຊ້ ຕັ້ງ ເອງ ຜ່ານ ລິ້ງ (ບໍ່ ມີ ໃຜ ຮູ້ ລະຫັດ).
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
            // supplier_id ສະເພາະ role=supplier; role ອື່ນ → null (ກັນ scope ຄ້າງ)
            'supplier_id' => $this->role === 'supplier' ? ($data['supplier_id'] ?: null) : null,
            'status' => $data['status'],
        ];

        $isNew = ! $this->editingId;
        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $user->fill($attrs);
            $user->save();
        } else {
            // ລະຫັດ ສຸ່ມ ທີ່ ໃຊ້ login ບໍ່ ໄດ້ — ຜູ້ໃຊ້ ຕັ້ງ ເອງ ຜ່ານ ລິ້ງ.
            $user = User::create($attrs + [
                'password' => bcrypt(Str::random(40)),
                'auth_provider' => 'password',
            ]);
        }

        $user->syncRoles([$this->role]);

        // ສິດ ເພີ່ມເຕີມ ລາຍ ບຸກຄົນ (ໂດຍກົງ): view+create+edit ຕໍ່ ເມນູ ທີ່ ໝາຍ.
        // intersect ກັບ grantable → ກັນ ການ ໃຫ້ ສິດ admin (escalation).
        $directPerms = [];
        foreach (array_keys(self::grantableMenus()) as $m) {
            $p = $this->extraPerms[$m] ?? [];
            $create = ! empty($p['create']);
            $edit = ! empty($p['edit']);
            $view = ! empty($p['view']) || $create || $edit;   // create/edit ⇒ view
            if ($view) {
                $directPerms[] = "{$m}.view";
            }
            if ($create) {
                $directPerms[] = "{$m}.create";
            }
            if ($edit) {
                $directPerms[] = "{$m}.edit";
            }
        }
        $user->syncPermissions($directPerms);

        $this->showModal = false;
        $this->dispatch('saved');

        // ບັນຊີ ໃໝ່ → ສ້າງ ລິ້ງ ຕັ້ງ ລະຫັດ + ສົ່ງ ອີເມລ (ຖ້າ SMTP ພ້ອມ).
        if ($isNew && config('features.local_auth')) {
            $this->issueSetPasswordLink($user);
        }
    }

    /** ສ້າງ ລິ້ງ ຕັ້ງ-ລະຫັດ (ໝົດ ອາຍຸ 60 ນາທີ) + ສົ່ງ ອີເມລ; ເກັບ ລິ້ງ ໄວ້ ໃຫ້ admin ກ໋ອບປີ້. */
    protected function issueSetPasswordLink(User $user): void
    {
        $token = Password::createToken($user);
        $this->setLinkEmail = $user->email;
        $this->setPasswordLink = route('password.reset', ['token' => $token]).'?email='.urlencode($user->email);
        // ສົ່ງ notification ດ້ວຍ token ດຽວ ກັນ (ສົ່ງ ຈິງ ຖ້າ MAIL ຕັ້ງ; ບໍ່ ດັ່ງນັ້ນ ລົງ log).
        $user->sendPasswordResetNotification($token);
    }

    /** ປຸ່ມ "ສົ່ງ/ກ໋ອບປີ້ ລິ້ງ ຕັ້ງ ລະຫັດ" ຕໍ່ ຜູ້ໃຊ້ (resend). */
    public function linkFor(int $id): void
    {
        abort_unless(auth()->user()->can('users.edit'), 403);
        abort_unless(config('features.local_auth'), 403);
        $this->issueSetPasswordLink(User::findOrFail($id));
    }

    public function closeLink(): void
    {
        $this->setPasswordLink = '';
        $this->setLinkEmail = '';
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
        $this->role = '';
        $this->unit_id = null;
        $this->department_id = null;
        $this->supplier_id = null;
        $this->status = 'active';
        $this->initExtraPerms();
        $this->setPasswordLink = '';
        $this->setLinkEmail = '';
        $this->resetValidation();
    }

    public function render(): View
    {
        $users = User::with(['roles', 'unit', 'department'])
            ->when($this->search, function ($q) {
                $q->where(fn ($w) => $w->where('display_name', 'like', "%{$this->search}%")
                    ->orWhere('username', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%"));
            })
            ->when($this->letter, fn ($q) => $q->where(fn ($w) => $w->where('display_name', 'like', "{$this->letter}%")
                ->orWhere('username', 'like', "{$this->letter}%")
                ->orWhere('email', 'like', "{$this->letter}%")))
            ->orderBy('display_name')
            ->paginate(9);

        return view('livewire.settings.users', [
            'users' => $users,
            'letters' => range('A', 'Z'),
            'roles' => Role::when(! auth()->user()->is_super_admin, fn ($q) => $q->where('name', '!=', 'super_admin'))
                ->orderBy('name')->pluck('name'),
            'grantableMenus' => self::grantableMenus(),
            'grantableActions' => self::grantableActions(),
            'units' => Unit::where('is_active', true)->orderBy('name')->get(),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'formDepartments' => $this->unit_id
                ? Department::where('unit_id', $this->unit_id)->where('is_active', true)->orderBy('name')->get()
                : collect(),
        ]);
    }
}
