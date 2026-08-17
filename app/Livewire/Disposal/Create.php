<?php

namespace App\Livewire\Disposal;

use App\Models\Department;
use App\Models\DepositItem;
use App\Models\DisposalRecord;
use App\Models\Equipment;
use App\Models\EquipmentInspection;
use App\Models\EquipmentMaintenance;
use App\Models\InventoryItem;
use App\Models\Uom;
use App\Services\DisposalService;
use App\Support\ConditionStatus;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Create extends Component
{
    use WithFileUploads;

    public string $title = '';

    public ?int $department_id = null;

    public string $note = '';

    // ເຄື່ອງຝາກເກົ່າ: ໃບ ຈຳໜ່າຍ ເຄື່ອງ ຝາກ ທີ່ ຄ້າງ ໄວ້ ດົນ (ບໍ່ ບັງຄັບ).
    public string $original_deposit_date = '';

    public string $original_receiver = '';

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    /** @var array<int, TemporaryUploadedFile[]> ຮູບ ຕໍ່ item */
    public array $photos = [];

    /** @var array<int, array<int, array{source:string,id:int,code:string,fixed:?string,name:string}>> */
    public array $assetMatches = [];

    // ── auto-pull to disposal (by condition-status) ──
    public bool $showPull = false;

    /** @var array<string,bool> */
    public array $pullSources = ['inventory' => true, 'equipment' => true, 'deposit' => true];

    /** @var array<string,bool> */
    public array $pullStatuses = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('disposal.create'), 403);
        $this->department_id = auth()->user()->department_id;
        $this->items = [$this->blankItem()];
        foreach (ConditionStatus::disposable() as $s) {
            $this->pullStatuses[$s] = true;
        }

        // preload ຈາກ ທະບຽນ ຕົ້ນທາງ (ປຸ່ມ "→ Disposal") — ?add=equipment:10
        if (preg_match('/^(inventory|equipment|deposit):(\d+)$/', (string) request()->query('add', ''), $m)) {
            $this->items[0]['source_type'] = $m[1];
            $this->pickAsset(0, $m[1], (int) $m[2]);
        }
    }

    protected function blankItem(): array
    {
        return [
            'source_type' => 'equipment', 'source_id' => null,
            'item_name' => '', 'asset_code' => '', 'fixed_asset_no' => '',
            'qty' => 1, 'unit' => '', 'condition' => '',
            'functional_status' => '',   // ໄຫຼ ຈາກ Deposit ຕອນ ດຶງ · ພິມ ເອງ ໄດ້
            'reason' => '', 'reason_detail' => '',
            'recommendation' => '', 'recommendation_detail' => '',
            'estimated_value' => '', 'currency' => '',
            'history' => [],   // [{date,kind,problem,action,include}]
            'photos' => [],    // path[] ດຶງ ຈາກ ຮູບ ຂອງ ແຫຼ່ງ (ຝາກ/ອຸປະກອນ/ສາງ)
        ];
    }

    public function addItem(): void
    {
        $this->items[] = $this->blankItem();
    }

    public function removeItem(int $i): void
    {
        unset($this->items[$i], $this->photos[$i]);
        $this->items = array_values($this->items);
        $this->photos = array_values($this->photos);
        $this->assetMatches = [];
        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }
    }

    /** ພິມ ໃນ ຊ່ອງ asset_code → ຄົ້ນ ຈາກ ແຫຼ່ງ ທີ່ ເລືອກ. */
    public function updatedItems($value, $key): void
    {
        [$i, $field] = array_pad(explode('.', (string) $key, 2), 2, null);
        $i = (int) $i;

        if ($field === 'source_type') {
            $this->assetMatches[$i] = [];

            return;
        }
        if ($field !== 'asset_code') {
            return;
        }

        $src = $this->items[$i]['source_type'] ?? 'equipment';
        $term = trim((string) $value);
        if ($src === 'new' || strlen($term) < 2) {
            $this->assetMatches[$i] = [];

            return;
        }

        $this->assetMatches[$i] = match ($src) {
            'inventory' => InventoryItem::query()
                ->where(fn ($q) => $q->where('slug', 'like', "%{$term}%")->orWhere('name', 'like', "%{$term}%")->orWhere('serial_number', 'like', "%{$term}%"))
                ->orderBy('slug')->limit(6)->get(['id', 'slug', 'name'])
                ->map(fn ($x) => ['source' => 'inventory', 'id' => $x->id, 'code' => $x->slug, 'fixed' => null, 'name' => $x->name])->all(),
            'deposit' => DepositItem::query()
                ->whereHas('record', fn ($r) => $r->whereIn('status', self::DEPOSIT_PULLABLE))   // ເຄື່ອງ ຍັງ ຢູ່ ສາງ ເທົ່ານັ້ນ
                ->where(fn ($q) => $q->where('item_name', 'like', "%{$term}%")->orWhere('asset_code', 'like', "%{$term}%"))
                ->orderByDesc('id')->limit(6)->get(['id', 'item_name', 'asset_code', 'fixed_asset_no'])
                ->map(fn ($x) => ['source' => 'deposit', 'id' => $x->id, 'code' => $x->asset_code ?: '—', 'fixed' => $x->fixed_asset_no, 'name' => $x->item_name])->all(),
            default => Equipment::query()
                ->where(fn ($q) => $q->where('asset_code', 'like', "%{$term}%")->orWhere('fixed_asset_no', 'like', "%{$term}%")->orWhere('name', 'like', "%{$term}%"))
                ->orderBy('asset_code')->limit(6)->get(['id', 'asset_code', 'fixed_asset_no', 'name'])
                ->map(fn ($e) => ['source' => 'equipment', 'id' => $e->id, 'code' => $e->asset_code, 'fixed' => $e->fixed_asset_no, 'name' => $e->name])->all(),
        };
    }

    /** ເລືອກ ຈາກ ຜົນ ຄົ້ນ → ຕື່ມ ຊ່ອງ + (Equipment) ດຶງ ປະຫວັດ ບັນຫາ/ສ້ອມ. */
    public function pickAsset(int $i, string $source, int $id): void
    {
        if (! isset($this->items[$i])) {
            return;
        }
        $this->items[$i]['source_type'] = $source;
        $this->items[$i]['source_id'] = $id;
        $this->items[$i]['history'] = [];
        $this->items[$i]['photos'] = [];

        if ($source === 'equipment') {
            $e = Equipment::with('photos')->find($id);
            if (! $e) {
                return;
            }
            $this->items[$i]['asset_code'] = $e->asset_code;
            $this->items[$i]['fixed_asset_no'] = $e->fixed_asset_no ?? '';
            $this->items[$i]['unit'] = $this->items[$i]['unit'] ?: ($e->unit?->name ?? '');
            $this->items[$i]['photos'] = $this->grabSourcePhotos($e->photos->pluck('path')->all(), $e->photo_path ?? null);
            $name = $e->name;
            $this->items[$i]['history'] = $this->pullEquipmentHistory($id);
        } elseif ($source === 'inventory') {
            $x = InventoryItem::with('photos')->find($id);
            if (! $x) {
                return;
            }
            $this->items[$i]['asset_code'] = $x->slug;
            $this->items[$i]['unit'] = $this->items[$i]['unit'] ?: ($x->unit ?? '');
            $this->items[$i]['photos'] = $this->grabSourcePhotos($x->photos->pluck('path')->all());
            $name = $x->name;
        } else { // deposit
            $x = DepositItem::with('photos', 'record')->find($id);
            if (! $x) {
                return;
            }
            $this->items[$i]['asset_code'] = $x->asset_code ?? '';
            $this->items[$i]['fixed_asset_no'] = $x->fixed_asset_no ?? '';
            $this->items[$i]['unit'] = $this->items[$i]['unit'] ?: ($x->unit ?? '');
            $this->items[$i]['photos'] = $this->grabSourcePhotos($x->orderedPhotoPaths());
            // functional ໄຫຼ ຈາກ ໃບ ຝາກ (ບໍ່ ທັບ ຄ່າ ທີ່ ພິມ ໄວ້ ແລ້ວ)
            $this->items[$i]['functional_status'] = $this->items[$i]['functional_status'] ?: ($x->record?->functional_status ?? '');
            $name = $x->item_name;
        }
        if (trim((string) ($this->items[$i]['item_name'] ?? '')) === '') {
            $this->items[$i]['item_name'] = $name;
        }
        $this->assetMatches[$i] = [];
    }

    /**
     * ດຶງ path ຮູບ ຈາກ ແຫຼ່ງ (ສູງ ສຸດ 6 ຮູບ · ຮອງຮັບ photo_path ດ່ຽວ ຂອງ ອຸປະກອນ).
     *
     * @param  array<int,?string>  $paths
     * @return array<int,string>
     */
    protected function grabSourcePhotos(array $paths, ?string $fallback = null): array
    {
        $paths = array_values(array_filter($paths));
        if (empty($paths) && $fallback) {
            $paths = [$fallback];
        }

        return array_slice($paths, 0, 6);
    }

    public function openPull(): void
    {
        $this->resetErrorBag('pull');
        $this->showPull = true;
    }

    /** ດຶງ ເຄື່ອງ ທີ່ ຢູ່ ໃນ ສະຖານະພາບ ທີ່ ເລືອກ ເຂົ້າ ໃບ ຈຳໜ່າຍ ອັດຕະໂນມັດ. */
    public function autoPull(): void
    {
        abort_unless(auth()->user()->can('disposal.create'), 403);
        $statuses = $this->selectedStatuses();
        if (empty($statuses)) {
            $this->addError('pull', 'ເລືອກ ຢ່າງ ໜ້ອຍ 1 ສະຖານະ.');

            return;
        }

        $existing = [];
        foreach ($this->items as $it) {
            if ($it['source_id']) {
                $existing[$it['source_type'].':'.$it['source_id']] = true;
            }
        }

        $added = 0;
        foreach ($this->pullQuery($statuses) as [$source, $id]) {
            if (isset($existing[$source.':'.$id])) {
                continue;
            }
            $this->items[] = $this->blankItem();
            $this->pickAsset(array_key_last($this->items), $source, $id);
            $existing[$source.':'.$id] = true;
            $added++;
        }

        // ຖິ້ມ ແຖວ ຫວ່າງ ເລີ່ມຕົ້ນ ຖ້າ ຍັງ ບໍ່ ໄດ້ ຕື່ມ
        $this->items = array_values(array_filter(
            $this->items,
            fn ($it) => $it['source_id'] || trim((string) $it['item_name']) !== ''
        ));
        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }

        $this->showPull = false;
        session()->flash('pullOk', "ດຶງ ເຂົ້າ {$added} ລາຍການ ຕາມ ສະຖານະພາບ.");
    }

    /** @return array<int, string> */
    protected function selectedStatuses(): array
    {
        return array_values(array_intersect(
            array_keys(array_filter($this->pullStatuses)),
            ConditionStatus::disposable()
        ));
    }

    /**
     * Owning-department clamp: a department-scoped preparer may only touch
     * their own department's assets. Broad roles / super_admin → null (no clamp).
     * Mirrors the record-dept clamp in save(). Returns -1 (fail-closed) if a
     * scoped user has no department.
     */
    protected function scopeDeptId(): ?int
    {
        $u = auth()->user();
        if ($u->is_super_admin) {
            return null;
        }
        // transactionScope precedence: all > department > assigned > own.
        // Only 'department' users (line_manager, department_admin) are clamped
        // to their own department's assets; all/assigned pull broadly.
        if ($u->transactionScope() === 'department') {
            return $u->department_id ?? -1;
        }

        return null;
    }

    /** ສະຖານະ deposit ທີ່ ເຄື່ອງ ຍັງ ຢູ່ ໃນ ສາງ ຈິງ → ດຶງ ໄປ ຈຳໜ່າຍ ໄດ້ (ບໍ່ ລວມ claimed/cancelled/ໃນ-disposal). */
    public const DEPOSIT_PULLABLE = ['accepted', 'stored', 'needs_fix'];

    /** @return array<int, array{0:string,1:int}> */
    protected function pullQuery(array $statuses): array
    {
        $dept = $this->scopeDeptId();
        $out = [];
        if (! empty($this->pullSources['equipment'])) {
            foreach (Equipment::whereIn('condition_status', $statuses)
                ->when($dept !== null, fn ($q) => $q->where('department_id', $dept))
                ->orderBy('asset_code')->limit(300)->pluck('id') as $id) {
                $out[] = ['equipment', (int) $id];
            }
        }
        if (! empty($this->pullSources['inventory'])) {
            foreach (InventoryItem::whereIn('condition_status', $statuses)
                ->when($dept !== null, fn ($q) => $q->where('department_id', $dept))
                ->orderBy('slug')->limit(300)->pluck('id') as $id) {
                $out[] = ['inventory', (int) $id];
            }
        }
        if (! empty($this->pullSources['deposit'])) {
            foreach (DepositItem::whereIn('condition_status', $statuses)
                ->whereHas('record', fn ($r) => $r->whereIn('status', self::DEPOSIT_PULLABLE))
                ->when($dept !== null, fn ($q) => $q->whereHas('record', fn ($r) => $r->where('owner_dept_id', $dept)))
                ->orderByDesc('id')->limit(300)->pluck('id') as $id) {
                $out[] = ['deposit', (int) $id];
            }
        }

        return $out;
    }

    public function pullCount(): int
    {
        $statuses = $this->selectedStatuses();
        if (empty($statuses)) {
            return 0;
        }
        $dept = $this->scopeDeptId();
        $n = 0;
        if (! empty($this->pullSources['equipment'])) {
            $n += Equipment::whereIn('condition_status', $statuses)
                ->when($dept !== null, fn ($q) => $q->where('department_id', $dept))->count();
        }
        if (! empty($this->pullSources['inventory'])) {
            $n += InventoryItem::whereIn('condition_status', $statuses)
                ->when($dept !== null, fn ($q) => $q->where('department_id', $dept))->count();
        }
        if (! empty($this->pullSources['deposit'])) {
            $n += DepositItem::whereIn('condition_status', $statuses)
                ->whereHas('record', fn ($r) => $r->whereIn('status', self::DEPOSIT_PULLABLE))
                ->when($dept !== null, fn ($q) => $q->whereHas('record', fn ($r) => $r->where('owner_dept_id', $dept)))->count();
        }

        return $n;
    }

    /**
     * ດຶງ ປະຫວັດ ບັນຫາ + ສ້ອມ/ປ່ຽນ ຂອງ ເຄື່ອງ: ຂໍ້ ບຳລຸງ NG + ໃບ ສ້ອມ (CM) + ໃບ ກວດ ບໍ່ຜ່ານ.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function pullEquipmentHistory(int $equipmentId): array
    {
        $out = [];

        $maints = EquipmentMaintenance::where('equipment_id', $equipmentId)
            ->orderByDesc('maintenance_date')->orderByDesc('id')->limit(30)->get();
        foreach ($maints as $m) {
            $date = $m->maintenance_date?->format('Y-m-d');
            if ($m->type === 'repair') {
                $out[] = ['date' => $date, 'kind' => 'repair', 'problem' => $m->title ?: 'ສ້ອມແປງ', 'action' => $m->description ?: 'ສ້ອມ', 'include' => true];
            }
            foreach (($m->checklist ?? []) as $c) {
                if (($c['status'] ?? '') === 'ng') {
                    $act = ($c['action'] ?? '') === 'X' ? 'ປ່ຽນ' : 'ສ້ອມ';
                    if (! empty($c['note'])) {
                        $act .= ' · '.$c['note'];
                    }
                    $out[] = ['date' => $date, 'kind' => 'maintenance', 'problem' => $c['label'] ?? '', 'action' => $act, 'include' => true];
                }
            }
        }

        $insps = EquipmentInspection::where('equipment_id', $equipmentId)->where('result', 'fail')
            ->orderByDesc('inspected_at')->limit(20)->get();
        foreach ($insps as $ins) {
            $out[] = ['date' => $ins->inspected_at?->format('Y-m-d'), 'kind' => 'inspection', 'problem' => 'ກວດ ບໍ່ຜ່ານ'.($ins->notes ? ' · '.Str::limit($ins->notes, 40) : ''), 'action' => 'ຕິດຕາມ/ສ້ອມ', 'include' => true];
        }

        return $out;
    }

    public function save(bool $submit = false): void
    {
        abort_unless(auth()->user()->can('disposal.create'), 403);

        $this->validate([
            'title' => ['nullable', 'string', 'max:256'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'note' => ['nullable', 'string', 'max:1000'],
            'original_deposit_date' => ['nullable', 'date'],
            'original_receiver' => ['nullable', 'string', 'max:256'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:256'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.asset_code' => ['nullable', 'string', 'max:64'],
            'items.*.functional_status' => ['nullable', 'in:usable,partial,unusable'],
            'items.*.reason' => ['nullable', 'string', 'max:128'],
            'items.*.recommendation' => ['nullable', 'string', 'max:128'],
            'items.*.estimated_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.currency' => ['nullable', 'in:LAK,THB,USD'],
            'photos.*.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ], [], ['title' => 'ຫົວຂໍ້', 'items.*.item_name' => 'ຊື່ເຄື່ອງ']);

        // ກັນ forged source_id: ຖ້າ ດຶງ ຈາກ ທະບຽນ, id ຕ້ອງ ມີ ຈິງ ຕາມ ປະເພດ — ບໍ່ ດັ່ງນັ້ນ
        // ຕອນ ຈຳໜ່າຍ updateSourceRegisters ອາດ ໄປ ແຕະ ຊັບ ອື່ນ ທີ່ ບໍ່ ກ່ຽວ.
        $exists = [
            'inventory' => fn ($id) => InventoryItem::whereKey($id)->exists(),
            'equipment' => fn ($id) => Equipment::whereKey($id)->exists(),
            'deposit' => fn ($id) => DepositItem::whereKey($id)->whereHas('record', fn ($r) => $r->whereIn('status', self::DEPOSIT_PULLABLE))->exists(),
        ];
        foreach (array_values($this->items) as $i => $it) {
            $st = $it['source_type'] ?? 'new';
            $sid = $it['source_id'] ?? null;
            if (isset($exists[$st]) && $sid && ! $exists[$st]($sid)) {
                $this->addError("items.$i.item_name", 'ແຫຼ່ງ ທີ່ ດຶງ ບໍ່ ພົບ ໃນ ທະບຽນ.');
                throw ValidationException::withMessages(['items' => 'invalid source_id']);
            }
        }

        // ກັນ ຂ້າມ ພະແນກ: ຜູ້ ໃຊ້ ທີ່ ຖືກ scope ພະແນກ ຈຳໜ່າຍ ໄດ້ ສະເພາະ ເຄື່ອງ ຂອງ ພະແນກ ຕົນ.
        if (($dept = $this->scopeDeptId()) !== null) {
            foreach (array_values($this->items) as $i => $it) {
                $sid = $it['source_id'] ?? null;
                if (! $sid) {
                    continue;
                }
                $ownDept = match ($it['source_type'] ?? 'new') {
                    'equipment' => Equipment::whereKey($sid)->value('department_id'),
                    'inventory' => InventoryItem::whereKey($sid)->value('department_id'),
                    'deposit' => optional(DepositItem::find($sid))->record?->owner_dept_id,
                    default => null,
                };
                if ($ownDept !== null && (int) $ownDept !== (int) $dept) {
                    $this->addError("items.$i.item_name", 'ບໍ່ ແມ່ນ ເຄື່ອງ ຂອງ ພະແນກ ທ່ານ — ຈຳໜ່າຍ ບໍ່ ໄດ້.');
                    throw ValidationException::withMessages(['items' => 'foreign department asset']);
                }
            }
        }

        $payloadItems = [];
        foreach (array_values($this->items) as $it) {
            $history = collect($it['history'] ?? [])
                ->filter(fn ($h) => ($h['include'] ?? true))
                ->map(fn ($h) => ['date' => $h['date'] ?? null, 'kind' => $h['kind'] ?? '', 'problem' => $h['problem'] ?? '', 'action' => $h['action'] ?? ''])
                ->values()->all();
            $payloadItems[] = $it + ['history' => $history];
        }

        // dept-admin ຖືກ ບັງຄັບ ໃຫ້ ພະແນກ ຕົນ (ຫ້າມ ຍື່ນ ໃສ່ ພະແນກ ອື່ນ).
        $u = auth()->user();
        $deptId = ($u->hasRole('department_admin') && ! $u->is_super_admin && ! $u->hasAnyRole(['admin', 'warehouse_staff', 'approver', 'line_manager']))
            ? $u->department_id
            : $this->department_id;

        $record = app(DisposalService::class)->createDraft([
            'title' => $this->title ?: null,
            'department_id' => $deptId,
            'note' => $this->note ?: null,
            'original_deposit_date' => $this->original_deposit_date ?: null,
            'original_receiver' => $this->original_receiver ?: null,
            'items' => $payloadItems,
        ], auth()->user());

        // ຮູບ ຕໍ່ item → ຮູບ ທີ່ ດຶງ ຈາກ ແຫຼ່ງ (ຝາກ/ອຸປະກອນ/ສາງ) ຄົງ ໄວ້ + ຕໍ່ ດ້ວຍ ຮູບ ທີ່ ອັບ ໂຫຼດ ໃໝ່
        foreach ($record->items as $idx => $item) {
            $paths = array_values($item->photos ?? []);   // ຮູບ ຈາກ ແຫຼ່ງ (ຝັງ ຕອນ createDraft)
            foreach (array_values($this->photos[$idx] ?? []) as $file) {
                $paths[] = $file->store("disposal/{$record->id}/{$item->id}", 'public');
            }
            if ($paths) {
                $item->update(['photos' => array_values($paths)]);
            }
        }

        if ($submit) {
            app(DisposalService::class)->transition($record, 'submit', auth()->user());
        }

        session()->flash('ok', $submit ? "ສົ່ງ ໃບ ຈຳໜ່າຍ {$record->request_number} ຂໍ ອະນຸມັດ ແລ້ວ" : "ບັນທຶກ ຮ່າງ {$record->request_number} ແລ້ວ");
        $this->redirectRoute('disposal.show', $record, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.disposal.create', [
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'reasons' => DisposalRecord::REASONS,
            'recommendations' => DisposalRecord::RECOMMENDATIONS,
            'pullCount' => $this->showPull ? $this->pullCount() : 0,
        ]);
    }
}
