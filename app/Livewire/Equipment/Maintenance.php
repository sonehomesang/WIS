<?php

namespace App\Livewire\Equipment;

use App\Livewire\Concerns\SoftDeletesWithReason;
use App\Models\Equipment;
use App\Models\EquipmentMaintenance;
use App\Models\MaintenanceTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/** ແທັບ ບຳລຸງຮັກສາ — ຝັງ ໃນ Equipment › ບຳລຸງຮັກສາ. */
class Maintenance extends Component
{
    use SoftDeletesWithReason, WithFileUploads, WithPagination;

    public const MAX_PHOTOS = 4;

    public bool $showModal = false;

    public ?int $editingId = null;

    /** ເປີດ ຟອມ ໃນ ໂໝດ ວາງແຜນ ລ່ວງໜ້າ (ບໍ່ ແມ່ນ ລົງມື ແລ້ວ). */
    public bool $planning = false;

    // ── ຄົ້ນຫາ + filter ລາຍການ (ຄື ແທັບ ທະບຽນເຄື່ອງ) ──
    public string $search = '';

    public string $typeFilter = '';

    public string $statusFilter = '';

    // ເລືອກ ເຄື່ອງ
    public string $mSearch = '';

    /** ເບິ່ງ ບັນທຶກ ບຳລຸງ ແບບ read-only (ບໍ່ ຕ້ອງ ສິດ ແກ້). */
    public ?int $viewingId = null;

    /** ໂຊ ແມ່ແບບ ທັງໝົດ (ບໍ່ ຄັດ ຕາມ ເຄື່ອງ/ປະເພດ) — ທາງເລືອກ ຢືດຢຸ່ນ. */
    public bool $mShowAllTemplates = false;

    public ?int $mEquipmentId = null;

    public string $mEquipmentLabel = '';

    // ຟອມ
    public ?string $mDate = null;

    public string $mType = 'preventive';

    public string $mTitle = '';

    public string $mDescription = '';

    public string $mPerformedBy = '';

    public ?string $mCost = null;

    public string $mFrequency = '';

    /** ຊັ້ນ 1: ປະເພດ ລົດ — '' | 'ev' | 'engine' (ຄັດ ເຊັກລິສ ໃຫ້ ຖືກ ກັບ ລົດ). */
    public string $mFuelType = '';

    public ?string $mNextService = null;

    public string $mStatus = 'done';

    public string $mNotes = '';

    public array $mPhotos = [];

    /** @var array<int,string> */
    public array $mExistingPhotos = [];

    // ── ເຊັກລິສ ຈາກ ແມ່ແບບ (ຄັດ ຕາມ ຮອບ = mFrequency) ──
    public ?int $mTemplateId = null;

    /** @var array<int,array{label:string,remark:string,action:string,group:string,status:string,photo_before?:string,photo_after?:string}> */
    public array $mChecklist = [];

    /** ຮູບ ຫຼັກຖານ ກ່ອນ/ຫຼັງ ຕໍ່ ຂໍ້ ປ່ຽນ (X) — key = index ຂໍ້ ໃນ mChecklist → TemporaryUploadedFile. */
    public array $itemPhotoBefore = [];

    public array $itemPhotoAfter = [];

    // ── ຄັດ ເຊັກລິສ ໃນ ຟອມ (ໃຫ້ 38+ ຂໍ້ ສັ້ນ ລົງ) ──
    public string $ckAction = '';   // '' | 'C' | 'X'

    public bool $ckNg = false;       // ໂຊ ສະເພາະ ຂໍ້ ບັນຫາ (NG)

