<?php

namespace App\Livewire\Equipment;

use App\Models\EquipmentCategory;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Categories extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $cName = '';

    public bool $cActive = true;

    public int $cSort = 0;

    /** ຈັດການ ປະເພດ = super_admin ຫຼື admin (SA) ເທົ່ານັ້ນ. */
    protected function canManage(): bool
    {
        $u = auth()->user();

        return $u->is_super_admin || $u->hasRole('admin');
    }

    public function mount(): void
    {
        abort_unless($this->canManage(), 403);
    }

    public function newCategory(): void
    {
        abort_unless($this->canManage(), 403);
        $this->reset(['editingId', 'cName', 'cActive', 'cSort']);
        $this->cActive = true;
        $this->cSort = (int) EquipmentCategory::max('sort_order') + 1;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function editCategory(int $id): void
    {
        abort_unless($this->canManage(), 403);
        $c = EquipmentCategory::findOrFail($id);
        $this->editingId = $c->id;
        $this->cName = $c->name;
        $this->cActive = $c->is_active;
        $this->cSort = $c->sort_order;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        abort_unless($this->canManage(), 403);
        $data = $this->validate([
            'cName' => ['required', 'string', 'max:128', Rule::unique('equipment_categories', 'name')->ignore($this->editingId)->whereNull('deleted_at')],
            'cActive' => ['boolean'],
            'cSort' => ['integer', 'min:0'],
        ]);

        $attrs = [
            'name' => trim($data['cName']),
            'is_active' => $data['cActive'],
            'sort_order' => $data['cSort'],
            'updated_by' => auth()->id(),
        ];

        if ($this->editingId) {
            EquipmentCategory::findOrFail($this->editingId)->update($attrs);
        } else {
            EquipmentCategory::create($attrs + ['created_by' => auth()->id()]);
        }

        $this->showModal = false;
        $this->dispatch('saved');
    }

    public function delete(int $id): void
    {
        abort_unless($this->canManage(), 403);
        EquipmentCategory::whereKey($id)->delete();
    }

    public function render(): View
    {
        return view('livewire.equipment.categories', [
            'categories' => EquipmentCategory::orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }
}
