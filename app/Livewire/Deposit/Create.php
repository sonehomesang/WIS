<?php

namespace App\Livewire\Deposit;

use App\Models\Department;
use App\Models\Equipment;
use App\Models\InventoryItem;
use App\Models\Setting;
use App\Models\Uom;
use App\Models\User;
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

    public string $request_type = 'legacy';   // default = ເຄື່ອງຝາກເກົ່າ · ຄ້າງ ດົນ

    public string $item_category = '';

    public string $origin_source = '';

    // ສະຖານະ ການ ໃຊ້ງານ / functional status ຂອງ ເຄື່ອງ (usable · partial · unusable)
    public string $functional_status = '';

    // ເຄື່ອງຝາກເກົ່າ (legacy): ວັນທີ ຝາກ ຈິງ ໃນ ອະດີດ + ຜູ້ ຮັບ ຝາກ ຕອນ ນັ້ນ (ບໍ່ ບັງຄັບ)
    public string $original_deposit_date = '';

    public string $original_receiver = '';

    public string $deposit_reason = '';

    public string $expected_duration = '';

    public string $deposit_date;

    public string $expected_arrival = '';

    public string $expected_claim_date = '';

    public string $remark = '';

    /** ເຈົ້າ ຂອງ ເຄື່ອງ ຝາກ (ດຶງ ຈາກ ຜູ້ ໃຊ້). Default = ຜູ້ ສ້າງ. */
    public ?int $owner_user_id = null;

    /** ພະແນກ ເຈົ້າ ຂອງ ເຄື່ອງ (Org Unit derived). Default = ຜູ້ ສ້າງ. */
    public ?int $owner_dept_id = null;

    /** @var array<int, array{item_name:string, asset_code:string, fixed_asset_no:string, description:string, qty:int, unit:string, estimated_value:string, currency:string, condition_on_deposit:string}> */
    public array $items = [];

    /** @var array<int, array<string, TemporaryUploadedFile[]>> ຮູບ deposit ຕໍ່ item (index) ຕໍ່ slot — ຄັງ ຖາວອນ */
    public array $photos = [];

    /** @var array<int, array<string, TemporaryUploadedFile[]>> ຮູບ ຈາກ ກ້ອງ (ຊົ່ວຄາວ → merge ເຂົ້າ photos) */
    public array $camUpload = [];

    /** @var array<int, array<string, TemporaryUploadedFile[]>> ຮູບ ຈາກ ແກເລີຣີ (ຊົ່ວຄາວ → merge ເຂົ້າ photos) */
    public array $galUpload = [];

    /** ມູມ ຮູບ ຝາກ ຕອນ ຮັບ — ແຍກ ອິດສະລະ, ແຕ່ ລະ ຊ່ອງ ຮັບ ໄດ້ ຈາກ ກ້ອງ ແລະ ແກເລີຣີ. [label, icon] */
    public const PHOTO_SLOTS = [
        'overall' => ['ຮູບ ໂດຍ ລວມ (Zoom-out)', '🔍'],
        'id' => ['ຮູບ ລະຫັດ ເຄື່ອງ / ຊັບສິນ', '🏷️'],
        'damage' => ['ຈຸດ ທີ່ ເປ ເພ / ເສຍ', '⚠️'],
    ];

    /** @var array<int, array<int, array{source:string,id:int,code:string,fixed:?string,name:string}>>
     *  ຜົນ ຄົ້ນ ຕໍ່ ແຖວ item (index) — ຈາກ Inventory ຫຼື Equipment ຕາມ ແຫຼ່ງ ທີ່ ເລືອກ. */
    public array $assetMatches = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('deposit.create'), 403);
        $this->deposit_date = Carbon::today()->toDateString();
        $this->owner_user_id = auth()->id();
        $this->owner_dept_id = auth()->user()->department_id;
        $this->items = [$this->blankItem()];
    }

    /** Optional per-item fields the admin can show/hide (Settings › System). Default hidden. */
    public const OPTIONAL_ITEM_FIELDS = [
        'condition_on_deposit' => 'ສະພາບ ຕອນ ຝາກ',
        'estimated_value' => 'ມູນຄ່າ (ປະມານ)',
        'currency' => 'ສະກຸນເງິນ',
        'description' => 'ລາຍລະອຽດ (Description)',
    ];

    protected function blankItem(): array
    {
        // asset_source = ແຫຼ່ງ ຂອງ ທະບຽນເຄື່ອງ: 'inventory' | 'equipment' | 'new' (UI ຢ່າງດຽວ — ບໍ່ ບັນທຶກ).
        return ['item_name' => '', 'asset_source' => 'inventory', 'asset_code' => '', 'fixed_asset_no' => '', 'description' => '', 'qty' => 1, 'unit' => '', 'estimated_value' => '', 'currency' => '', 'condition_on_deposit' => '', 'storage_location' => '', 'condition_status' => 'in_service'];
    }

    public function addItem(): void
    {
        $this->items[] = $this->blankItem();
    }

    // ── ຮູບ: ກ້ອງ / ແກເລີຣີ → ສະສົມ ເຂົ້າ photos (ບໍ່ ທັບ ກັນ) ──
    public function updatedCamUpload($value, $key): void
    {
        $this->absorbPhotos('camUpload', $key);
    }

    public function updatedGalUpload($value, $key): void
    {
        $this->absorbPhotos('galUpload', $key);
    }

    protected function absorbPhotos(string $prop, $key): void
    {
        // $key = "{itemIndex}.{slot}" ເຊັ່ນ "0.overall"
        [$i, $slot] = array_pad(explode('.', (string) $key, 2), 2, null);
        $i = (int) $i;
        if (! array_key_exists($slot, self::PHOTO_SLOTS)) {
            return;
        }
        $files = $this->{$prop}[$i][$slot] ?? [];
        $files = array_values(array_filter(is_array($files) ? $files : [$files]));
        if ($files) {
            $this->photos[$i][$slot] = array_merge($this->photos[$i][$slot] ?? [], $files);
        }
        unset($this->{$prop}[$i][$slot]);
        if (empty($this->{$prop}[$i])) {
            unset($this->{$prop}[$i]);
        }
    }

    public function removePhoto(int $i, string $slot, int $j): void
    {
        if (isset($this->photos[$i][$slot][$j])) {
            unset($this->photos[$i][$slot][$j]);
            $this->photos[$i][$slot] = array_values($this->photos[$i][$slot]);
        }
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
        unset($this->items[$i], $this->photos[$i], $this->camUpload[$i], $this->galUpload[$i]);
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
            'request_type' => ['required', 'in:walk_in,pre_request,legacy'],
            // ຂໍ້ມູນ ທົ່ວໄປ = ຂັ້ນ 2 (ຫ້ອງການ) → ບໍ່ ບັງຄັບ ຕອນ ບັນທຶກ ໜ້າງານ
            'item_category' => ['nullable', 'string', 'max:256'],
            'origin_source' => ['nullable', 'string', 'max:500'],
            'functional_status' => ['nullable', 'in:usable,partial,unusable'],
            'original_deposit_date' => ['nullable', 'date'],
            'original_receiver' => ['nullable', 'string', 'max:256'],
            'deposit_reason' => ['nullable', 'string', 'max:1000'],
            'expected_duration' => ['nullable', 'string', 'max:128'],
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
            'items.*.storage_location' => ['nullable', 'string', 'max:256'],
            'items.*.estimated_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.currency' => ['nullable', 'in:LAK,THB,USD'],
            'photos.*.*.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [], ['item_category' => 'ປະເພດ', 'origin_source' => 'ແຫຼ່ງທີ່ມາ', 'deposit_reason' => 'ເຫດຜົນ', 'expected_duration' => 'ໄລຍະເວລາ']);

        // submit → ບັງຄັບ ≥1 ຮູບ ຕໍ່ລາຍການ (ຢ່າງໜ້ອຍ 1 ຊ່ອງ ໃນ 3 ມູມ) (evidence)
        if ($submit) {
            foreach ($this->items as $i => $it) {
                if (empty(array_filter($this->photos[$i] ?? []))) {
                    $this->addError('photos', 'ຕ້ອງມີຢ່າງໜ້ອຍ 1 ຮູບ ສຳລັບລາຍການທີ '.($i + 1).' ກ່ອນສົ່ງ.');
                    throw ValidationException::withMessages(['photos' => 'photo required']);
                }
            }
        }

        $record = app(DepositService::class)->createDraft([
            'request_type' => $this->request_type,
            'item_category' => $this->item_category,
            'origin_source' => $this->origin_source,
            'functional_status' => $this->functional_status ?: null,
            'original_deposit_date' => $this->original_deposit_date ?: null,
            'original_receiver' => $this->original_receiver ?: null,
            'deposit_reason' => $this->deposit_reason,
            'expected_duration' => $this->expected_duration,
            'deposit_date' => $this->deposit_date,
            'expected_arrival' => $this->expected_arrival ?: null,
            'expected_claim_date' => $this->expected_claim_date ?: null,
            'remark' => $this->remark ?: null,
            'owner_user_id' => $this->owner_user_id,
            'owner_dept_id' => $this->owner_dept_id,
            'owner_unit_id' => $this->owner_dept_id ? Department::find($this->owner_dept_id)?->unit_id : null,
            'items' => $this->items,
        ], auth()->user());

        // ເກັບຮູບ deposit ຕໍ່ item (ຕາມ index) ແຍກ 3 ມູມ (slot): overall · id · damage
        foreach ($record->items as $idx => $item) {
            $sort = 0;
            foreach (array_keys(self::PHOTO_SLOTS) as $slot) {
                foreach (array_values($this->photos[$idx][$slot] ?? []) as $file) {
                    $path = $file->store("deposit/{$record->id}/{$item->id}", 'public');
                    $item->photos()->create(['kind' => 'deposit', 'slot' => $slot, 'path' => $path, 'sort_order' => $sort++]);
                }
            }
        }

        // ຂັ້ນ 1 (ໜ້າງານ) = ບັນທຶກ ຮ່າງ ແລ້ວ ໄປ ຟອມ ໃໝ່ ເລີຍ; ຫົວໜ້າ ຕື່ມ ຂໍ້ມູນ + ສົ່ງ ໃນ ໜ້າ ແກ້ໄຂ (ຂັ້ນ 2)
        session()->flash('ok', "✓ ບັນທຶກ {$record->request_number} ແລ້ວ · ເພີ່ມ ລາຍການ ໃໝ່ ໄດ້ ເລີຍ");
        $this->redirectRoute('deposit.create', navigate: true);
    }

    /** ຟິລ optional ທີ່ ຄວນ ສະແດງ (admin ຕັ້ງ ໃນ Settings › System) — default ເຊື່ອງ. */
    protected function optionalFieldVisibility(): array
    {
        $saved = Setting::get('deposit', [])['fields'] ?? [];
        $out = [];
        foreach (self::OPTIONAL_ITEM_FIELDS as $k => $_) {
            $out[$k] = (bool) ($saved[$k] ?? false);
        }

        return $out;
    }

    public function render(): View
    {
        return view('livewire.deposit.create', [
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::where('is_active', true)->with('unit:id,name')->orderBy('name')->get(['id', 'name', 'unit_id']),
            'ownerUsers' => User::where('status', 'active')->orderBy('display_name')->get(['id', 'display_name', 'email']),
            'fieldVisible' => $this->optionalFieldVisibility(),
        ]);
    }
}
