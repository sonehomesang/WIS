<?php

namespace App\Livewire\Equipment;

use App\Models\EquipmentCategory;
use App\Models\MaintenanceTemplate;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** ຈັດການ ແມ່ແບບ ເຊັກລິສ ບຳລຸງຮັກສາ (Equipment › ແມ່ແບບ ບຳລຸງ). */
#[Layout('layouts.app')]
class MaintenanceTemplates extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $tName = '';

    public string $tCategory = '';

    public string $tMethod = '';

    /** @var array<int,array{label:string,freqs:array<int,string>}> */
    public array $tItems = [];

    public bool $tActive = true;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('equipment.view'), 403);
        // ແມ່ແບບ ບຳລຸງ ເປັນ ຂໍ້ມູນ ກາງ — ສະຫງວນ ໃຫ້ SA+ (department_admin ບໍ່ ແກ້).
        abort_if(auth()->user()->equipmentDepartmentScoped(), 403);
    }

    public function newTemplate(): void
    {
        abort_unless(auth()->user()->can('equipment.edit'), 403);
        $this->resetForm();
        $this->tItems = [['label' => '', 'freqs' => []]];
        $this->showModal = true;
    }

    public function editTemplate(int $id): void
    {
        $t = MaintenanceTemplate::findOrFail($id);
        $this->editingId = $t->id;
        $this->tName = $t->name;
        $this->tCategory = $t->category ?? '';
        $this->tMethod = $t->method ?? '';
        $this->tItems = $t->normalizedItems() ?: [['label' => '', 'freqs' => []]];
        $this->tActive = $t->is_active;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function addChecklistItem(): void
    {
        $this->tItems[] = ['label' => '', 'freqs' => []];
    }

    public function removeChecklistItem(int $i): void
    {
        unset($this->tItems[$i]);
        $this->tItems = array_values($this->tItems);
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('equipment.'.($this->editingId ? 'edit' : 'create')), 403);

        $data = $this->validate([
            'tName' => ['required', 'string', 'max:256'],
            'tCategory' => ['nullable', 'string', 'max:128'],
            'tMethod' => ['nullable', 'string', 'max:2000'],
            'tItems.*.label' => ['nullable', 'string', 'max:256'],
            'tItems.*.freqs' => ['nullable', 'array'],
            'tItems.*.freqs.*' => ['in:'.implode(',', MaintenanceTemplate::FREQUENCIES)],
        ]);

        // ເກັບ ສະເພາະ ຂໍ້ ທີ່ ມີ ຊື່; ຮັກສາ ຮອບ (freqs).
        $items = collect($this->tItems)
            ->map(function ($it) {
                if (is_string($it)) {
                    return ['label' => trim($it), 'freqs' => []];
                }
                $freqs = array_values(array_intersect(MaintenanceTemplate::FREQUENCIES, (array) ($it['freqs'] ?? [])));

                return [
                    'label' => trim((string) ($it['label'] ?? '')),
                    'freqs' => $freqs,
                ];
            })
            ->filter(fn ($x) => $x['label'] !== '')
            ->values()
            ->all();

        $attrs = [
            'name' => $data['tName'],
            'category' => $data['tCategory'] ?: null,
            'method' => $data['tMethod'] ?: null,
            'items' => $items,
            'is_active' => $this->tActive,
            'updated_by' => auth()->id(),
        ];

        if ($this->editingId) {
            MaintenanceTemplate::whereKey($this->editingId)->update($attrs);
        } else {
            MaintenanceTemplate::create($attrs + ['created_by' => auth()->id()]);
        }

        $this->showModal = false;
        $this->dispatch('saved');
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can('equipment.delete'), 403);
        MaintenanceTemplate::whereKey($id)->delete();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->tName = '';
        $this->tCategory = '';
        $this->tMethod = '';
        $this->tItems = [];
        $this->tActive = true;
        $this->resetValidation();
    }

    public function render(): View
    {
        // ປະເພດ ເຄື່ອງ ດຶງ ຈາກ master ດຽວ ກັບ ຟອມ ສ້າງ ເຄື່ອງ — ຮວມ ຄ່າ ປັດຈຸບັນ ຕອນ ແກ້ (ກັນ ຫາຍ ຖ້າ ຖືກ ປິດ).
        $categoryOptions = EquipmentCategory::where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')->pluck('name');
        if ($this->tCategory !== '' && ! $categoryOptions->contains($this->tCategory)) {
            $categoryOptions = $categoryOptions->prepend($this->tCategory);
        }

        return view('livewire.equipment.maintenance-templates', [
            'templates' => MaintenanceTemplate::orderBy('name')->get(),
            'categoryOptions' => $categoryOptions,
        ]);
    }
}
