<?php

namespace App\Livewire\Equipment;

use App\Livewire\Concerns\SoftDeletesWithReason;
use App\Models\Equipment;
use App\Models\EquipmentCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Categories extends Component
{
    use SoftDeletesWithReason;

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
            $cat = EquipmentCategory::findOrFail($this->editingId);
            $oldName = $cat->name;
            $cat->update($attrs);
            // rename cascade: ຮັກສາ equipment records ໃຫ້ຕົງກັບ master (ບໍ່ໃຫ້ orphan ຕອນປ່ຽນຊື່).
            if ($oldName !== $attrs['name']) {
                Equipment::where('category', $oldName)->update(['category' => $attrs['name']]);
            }
        } else {
            EquipmentCategory::create($attrs + ['created_by' => auth()->id()]);
        }

        $this->showModal = false;
        $this->dispatch('saved');
    }

    // ── ລຶບ-ດ້ວຍ-ເຫດຜົນ + Deleted Log (trait SoftDeletesWithReason) ──
    protected function deleteModelClass(): string
    {
        return EquipmentCategory::class;
    }

    /** role-based (ບໍ່ ໃຊ້ permission): ຈັດການ ການ ລຶບ = super_admin ຫຼື admin. */
    protected function canManageDeleted(): bool
    {
        return $this->canManage();
    }

    protected function deleteLabel(Model $record): string
    {
        return $record->name;
    }

    protected function deleteNoun(): string
    {
        return 'ປະເພດ';
    }

    public function render(): View
    {
        return view('livewire.equipment.categories', [
            'canManageDeleted' => $this->canManageDeleted(),
            'categories' => EquipmentCategory::query()
                ->when($this->showDeleted && $this->canManageDeleted(), fn ($q) => $q->onlyTrashed()->with('deletedBy'))
                ->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }
}
