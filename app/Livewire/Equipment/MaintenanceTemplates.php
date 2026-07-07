<?php

namespace App\Livewire\Equipment;

use App\Models\Equipment;
use App\Models\MaintenanceTemplate;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** ຈັດການ ແມ່ແບບ ເຊັກລິສ ບຳລຸງຮັກສາ ຕໍ່ ເຄື່ອງ (Equipment › ແມ່ແບບ ບຳລຸງ). */
#[Layout('layouts.app')]
class MaintenanceTemplates extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $tName = '';

    // ເຄື່ອງ ທີ່ ແມ່ແບບ ນີ້ ໃຊ້ ນຳ — ປະເພດ ດຶງ ຈາກ ເຄື່ອງ ນີ້.
    public ?int $tEquipmentId = null;

    public string $tMethod = '';

    /** @var array<int,array{label:string,remark:string,cycles:array<string,string>}> */
    public array $tItems = [];

    public bool $tActive = true;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('equipment.view'), 403);
        // ແມ່ແບບ ບຳລຸງ ເປັນ ຂໍ້ມູນ ກາງ — ສະຫງວນ ໃຫ້ SA+ (department_admin ບໍ່ ແກ້).
        abort_if(auth()->user()->equipmentDepartmentScoped(), 403);
    }

    /** ຂໍ້ ເປົ່າ ໃໝ່. */
    protected function blankItem(): array
    {
        return ['label' => '', 'remark' => '', 'cycles' => []];
    }

    public function newTemplate(): void
    {
        abort_unless(auth()->user()->can('equipment.edit'), 403);
        $this->resetForm();
        $this->tItems = [$this->blankItem()];
        $this->showModal = true;
    }

    public function editTemplate(int $id): void
    {
        $t = MaintenanceTemplate::findOrFail($id);
        $this->editingId = $t->id;
        $this->tName = $t->name;
        $this->tEquipmentId = $t->equipment_id;
        $this->tMethod = $t->method ?? '';
        $this->tItems = $t->normalizedItems() ?: [$this->blankItem()];
        $this->tActive = $t->is_active;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function addChecklistItem(): void
    {
        $this->tItems[] = $this->blankItem();
    }

    public function removeChecklistItem(int $i): void
    {
        unset($this->tItems[$i]);
        $this->tItems = array_values($this->tItems);
    }

    /** ກົດ ຊ່ອງ ຮອບ → ສະຫຼັບ: ວ່າງ → ກວດ(C) → ປ່ຽນ(X) → ວ່າງ. */
    public function bumpCycle(int $i, string $cycle): void
    {
        if (! isset($this->tItems[$i]) || ! in_array($cycle, MaintenanceTemplate::FREQUENCIES, true)) {
            return;
        }
        $current = $this->tItems[$i]['cycles'][$cycle] ?? '';
        $next = match ($current) {
            '' => 'C',
            'C' => 'X',
            default => '',
        };
        if ($next === '') {
            unset($this->tItems[$i]['cycles'][$cycle]);
        } else {
            $this->tItems[$i]['cycles'][$cycle] = $next;
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('equipment.'.($this->editingId ? 'edit' : 'create')), 403);

        $data = $this->validate([
            'tName' => ['required', 'string', 'max:256'],
            'tEquipmentId' => ['required', 'exists:equipment,id'],
            'tMethod' => ['nullable', 'string', 'max:2000'],
            'tItems.*.label' => ['nullable', 'string', 'max:256'],
            'tItems.*.remark' => ['nullable', 'string', 'max:256'],
        ]);

        // ເກັບ ສະເພາະ ຂໍ້ ທີ່ ມີ ຊື່; ຄັດ cycles ໃຫ້ ຢູ່ ໃນ ຮອບ + action ທີ່ ຮັບຮອງ.
        $items = collect($this->tItems)
            ->map(function ($it) {
                if (is_string($it)) {
                    return ['label' => trim($it), 'remark' => '', 'cycles' => []];
                }
                $cycles = [];
                foreach (MaintenanceTemplate::FREQUENCIES as $f) {
                    $v = $it['cycles'][$f] ?? null;
                    if (in_array($v, ['C', 'X'], true)) {
                        $cycles[$f] = $v;
                    }
                }

                return [
                    'label' => trim((string) ($it['label'] ?? '')),
                    'remark' => trim((string) ($it['remark'] ?? '')),
                    'cycles' => $cycles,
                ];
            })
            ->filter(fn ($x) => $x['label'] !== '')
            ->values()
            ->all();

        // ປະເພດ ດຶງ ຈາກ ເຄື່ອງ ທີ່ ເລືອກ (ຜູກ ໄວ້ ຕັ້ງແຕ່ ຕອນ ສ້າງ ທະບຽນ ເຄື່ອງ).
        $e = Equipment::findOrFail($data['tEquipmentId']);

        $attrs = [
            'name' => $data['tName'],
            'equipment_id' => $e->id,
            'category' => $e->category,
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
        $this->tEquipmentId = null;
        $this->tMethod = '';
        $this->tItems = [];
        $this->tActive = true;
        $this->resetValidation();
    }

    public function render(): View
    {
        $equipmentOptions = Equipment::orderBy('asset_code')->orderBy('name')
            ->get(['id', 'asset_code', 'name', 'category']);

        // ປະເພດ ຂອງ ເຄື່ອງ ທີ່ ເລືອກ (ສະແດງ ແບບ read-only ໃນ ຟອມ).
        $selectedCategory = $this->tEquipmentId
            ? $equipmentOptions->firstWhere('id', $this->tEquipmentId)?->category
            : null;

        return view('livewire.equipment.maintenance-templates', [
            'templates' => MaintenanceTemplate::with('equipment')->orderBy('name')->get(),
            'equipmentOptions' => $equipmentOptions,
            'selectedCategory' => $selectedCategory,
        ]);
    }
}
