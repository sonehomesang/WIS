<?php

namespace App\Livewire\Deposit;

use App\Models\Equipment;
use App\Models\InventoryItem;
use App\Models\Uom;
use App\Services\DepositService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Create extends Component
{
    use WithFileUploads;

    public string $request_type = 'walk_in';

    public string $item_category = '';

    public string $origin_source = '';

    public string $deposit_reason = '';

    public string $expected_duration = '';

    public string $deposit_date;

    public string $expected_arrival = '';

    public string $expected_claim_date = '';

    public string $remark = '';

    /** @var array<int, array{item_name:string, asset_code:string, fixed_asset_no:string, description:string, qty:int, unit:string, estimated_value:string, currency:string, condition_on_deposit:string}> */
    public array $items = [];

    /** @var array<int, TemporaryUploadedFile[]> ຮູບ deposit ຕໍ່ item (index) */
    public array $photos = [];

    /** @var array<int, array<int, array{source:string,id:int,code:string,fixed:?string,name:string}>>
     *  ຜົນ ຄົ້ນ ຕໍ່ ແຖວ item (index) — ຈາກ Inventory ຫຼື Equipment ຕາມ ແຫຼ່ງ ທີ່ ເລືອກ. */
    public array $assetMatches = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('deposit.create'), 403);
        $this->deposit_date = Carbon::today()->toDateString();
        $this->items = [$this->blankItem()];
    }

    protected function blankItem(): array
    {
        // asset_source = ແຫຼ່ງ ຂອງ ທະບຽນເຄື່ອງ: 'inventory' | 'equipment' | 'new' (UI ຢ່າງດຽວ — ບໍ່ ບັນທຶກ).
        return ['item_name' => '', 'asset_source' => 'inventory', 'asset_code' => '', 'fixed_asset_no' => '', 'description' => '', 'qty' => 1, 'unit' => '', 'estimated_value' => '', 'currency' => '', 'condition_on_deposit' => ''];
    }

    public function addItem(): void
    {
        $this->items[] = $this->blankItem();
    }

    /** ພິມ ໃນ ຊ່ອງ ທະບຽນເຄື່ອງ → ຄົ້ນ ຈາກ ແຫຼ່ງ ທີ່ ເລືອກ (Inventory/Equipment); 'new' = ພິມ ເອງ. */
    public function updatedItems($value, $key): void
    {
        [$i, $field] = array_pad(explode('.', (string) $key, 2), 2, null);
        $i = (int) $i;

        if ($field === 'asset_source') {
            $this->assetMatches[$i] = [];   // ປ່ຽນ ແຫຼ່ງ → ລ້າງ ຜົນ ຄົ້ນ ເກົ່າ

            return;
        }
        if ($field !== 'asset_code') {
            return;
        }

        $src = $this->items[$i]['asset_source'] ?? 'inventory';
        $term = trim((string) $value);
        if ($src === 'new' || strlen($term) < 2) {
            $this->assetMatches[$i] = [];

            return;
        }

        if ($src === 'equipment') {
            $this->assetMatches[$i] = Equipment::query()
                ->where(fn ($q) => $q->where('asset_code', 'like', "%{$term}%")
                    ->orWhere('fixed_asset_no', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%"))
                ->orderBy('asset_code')->limit(6)
                ->get(['id', 'asset_code', 'fixed_asset_no', 'name'])
                ->map(fn ($e) => ['source' => 'equipment', 'id' => $e->id, 'code' => $e->asset_code, 'fixed' => $e->fixed_asset_no, 'name' => $e->name])
                ->all();
        } else { // inventory
            $this->assetMatches[$i] = InventoryItem::query()
                ->where(fn ($q) => $q->where('slug', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%")
                    ->orWhere('serial_number', 'like', "%{$term}%"))
                ->orderBy('slug')->limit(6)
                ->get(['id', 'slug', 'name', 'serial_number'])
                ->map(fn ($x) => ['source' => 'inventory', 'id' => $x->id, 'code' => $x->slug, 'fixed' => null, 'name' => $x->name])
                ->all();
        }
    }

    /** ເລືອກ ຈາກ ຜົນ ຄົ້ນ → ຕື່ມ ທະບຽນເຄື່ອງ (+ ຊັບສິນ ຖ້າ ມາ ຈາກ Equipment) (+ ຊື່ ຖ້າ ຍັງ ຫວ່າງ). */
    public function pickAsset(int $i, string $source, int $id): void
    {
        if (! isset($this->items[$i])) {
            return;
        }
        if ($source === 'equipment') {
            $e = Equipment::find($id);
            if (! $e) {
                return;
            }
            $this->items[$i]['asset_code'] = $e->asset_code;
            $this->items[$i]['fixed_asset_no'] = $e->fixed_asset_no ?? '';
            $name = $e->name;
        } else {
            $x = InventoryItem::find($id);
            if (! $x) {
                return;
            }
            $this->items[$i]['asset_code'] = $x->slug;
            // Inventory ບໍ່ ມີ ເລກ ຊັບສິນ — ຄົງ ຄ່າ ເກົ່າ ໄວ້.
            $name = $x->name;
        }
        if (trim((string) ($this->items[$i]['item_name'] ?? '')) === '') {
            $this->items[$i]['item_name'] = $name;
        }
        $this->assetMatches[$i] = [];
    }

    public function removeItem(int $i): void
    {
        unset($this->items[$i], $this->photos[$i]);
        $this->items = array_values($this->items);
        $this->photos = array_values($this->photos);
        $this->assetMatches = [];   // index shift → ລ້າງ ຜົນ ຄົ້ນ ທັງໝົດ ກັນ ຫຼົງ ແຖວ
        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }
    }

    public function save(bool $submit = false): void
    {
        abort_unless(auth()->user()->can('deposit.create'), 403);

        $this->validate([
            'request_type' => ['required', 'in:walk_in,pre_request'],
            'item_category' => ['required', 'string', 'max:256'],
            'origin_source' => ['required', 'string', 'max:500'],
            'deposit_reason' => ['required', 'string', 'max:1000'],
            'expected_duration' => ['required', 'string', 'max:128'],
            'deposit_date' => ['required', 'date'],
            'expected_arrival' => ['nullable', 'date'],
            'expected_claim_date' => ['nullable', 'date'],
            'remark' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:256'],
            'items.*.asset_code' => ['nullable', 'string', 'max:64'],
            'items.*.fixed_asset_no' => ['nullable', 'string', 'max:64'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.unit' => ['nullable', 'string', 'max:32'],
            'items.*.estimated_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.currency' => ['nullable', 'in:LAK,THB,USD'],
            'photos.*.*' => ['image', 'max:4096'],
        ], [], ['item_category' => 'ປະເພດ', 'origin_source' => 'ແຫຼ່ງທີ່ມາ', 'deposit_reason' => 'ເຫດຜົນ', 'expected_duration' => 'ໄລຍະເວລາ']);

        // submit → ບັງຄັບ ≥1 ຮູບ ຕໍ່ລາຍການ (evidence)
        if ($submit) {
            foreach ($this->items as $i => $it) {
                if (empty($this->photos[$i])) {
                    $this->addError('photos', 'ຕ້ອງມີຢ່າງໜ້ອຍ 1 ຮູບ ສຳລັບລາຍການທີ '.($i + 1).' ກ່ອນສົ່ງ.');
                    throw ValidationException::withMessages(['photos' => 'photo required']);
                }
            }
        }

        $record = app(DepositService::class)->createDraft([
            'request_type' => $this->request_type,
            'item_category' => $this->item_category,
            'origin_source' => $this->origin_source,
            'deposit_reason' => $this->deposit_reason,
            'expected_duration' => $this->expected_duration,
            'deposit_date' => $this->deposit_date,
            'expected_arrival' => $this->expected_arrival ?: null,
            'expected_claim_date' => $this->expected_claim_date ?: null,
            'remark' => $this->remark ?: null,
            'items' => $this->items,
        ], auth()->user());

        // ເກັບຮູບ deposit ຕໍ່ item (ຕາມ index)
        foreach ($record->items as $idx => $item) {
            foreach (array_values($this->photos[$idx] ?? []) as $sort => $file) {
                $path = $file->store("deposit/{$record->id}/{$item->id}", 'public');
                $item->photos()->create(['kind' => 'deposit', 'path' => $path, 'sort_order' => $sort]);
            }
        }

        if ($submit) {
            app(DepositService::class)->transition($record, 'submit', auth()->user());
        }

        session()->flash('ok', $submit ? "ສົ່ງຄຳຂໍຝາກ {$record->request_number} ແລ້ວ" : "ບັນທຶກ draft {$record->request_number} ແລ້ວ");
        $this->redirectRoute('deposit.show', $record, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.deposit.create', [
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
