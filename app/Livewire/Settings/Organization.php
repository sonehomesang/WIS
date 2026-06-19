<?php

namespace App\Livewire\Settings;

use App\Models\Department;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Organization extends Component
{
    public ?int $selectedUnitId = null;

    // Modal state
    public bool $showModal = false;

    public string $type = 'unit';          // 'unit' | 'department'

    public ?int $editingId = null;

    // Form fields
    public string $name = '';

    public string $name_en = '';

    public string $description = '';

    public bool $is_active = true;

    public ?int $unitId = null;            // parent Org Unit (department form)

    public function mount(): void
    {
        abort_unless(auth()->user()->can('units.view'), 403);
        $this->selectedUnitId = Unit::orderBy('name')->value('id');
    }

    public function selectUnit(int $id): void
    {
        $this->selectedUnitId = $id;
    }

    public function newUnit(): void
    {
        $this->resetForm('unit');
        $this->showModal = true;
    }

    public function editUnit(int $id): void
    {
        $this->fillForm('unit', Unit::findOrFail($id));
    }

    public function newDepartment(): void
    {
        if (! $this->selectedUnitId) {
            return;
        }
        $this->resetForm('department');
        $this->unitId = $this->selectedUnitId;
        $this->showModal = true;
    }

    public function editDepartment(int $id): void
    {
        $this->fillForm('department', Department::findOrFail($id));
    }

    public function save(): void
    {
        $menu = $this->type === 'unit' ? 'units' : 'departments';
        abort_unless(auth()->user()->can("{$menu}.".($this->editingId ? 'edit' : 'create')), 403);

        $nameRule = $this->type === 'unit'
            ? Rule::unique('units', 'name')->whereNull('deleted_at')->ignore($this->editingId)
            : Rule::unique('departments', 'name')->where('unit_id', $this->unitId)->whereNull('deleted_at')->ignore($this->editingId);

        $rules = [
            'name' => ['required', 'string', 'max:256', $nameRule],
            'name_en' => ['nullable', 'string', 'max:256'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ];
        if ($this->type === 'department') {
            $rules['unitId'] = ['required', 'integer', 'exists:units,id'];
        }
        $data = $this->validate($rules, [], ['name' => 'ຊື່', 'unitId' => 'ໜ່ວຍງານ']);

        $uid = auth()->id();
        $payload = [
            'name' => $data['name'],
            'name_en' => $data['name_en'] ?: null,
            'description' => $data['description'] ?: null,
            'is_active' => $this->is_active,
            'updated_by' => $uid,
        ];

        if ($this->type === 'unit') {
            if ($this->editingId) {
                Unit::findOrFail($this->editingId)->update($payload);
            } else {
                $payload['slug'] = $this->uniqueSlug($data['name'], 'units');
                $payload['created_by'] = $uid;
                $this->selectedUnitId = Unit::create($payload)->id;
            }
        } else {
            $payload['unit_id'] = $this->unitId;
            if ($this->editingId) {
                Department::findOrFail($this->editingId)->update($payload);
            } else {
                $payload['slug'] = $this->uniqueSlug($data['name'], 'departments');
                $payload['created_by'] = $uid;
                Department::create($payload);
            }
            $this->selectedUnitId = $this->unitId;
        }

        $this->showModal = false;
        $this->dispatch('saved');
    }

    public function toggleUnit(int $id): void
    {
        $unit = Unit::findOrFail($id);
        abort_unless(auth()->user()->can('units.'.($unit->is_active ? 'deactivate' : 'activate')), 403);
        $unit->update(['is_active' => ! $unit->is_active, 'updated_by' => auth()->id()]);
    }

    public function deleteUnit(int $id): void
    {
        abort_unless(auth()->user()->can('units.delete'), 403);
        $unit = Unit::findOrFail($id);
        if ($unit->departments()->exists()) {
            $this->addError('row', 'ລຶບ Unit ບໍ່ໄດ້ — ຍັງມີ Department ຢູ່ພາຍໃນ.');

            return;
        }
        $unit->delete();
        if ($this->selectedUnitId === $id) {
            $this->selectedUnitId = Unit::orderBy('name')->value('id');
        }
    }

    public function toggleDepartment(int $id): void
    {
        $dept = Department::findOrFail($id);
        abort_unless(auth()->user()->can('departments.'.($dept->is_active ? 'deactivate' : 'activate')), 403);
        $dept->update(['is_active' => ! $dept->is_active, 'updated_by' => auth()->id()]);
    }

    public function deleteDepartment(int $id): void
    {
        abort_unless(auth()->user()->can('departments.delete'), 403);
        Department::findOrFail($id)->delete();
    }

    protected function resetForm(string $type): void
    {
        $this->type = $type;
        $this->editingId = null;
        $this->name = '';
        $this->name_en = '';
        $this->description = '';
        $this->is_active = true;
        $this->unitId = null;
        $this->resetValidation();
    }

    protected function fillForm(string $type, Unit|Department $model): void
    {
        $this->type = $type;
        $this->editingId = $model->id;
        $this->name = $model->name;
        $this->name_en = $model->name_en ?? '';
        $this->description = $model->description ?? '';
        $this->is_active = (bool) $model->is_active;
        $this->unitId = $type === 'department' ? $model->unit_id : null;
        $this->resetValidation();
        $this->showModal = true;
    }

    protected function uniqueSlug(string $base, string $table): string
    {
        $slug = Str::slug($base) ?: 'item';
        $original = $slug;
        $i = 2;
        while (DB::table($table)->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$i++;
        }

        return $slug;
    }

    public function render(): View
    {
        $units = Unit::withCount('departments')->orderBy('name')->get();
        $departments = $this->selectedUnitId
            ? Department::where('unit_id', $this->selectedUnitId)->orderBy('name')->get()
            : collect();
        $selectedUnit = $this->selectedUnitId ? $units->firstWhere('id', $this->selectedUnitId) : null;

        return view('livewire.settings.organization', compact('units', 'departments', 'selectedUnit'));
    }
}
