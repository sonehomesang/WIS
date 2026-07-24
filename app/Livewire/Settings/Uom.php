<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\SoftDeletesWithReason;
use App\Models\Uom as UomModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Uom extends Component
{
    use SoftDeletesWithReason;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $name_en = '';

    public bool $is_active = true;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('units.view'), 403);
    }

    public function newItem(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function editItem(int $id): void
    {
        $m = UomModel::findOrFail($id);
        $this->editingId = $m->id;
        $this->name = $m->name;
        $this->name_en = $m->name_en ?? '';
        $this->is_active = (bool) $m->is_active;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('units.'.($this->editingId ? 'edit' : 'create')), 403);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:64', Rule::unique('uoms', 'name')->whereNull('deleted_at')->ignore($this->editingId)],
            'name_en' => ['nullable', 'string', 'max:64'],
            'is_active' => ['boolean'],
        ], [], ['name' => 'ຊື່']);

        $uid = auth()->id();
        $payload = [
            'name' => $data['name'],
            'name_en' => $data['name_en'] ?: null,
            'is_active' => $this->is_active,
            'updated_by' => $uid,
        ];

        if ($this->editingId) {
            UomModel::findOrFail($this->editingId)->update($payload);
        } else {
            $payload['slug'] = $this->uniqueSlug($data['name']);
            $payload['created_by'] = $uid;
            UomModel::create($payload);
        }

        $this->showModal = false;
        $this->dispatch('saved');
    }

    public function toggle(int $id): void
    {
        $m = UomModel::findOrFail($id);
        abort_unless(auth()->user()->can('units.'.($m->is_active ? 'deactivate' : 'activate')), 403);
        $m->update(['is_active' => ! $m->is_active, 'updated_by' => auth()->id()]);
    }

    // ── ລຶບ-ດ້ວຍ-ເຫດຜົນ + Deleted Log (trait SoftDeletesWithReason) ──
    protected function deleteModelClass(): string
    {
        return UomModel::class;
    }

    protected function deletePermission(): string
    {
        return 'units.delete';
    }

    protected function deleteLabel(Model $record): string
    {
        return $record->name;
    }

    protected function deleteNoun(): string
    {
        return 'ໜ່ວຍວັດ';
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->name_en = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    protected function uniqueSlug(string $base): string
    {
        $slug = Str::slug($base) ?: 'uom';
        $original = $slug;
        $i = 2;
        while (DB::table('uoms')->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$i++;
        }

        return $slug;
    }

    public function render(): View
    {
        $items = UomModel::query()
            ->when($this->showDeleted && $this->canManageDeleted(), fn ($q) => $q->onlyTrashed()->with('deletedBy'))
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$this->search}%")->orWhere('name_en', 'like', "%{$this->search}%")))
            ->orderBy('name')->get();

        return view('livewire.settings.uom', [
            'items' => $items,
            'canManageDeleted' => $this->canManageDeleted(),
        ]);
    }
}
