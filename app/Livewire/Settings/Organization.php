<?php

namespace App\Livewire\Settings;

use App\Models\Department;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
        $action = $this->editingId ? 'edit' : 'create';
        abort_unless(auth()->user()->can("{$menu}.{$action}"), 403);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:256'],
            'name_en' => ['nullable', 'string', 'max:256'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ]);

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
                $payload['slug'] = $this->uniqueSlug($data['name'], 'units', null);
                $payload['created_by'] = $uid;
                $this->selectedUnitId = Unit::create($payload)->id;
            }
        } else {
            $this->validate(['unitId' => ['required', 'integer', 'exists:units,id']]);
            $payload['unit_id'] = $this->unitId;
            if ($this->editingId) {
                Department::findOrFail($this->editingId)->update($payload);
            } else {
                $payload['slug'] = $this->uniqueSlug($data['name'], 'departments', null);
                $payload['created_by'] = $uid;
                Department::create($payload);
            }
            $this->selectedUnitId = $this->unitId;   // jump to the (possibly new) parent unit
        }

        $this->showModal = false;
        $this->dispatch('saved');
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

    protected function uniqueSlug(string $base, string $table, ?int $ignoreId): string
    {
        $slug = Str::slug($base) ?: 'item';
        $original = $slug;
        $i = 2;
        while (
            DB::table($table)->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
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
