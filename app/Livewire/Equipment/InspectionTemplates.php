<?php

namespace App\Livewire\Equipment;

use App\Models\InspectionTemplate;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class InspectionTemplates extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $tName = '';

    public string $tCategory = '';

    public string $tMethod = '';

    /** @var array<int,array{label:string,applies:string}> */
    public array $tItems = [];

    public bool $tActive = true;

    /** ຟິລເຕີ ສະແດງ ແຖວ ໃນ ຕົວ ແກ້: all | engine | ev. */
    public string $tFilter = 'all';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('equipment.view'), 403);
        // ແມ່ແບບ ກວດກາ ເປັນ ຂໍ້ມູນ ກາງ — ສະຫງວນ ໃຫ້ SA+ (department_admin ໃຊ້ ໄດ້ ແຕ່ ບໍ່ ແກ້).
        abort_if(auth()->user()->equipmentDepartmentScoped(), 403);
    }

    public function newTemplate(): void
    {
        abort_unless(auth()->user()->can('equipment.edit'), 403);
        $this->resetForm();
        $this->tItems = [['label' => '', 'applies' => 'both', 'freqs' => []]];
        $this->showModal = true;
    }

    public function editTemplate(int $id): void
    {
        $t = InspectionTemplate::findOrFail($id);
        $this->editingId = $t->id;
        $this->tName = $t->name;
        $this->tCategory = $t->category ?? '';
        $this->tMethod = $t->method ?? '';
        $this->tItems = $t->normalizedItems() ?: [['label' => '', 'applies' => 'both', 'freqs' => []]];
        $this->tActive = $t->is_active;
        $this->tFilter = 'all';
        $this->resetValidation();
        $this->showModal = true;
    }

    public function addChecklistItem(): void
    {
        $this->tItems[] = ['label' => '', 'applies' => 'both', 'freqs' => []];
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
            'tItems.*.applies' => ['nullable', 'in:both,ev,engine'],
            'tItems.*.freqs' => ['nullable', 'array'],
            'tItems.*.freqs.*' => ['in:'.implode(',', InspectionTemplate::FREQUENCIES)],
        ]);

        // ເກັບ ສະເພາະ ຂໍ້ ທີ່ ມີ ຊື່; ຮັກສາ ປ້າຍ applies + ຮອບ (freqs).
        $items = collect($this->tItems)
            ->map(function ($it) {
                if (is_string($it)) {
                    return ['label' => trim($it), 'applies' => 'both', 'freqs' => []];
                }
                $applies = $it['applies'] ?? 'both';
                $freqs = array_values(array_intersect(InspectionTemplate::FREQUENCIES, (array) ($it['freqs'] ?? [])));

                return [
                    'label' => trim((string) ($it['label'] ?? '')),
                    'applies' => in_array($applies, ['both', 'ev', 'engine'], true) ? $applies : 'both',
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
            InspectionTemplate::whereKey($this->editingId)->update($attrs);
        } else {
            InspectionTemplate::create($attrs + ['created_by' => auth()->id()]);
        }

        $this->showModal = false;
        $this->dispatch('saved');
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can('equipment.delete'), 403);
        InspectionTemplate::whereKey($id)->delete();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->tName = '';
        $this->tCategory = '';
        $this->tMethod = '';
        $this->tItems = [];
        $this->tActive = true;
        $this->tFilter = 'all';
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.equipment.inspection-templates', [
            'templates' => InspectionTemplate::orderBy('name')->get(),
        ]);
    }
}