    // ── ໜ້າຕ່າງ ປະຫວັດ ຕໍ່ ເຄື່ອງ ──
    public ?int $historyEquipmentId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('equipment.view'), 403);
    }

    public function updatingMSearch(): void
    {
        $this->resetPage();
    }

    /** ຖືກ ຈຳກັດ ໃຫ້ ເບິ່ງ/ຈັດການ ສະເພາະ ພະແນກ ຕົນ ບໍ. */
    protected function deptScoped(): bool
    {
        return auth()->user()->equipmentDepartmentScoped();
    }

    protected function userDeptId(): ?int
    {
        return auth()->user()->department_id;
    }

    protected function guardDept(Equipment $e): void
    {
        if ($this->deptScoped() && $e->department_id !== $this->userDeptId()) {
            abort(403);
        }
    }

    /** ຄ່າ ຕັ້ງຕົ້ນ ຮ່ວມ ຂອງ ຟອມ ໃໝ່ (ໃຊ້ ໂດຍ ລົງມື + ວາງແຜນ). */
    protected function resetForm(): void
    {
        $this->reset([
            'editingId', 'mSearch', 'mEquipmentId', 'mEquipmentLabel', 'mDescription',
            'mPerformedBy', 'mCost', 'mFrequency', 'mNextService', 'mNotes', 'mPhotos', 'mExistingPhotos', 'mTitle',
            'mTemplateId', 'mChecklist', 'ckAction', 'ckNg', 'mFuelType', 'itemPhotoBefore', 'itemPhotoAfter',
        ]);
        $this->mDate = now()->toDateString();
        $this->mType = 'preventive';
        $this->resetValidation();
        $this->showModal = true;
    }

    /** ລົງມື ບຳລຸງ — ບັນທຶກ ວຽກ ທີ່ ເຮັດ ແລ້ວ (ສະຖານະ = ແລ້ວ). */
    public function newMaintenance(): void
    {
        abort_unless(auth()->user()->can('equipment.edit'), 403);
        $this->resetForm();
        $this->mStatus = 'done';
        $this->planning = false;
    }

    /** ວາງແຜນ ບຳລຸງ ລ່ວງໜ້າ — ຕັ້ງ ນັດ ໄວ້ ກ່ອນ (ສະຖານະ = ວາງແຜນ). */
    public function newPlan(): void
    {
        abort_unless(auth()->user()->can('equipment.edit'), 403);
        $this->resetForm();
        $this->mStatus = 'planned';
        $this->planning = true;
    }

    public function editMaintenance(int $id): void
    {
        abort_unless(auth()->user()->can('equipment.edit'), 403);
        $m = EquipmentMaintenance::with('equipment')->findOrFail($id);
        if ($m->equipment) {
            $this->guardDept($m->equipment);
        }
        $this->reset(['mSearch', 'mPhotos', 'itemPhotoBefore', 'itemPhotoAfter']);
        $this->planning = false;
        $this->editingId = $m->id;
        $this->mEquipmentId = $m->equipment_id;
        $this->mEquipmentLabel = $m->equipment ? $m->equipment->asset_code.' · '.$m->equipment->name : '';
        $this->mDate = $m->maintenance_date?->toDateString();
        $this->mType = $m->type;
        $this->mTitle = $m->title;
        $this->mDescription = $m->description ?? '';
        $this->mPerformedBy = $m->performed_by ?? '';
        $this->mCost = $m->cost !== null ? (string) $m->cost : null;
        $this->mFrequency = $m->frequency ?? '';
        $this->mNextService = $m->next_service_date?->toDateString();
        $this->mStatus = $m->status;
        $this->mNotes = $m->notes ?? '';
        $this->mExistingPhotos = $m->photos ?? [];
        $this->mTemplateId = $m->template_id;
        $this->mChecklist = $m->checklist ?? [];
        $this->resetValidation();
        $this->showModal = true;
    }

    public function pickEquipment(int $id): void
    {
        $e = Equipment::find($id);
        if (! $e) {
            return;
        }
        $this->guardDept($e);
        $this->mEquipmentId = $e->id;
        $this->mEquipmentLabel = $e->asset_code.' · '.$e->name;
        $this->mSearch = '';
        // ແມ່ແບບ ຜູກ ຕໍ່ ເຄື່ອງ → ປ່ຽນ ເຄື່ອງ ຕ້ອງ ລ້າງ ແມ່ແບບ/ເຊັກລິສ.
        $this->reset(['mTemplateId', 'mChecklist']);
    }

    /** ເລືອກ ຮອບ → ຄິດ ວັນ service ຄັ້ງ ໜ້າ + ຄັດ ເຊັກລິສ ຕາມ ຮອບ. */
    public function updatedMFrequency($value): void
    {
        $due = $this->nextServiceFor((string) $value);
        if ($due) {
            $this->mNextService = $due;
        }
        $this->rebuildChecklist();
    }

    /** ເລືອກ ແມ່ແບບ → ຄັດ ເຊັກລິສ ໃໝ່ (ຕາມ ຮອບ ທີ່ ເລືອກ ຢູ່). */
    public function updatedMTemplateId($value): void
    {
        $this->mFuelType = '';   // ແມ່ແບບ ໃໝ່ → ເລືອກ ປະເພດ ລົດ ຄືນ
        $this->rebuildChecklist();
    }

    /** ເລືອກ ປະເພດ ລົດ (EV/Engine) → ຄັດ ເຊັກລິສ ໃໝ່. */
    public function updatedMFuelType($value): void
    {
        $this->rebuildChecklist();
    }

    /** ຄັດ ລາຍການ ຈາກ ແມ່ແບບ ຕາມ ຮອບ (mFrequency) + ປະເພດ ລົດ (mFuelType) — ວ່າງ ຖ້າ ຍັງ ບໍ່ ເລືອກ ຄົບ. */
    protected function rebuildChecklist(): void
    {
        $t = $this->mTemplateId ? MaintenanceTemplate::find($this->mTemplateId) : null;
        if (! $t || $this->mFrequency === '') {
            $this->mChecklist = [];

            return;
        }
        // ຖ້າ ແມ່ແບບ ມີ ຂໍ້ ຕິດ ປະເພດ ລົດ → ຕ້ອງ ເລືອກ EV/Engine ກ່ອນ.
        if ($t->hasFuelTypes() && $this->mFuelType === '') {
            $this->mChecklist = [];

            return;
        }
        $this->mChecklist = collect($t->itemsForCycle($this->mFrequency, $this->mFuelType))
            ->map(fn ($x) => ['label' => $x['label'], 'remark' => $x['remark'], 'action' => $x['action'], 'group' => $x['group'] ?? 'other', 'status' => 'ok'])
            ->values()
            ->all();
    }

    /** ລ້າງ ຕົວ ຄັດ ເຊັກລິສ (ໝວດ/C/X/NG). */
    public function resetChecklistFilter(): void
    {
        $this->ckAction = '';
        $this->ckNg = false;
    }

    /** ຕິກ ສະຖານະ ຂໍ້: ✓ ok / ✗ ng (ກົດ ຊ້ຳ = ກັບ ໄປ N/A). */
    public function toggleMaintChecklist(int $i, string $status): void
    {
        if (! isset($this->mChecklist[$i])) {
            return;
        }
        $this->mChecklist[$i]['status'] = ($this->mChecklist[$i]['status'] ?? '') === $status ? 'na' : $status;
    }

    protected function nextServiceFor(string $frequency): ?string
    {
        $from = $this->mDate ? Carbon::parse($this->mDate) : now();

        return match ($frequency) {
            'monthly' => $from->copy()->addMonth()->toDateString(),
            'quarterly' => $from->copy()->addMonths(3)->toDateString(),
            'semi_annual' => $from->copy()->addMonths(6)->toDateString(),
            'annual' => $from->copy()->addYear()->toDateString(),
            default => null,
        };
    }

    public function removePhoto(int $i): void
    {
        unset($this->mPhotos[$i]);
        $this->mPhotos = array_values($this->mPhotos);
    }

    public function removeExistingPhoto(int $i): void
    {
        abort_unless(auth()->user()->can('equipment.edit'), 403);
        if (isset($this->mExistingPhotos[$i])) {
            Storage::disk('public')->delete($this->mExistingPhotos[$i]);
            unset($this->mExistingPhotos[$i]);
            $this->mExistingPhotos = array_values($this->mExistingPhotos);
        }
    }

    /** ລຶບ ຮູບ ກ່ອນ/ຫຼັງ ຂອງ ຂໍ້ ໜຶ່ງ ($slot = 'before'|'after'). ລຶບ ທັງ temp ອັບ + ຮູບ ທີ່ ບັນທຶກ ໄວ້. */
    public function clearItemPhoto(int $i, string $slot): void
    {
        $store = $slot === 'after' ? 'itemPhotoAfter' : 'itemPhotoBefore';
        unset($this->{$store}[$i]);

        $key = 'photo_'.($slot === 'after' ? 'after' : 'before');
        if (isset($this->mChecklist[$i][$key])) {
            if ($this->editingId) {
                Storage::disk('public')->delete($this->mChecklist[$i][$key]);
            }
            unset($this->mChecklist[$i][$key]);
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('equipment.'.($this->editingId ? 'edit' : 'create')), 403);

        $data = $this->validate([
            'mEquipmentId' => ['required', 'exists:equipment,id'],
            'mDate' => ['required', 'date'],
            'mType' => ['required', 'in:'.implode(',', array_keys(EquipmentMaintenance::TYPES))],
            'mTitle' => ['required', 'string', 'max:256'],
            'mDescription' => ['nullable', 'string', 'max:2000'],
            'mPerformedBy' => ['nullable', 'string', 'max:256'],
            'mCost' => ['nullable', 'numeric', 'min:0'],
            'mFrequency' => ['nullable', 'in:'.implode(',', EquipmentMaintenance::FREQUENCIES)],
            'mFuelType' => ['nullable', 'in:ev,engine'],
            'mNextService' => ['nullable', 'date'],
            'mStatus' => ['required', 'in:'.implode(',', array_keys(EquipmentMaintenance::STATUSES))],
            'mNotes' => ['nullable', 'string', 'max:2000'],
            'mTemplateId' => ['nullable', 'exists:maintenance_templates,id'],
            'mPhotos' => ['array', 'max:'.self::MAX_PHOTOS],
            'mPhotos.*' => ['image', 'max:8192'],
            'itemPhotoBefore.*' => ['nullable', 'image', 'max:8192'],
            'itemPhotoAfter.*' => ['nullable', 'image', 'max:8192'],
        ]);

        $e = Equipment::findOrFail($this->mEquipmentId);
        $this->guardDept($e);

        // ຖ້າ ໃຊ້ ແມ່ແບບ ທີ່ ຂໍ້ ຕິດ ປະເພດ ລົດ → ຕ້ອງ ເລືອກ EV/Engine ກ່ອນ ບັນທຶກ.
        $tpl = $this->mTemplateId ? MaintenanceTemplate::find($this->mTemplateId) : null;
        if ($tpl && $tpl->hasFuelTypes() && $this->mFuelType === '') {
            $this->addError('mFuelType', 'ກະລຸນາ ເລືອກ ປະເພດ ລົດ (ໄຟຟ້າ/ນ້ຳມັນ) ກ່ອນ.');

            return;
        }

        if (count($this->mExistingPhotos) + count($this->mPhotos) > self::MAX_PHOTOS) {
            $this->addError('mPhotos', 'ຮູບ ໄດ້ ສູງສຸດ '.self::MAX_PHOTOS.' ໃບ.');

            return;
        }
        $photoPaths = $this->mExistingPhotos;
        foreach ($this->mPhotos as $photo) {
            $photoPaths[] = $photo->store('equipment/maintenance/'.$e->id, 'public');
        }

        $nextService = $this->mNextService ?: $this->nextServiceFor($this->mFrequency);

        // ຮູບ ຫຼັກຖານ ກ່ອນ/ຫຼັງ ຕໍ່ ຂໍ້ ປ່ຽນ (X) → ເກັບ ໄຟລ໌ + ຝັງ path ໃສ່ ຂໍ້.
        foreach ($this->mChecklist as $i => $c) {
            if (($c['action'] ?? '') !== 'X') {
                continue;
            }
            if (isset($this->itemPhotoBefore[$i]) && $this->itemPhotoBefore[$i]) {
                $this->mChecklist[$i]['photo_before'] = $this->itemPhotoBefore[$i]->store('equipment/maintenance/'.$e->id.'/items', 'public');
            }
            if (isset($this->itemPhotoAfter[$i]) && $this->itemPhotoAfter[$i]) {
                $this->mChecklist[$i]['photo_after'] = $this->itemPhotoAfter[$i]->store('equipment/maintenance/'.$e->id.'/items', 'public');
            }
        }
        $this->reset(['itemPhotoBefore', 'itemPhotoAfter']);

        // ເກັບ ເຊັກລິສ ສະເພາະ ເມື່ອ ຜູກ ແມ່ແບບ + ມີ ຮອບ (ຄັດ ໄດ້).
        $checklist = ($this->mTemplateId && count($this->mChecklist)) ? $this->mChecklist : null;

        $attrs = [
            'equipment_id' => $e->id,
            'template_id' => $checklist ? $this->mTemplateId : null,
            'maintenance_date' => $data['mDate'],
            'type' => $data['mType'],
            'title' => $data['mTitle'],
            'description' => $data['mDescription'] ?: null,
            'performed_by' => $data['mPerformedBy'] ?: null,
            'cost' => $data['mCost'] !== null && $data['mCost'] !== '' ? $data['mCost'] : null,
            'frequency' => $this->mFrequency ?: null,
            'next_service_date' => $nextService ?: null,
            'status' => $data['mStatus'],
            'checklist' => $checklist,
            'notes' => $data['mNotes'] ?: null,
            'photos' => $photoPaths ?: null,
        ];

        if ($this->editingId) {
            EquipmentMaintenance::findOrFail($this->editingId)->update($attrs);
        } else {
            $e->maintenances()->create($attrs + ['created_by' => auth()->id()]);
        }

        $this->showModal = false;
        $this->dispatch('saved');
    }

    /** C2: ສ້າງ ໃບ ສ້ອມ (CM · type=repair) ຈາກ ຂໍ້ NG ຂອງ ໃບ ບຳລຸງ/ກວດ ຕົ້ນທາງ. idempotent (ບໍ່ ຊ້ຳ ຕໍ່ label). */
    public function createRepairsFromNg(int $id): void
    {
        abort_unless(auth()->user()->can('equipment.create'), 403);
        $src = EquipmentMaintenance::with('equipment')->findOrFail($id);
        if ($src->equipment) {
            $this->guardDept($src->equipment);
        }

        $ng = collect($src->checklist ?? [])->where('status', 'ng')
            ->map(fn ($c) => trim((string) ($c['label'] ?? '')))->filter()->unique();
        if ($ng->isEmpty() || ! $src->equipment) {
            return;
        }

        $existing = $src->correctiveRepairs()->pluck('title')->all();
        $ref = 'ຈາກ: '.($src->title ?: 'ບຳລຸງ/ກວດ').' · '.optional($src->maintenance_date)->format('d/m/Y');
        $created = 0;
        foreach ($ng as $label) {
            if (in_array($label, $existing, true)) {
                continue;
            }
            $src->equipment->maintenances()->create([
                'source_maintenance_id' => $src->id,
                'maintenance_date' => now()->toDateString(),
                'type' => 'repair',
                'title' => $label,
                'description' => $ref,
                'status' => 'planned',
                'created_by' => auth()->id(),
            ]);
            $created++;
        }

        session()->flash('ok', $created ? "✓ ສ້າງ ໃບ ສ້ອມ (CM) {$created} ໃບ ຈາກ NG" : 'ທຸກ ຂໍ້ NG ມີ ໃບ ສ້ອມ ແລ້ວ');
        $this->dispatch('saved');
    }

    // ── ລຶບ-ດ້ວຍ-ເຫດຜົນ + Deleted Log (trait SoftDeletesWithReason) ──
    protected function deleteModelClass(): string
    {
        return EquipmentMaintenance::class;
    }

    protected function deletePermission(): string
    {
        return 'equipment.delete';
    }

    protected function deleteGuard(Model $record): void
    {
        if ($record->equipment) {
            $this->guardDept($record->equipment);
        }
    }

    protected function deleteLabel(Model $record): string
    {
        return $record->title ?: ('#'.$record->getKey());
    }

    protected function deleteNoun(): string
    {
        return 'ບັນທຶກ ບຳລຸງ';
    }

    public function viewHistory(int $equipmentId): void
    {
        abort_unless(auth()->user()->can('equipment.view'), 403);
        $e = Equipment::findOrFail($equipmentId);
        $this->guardDept($e);
        $this->historyEquipmentId = $equipmentId;
    }

    /** ເບິ່ງ ບັນທຶກ ບຳລຸງ read-only (ເຫັນ ໜ້າ ນີ້ ກໍ່ ເບິ່ງ ໄດ້; ດຶງ dept scope ຕາມ ເຄື່ອງ). */
    public function viewRecord(int $id): void
    {
        $m = EquipmentMaintenance::with('equipment')->findOrFail($id);
        if ($m->equipment) {
            $this->guardDept($m->equipment);
        }
        $this->viewingId = $id;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $deptScoped = $this->deptScoped();
        $deptId = $this->userDeptId();

        $searchResults = strlen($this->mSearch) >= 2
            ? Equipment::where(fn ($q) => $q->where('name', 'like', "%{$this->mSearch}%")
                ->orWhere('asset_code', 'like', "%{$this->mSearch}%"))
                ->when($deptScoped, fn ($q) => $q->where('department_id', $deptId))
                ->orderBy('asset_code')->limit(8)->get()
            : collect();

        $records = EquipmentMaintenance::with('equipment')
            ->when($this->showDeleted && $this->canManageDeleted(), fn ($q) => $q->onlyTrashed()->with('deletedBy'))
            ->when($deptScoped, fn ($q) => $q->whereHas('equipment', fn ($w) => $w->where('department_id', $deptId)))
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$this->search}%")
                ->orWhere('performed_by', 'like', "%{$this->search}%")
                ->orWhereHas('equipment', fn ($e) => $e
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('asset_code', 'like', "%{$this->search}%"))))
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('maintenance_date')->orderByDesc('id')
            ->paginate(10);

        // ແມ່ແບບ ເຊັກລິສ ສຳລັບ ເຄື່ອງ ທີ່ ເລືອກ — ຄັດ ຕາມ scope: ຕໍ່ ເຄື່ອງ ນີ້ · ຕໍ່ ປະເພດ ຂອງ ມັນ · ທົ່ວໄປ.
        // ຕິກ "ໂຊ ທັງໝົດ" → ໂຊ ແມ່ແບບ active ໝົດ (ບໍ່ ຄັດ).
        $templateOptions = collect();
        if ($this->mEquipmentId) {
            $q = MaintenanceTemplate::where('is_active', true);
            if (! $this->mShowAllTemplates) {
                $eqCat = Equipment::whereKey($this->mEquipmentId)->value('category');
                $q->where(fn ($w) => $w
                    ->where('equipment_id', $this->mEquipmentId)                              // ຕໍ່ ເຄື່ອງ ນີ້
                    ->orWhere(fn ($g) => $g->whereNull('equipment_id')->where(fn ($c) => $c   // ທົ່ວໄປ / ຕໍ່ ປະເພດ
                        ->whereNull('category')->when($eqCat, fn ($x) => $x->orWhere('category', $eqCat)))));
            }
            $templateOptions = $q->orderBy('name')->get(['id', 'name', 'equipment_id', 'category']);
        }

        $historyEquipment = $this->historyEquipmentId ? Equipment::find($this->historyEquipmentId) : null;
        $history = $this->historyEquipmentId
            ? EquipmentMaintenance::where('equipment_id', $this->historyEquipmentId)
                ->orderByDesc('maintenance_date')->orderByDesc('id')->limit(50)->get()
            : collect();

        // ຈັດ ເຊັກລິສ ເປັນ ໝວດ + ຄັດ ຕາມ filter (ຮັກສາ index ເດີມ ໄວ້ ສຳລັບ toggle).
        $ckNgCount = 0;
        $ckGroups = [];
        foreach ($this->mChecklist as $i => $c) {
            if (($c['status'] ?? '') === 'ng') {
                $ckNgCount++;
            }
            if ($this->ckAction !== '' && ($c['action'] ?? '') !== $this->ckAction) {
                continue;
            }
            if ($this->ckNg && ($c['status'] ?? '') !== 'ng') {
                continue;
            }
            $g = $c['group'] ?? 'other';
            if (! array_key_exists($g, MaintenanceTemplate::GROUPS)) {
                $g = 'other';
            }
            $ckGroups[$g][] = ['i' => $i] + $c;
        }
        $checklistGroups = [];
        foreach (array_keys(MaintenanceTemplate::GROUPS) as $gk) {
            if (! empty($ckGroups[$gk])) {
                $checklistGroups[$gk] = $ckGroups[$gk];
            }
        }

        return view('livewire.equipment.maintenance', [
            'searchResults' => $searchResults,
            'records' => $records,
            'historyEquipment' => $historyEquipment,
            'history' => $history,
            'deptScoped' => $deptScoped,
            'templateOptions' => $templateOptions,
            'viewing' => $this->viewingId ? EquipmentMaintenance::with('equipment')->find($this->viewingId) : null,
            'canManageDeleted' => $this->canManageDeleted(),
            'checklistGroups' => $checklistGroups,
            'checklistTotal' => count($this->mChecklist),
            'checklistNgCount' => $ckNgCount,
            'mTemplateHasFuelTypes' => $this->mTemplateId ? (MaintenanceTemplate::find($this->mTemplateId)?->hasFuelTypes() ?? false) : false,
        ]);
    }
}
