<?php

namespace App\Livewire\Disposal;

use App\Models\Department;
use App\Models\DepositItem;
use App\Models\Equipment;
use App\Models\EquipmentInspection;
use App\Models\EquipmentMaintenance;
use App\Models\InventoryItem;
use App\Models\Uom;
use App\Services\DisposalService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Create extends Component
{
    use WithFileUploads;

    public string $title = '';

    public ?int $department_id = null;

    public string $note = '';

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile[]> ຮູບ ຕໍ່ item */
    public array $photos = [];

    /** @var array<int, array<int, array{source:string,id:int,code:string,fixed:?string,name:string}>> */
    public array $assetMatches = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('disposal.create'), 403);
        $this->department_id = auth()->user()->department_id;
        $this->items = [$this->blankItem()];

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
            'reason' => '', 'reason_detail' => '',
            'recommendation' => '', 'recommendation_detail' => '',
            'estimated_value' => '', 'currency' => '',
            'history' => [],   // [{date,kind,problem,action,include}]
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

        if ($source === 'equipment') {
            $e = Equipment::find($id);
            if (! $e) {
                return;
            }
            $this->items[$i]['asset_code'] = $e->asset_code;
            $this->items[$i]['fixed_asset_no'] = $e->fixed_asset_no ?? '';
            $this->items[$i]['unit'] = $this->items[$i]['unit'] ?: ($e->unit?->name ?? '');
            $name = $e->name;
            $this->items[$i]['history'] = $this->pullEquipmentHistory($id);
        } elseif ($source === 'inventory') {
            $x = InventoryItem::find($id);
            if (! $x) {
                return;
            }
            $this->items[$i]['asset_code'] = $x->slug;
            $this->items[$i]['unit'] = $this->items[$i]['unit'] ?: ($x->unit ?? '');
            $name = $x->name;
        } else { // deposit
            $x = DepositItem::find($id);
            if (! $x) {
                return;
            }
            $this->items[$i]['asset_code'] = $x->asset_code ?? '';
            $this->items[$i]['fixed_asset_no'] = $x->fixed_asset_no ?? '';
            $this->items[$i]['unit'] = $this->items[$i]['unit'] ?: ($x->unit ?? '');
            $name = $x->item_name;
        }
        if (trim((string) ($this->items[$i]['item_name'] ?? '')) === '') {
            $this->items[$i]['item_name'] = $name;
        }
        $this->assetMatches[$i] = [];
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
            $out[] = ['date' => $ins->inspected_at?->format('Y-m-d'), 'kind' => 'inspection', 'problem' => 'ກວດ ບໍ່ຜ່ານ'.($ins->notes ? ' · '.\Illuminate\Support\Str::limit($ins->notes, 40) : ''), 'action' => 'ຕິດຕາມ/ສ້ອມ', 'include' => true];
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:256'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.asset_code' => ['nullable', 'string', 'max:64'],
            'items.*.reason' => ['nullable', 'string', 'max:128'],
            'items.*.recommendation' => ['nullable', 'string', 'max:128'],
            'items.*.estimated_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.currency' => ['nullable', 'in:LAK,THB,USD'],
            'photos.*.*' => ['image', 'max:8192'],
        ], [], ['title' => 'ຫົວຂໍ້', 'items.*.item_name' => 'ຊື່ເຄື່ອງ']);

        // ກັນ forged source_id: ຖ້າ ດຶງ ຈາກ ທະບຽນ, id ຕ້ອງ ມີ ຈິງ ຕາມ ປະເພດ — ບໍ່ ດັ່ງນັ້ນ
        // ຕອນ ຈຳໜ່າຍ updateSourceRegisters ອາດ ໄປ ແຕະ ຊັບ ອື່ນ ທີ່ ບໍ່ ກ່ຽວ.
        $exists = [
            'inventory' => fn ($id) => \App\Models\InventoryItem::whereKey($id)->exists(),
            'equipment' => fn ($id) => \App\Models\Equipment::whereKey($id)->exists(),
            'deposit' => fn ($id) => \App\Models\DepositItem::whereKey($id)->exists(),
        ];
        foreach (array_values($this->items) as $i => $it) {
            $st = $it['source_type'] ?? 'new';
            $sid = $it['source_id'] ?? null;
            if (isset($exists[$st]) && $sid && ! $exists[$st]($sid)) {
                $this->addError("items.$i.item_name", 'ແຫຼ່ງ ທີ່ ດຶງ ບໍ່ ພົບ ໃນ ທະບຽນ.');
                throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'invalid source_id']);
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
            'items' => $payloadItems,
        ], auth()->user());

        // ຮູບ ຕໍ່ item → ເກັບ ໄຟລ໌ + ຝັງ path ໃສ່ item.photos
        foreach ($record->items as $idx => $item) {
            $paths = [];
            foreach (array_values($this->photos[$idx] ?? []) as $file) {
                $paths[] = $file->store("disposal/{$record->id}/{$item->id}", 'public');
            }
            if ($paths) {
                $item->update(['photos' => $paths]);
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
            'reasons' => \App\Models\DisposalRecord::REASONS,
            'recommendations' => \App\Models\DisposalRecord::RECOMMENDATIONS,
        ]);
    }
}
