<?php

namespace App\Livewire\Borrow;

use App\Models\Equipment;
use App\Models\InventoryItem;
use App\Models\User;
use App\Services\BorrowService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Create extends Component
{
    use WithFileUploads;

    public string $borrow_type = 'new_inventory';

    public string $purpose = '';

    public string $remark = '';

    public string $others_detail = '';

    public string $borrow_date;

    public int $period_days = 7;

    public bool $requires_acknowledge = false;

    public ?int $acknowledge_user_id = null;

    public ?int $approver_user_id = null;

    /** @var array<int, array{item_id:?int, equipment_id:?int, code:string, item_name:string, qty:int}> */
    public array $items = [];

    /** @var array ຮູບ ຕໍ່ ລາຍການ (index-aligned ກັບ; ບໍ່ ບັງຄັບ) — ກ້ອງ/ແກເລີຣີ */
    public array $itemPhotos = [];

    public string $itemSearch = '';

    public string $equipmentSearch = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('borrow.create'), 403);
        $this->borrow_date = Carbon::today()->toDateString();
    }

    public function updatedBorrowType(): void
    {
        $this->items = [];
        $this->itemPhotos = [];
        $this->itemSearch = '';
        $this->equipmentSearch = '';
    }

    public function addInventoryItem(int $id): void
    {
        $inv = InventoryItem::find($id);
        if (! $inv) {
            return;
        }
        foreach ($this->items as $i => $it) {
            if (($it['item_id'] ?? null) === $inv->id) {
                $this->items[$i]['qty']++;

                return;
            }
        }
        $this->items[] = ['item_id' => $inv->id, 'equipment_id' => null, 'code' => $inv->slug, 'item_name' => $inv->name, 'qty' => 1];
        $this->itemPhotos[] = null;
        $this->itemSearch = '';
    }

    /** ທາງເລືອກ 2: ເລືອກ ເຄື່ອງ ຈາກ ທະບຽນ Equipment (ດຶງ ຂໍ້ມູນ ໄປ ໃຊ້; workflow ຢືມ ຄື ເກົ່າ). */
    public function addEquipmentItem(int $id): void
    {
        $eq = Equipment::find($id);
        if (! $eq) {
            return;
        }
        foreach ($this->items as $i => $it) {
            if (($it['equipment_id'] ?? null) === $eq->id) {
                $this->items[$i]['qty']++;

                return;
            }
        }
        $this->items[] = ['item_id' => null, 'equipment_id' => $eq->id, 'code' => $eq->asset_code, 'item_name' => $eq->name, 'qty' => 1];
        $this->itemPhotos[] = null;
        $this->equipmentSearch = '';
    }

    public function addFreeItem(): void
    {
        $this->items[] = ['item_id' => null, 'equipment_id' => null, 'code' => '', 'item_name' => '', 'qty' => 1];
        $this->itemPhotos[] = null;
    }

    public function removeItem(int $i): void
    {
        unset($this->items[$i], $this->itemPhotos[$i]);
        $this->items = array_values($this->items);
        $this->itemPhotos = array_values($this->itemPhotos);
    }

    public function save(bool $submit = false): void
    {
        abort_unless(auth()->user()->can('borrow.create'), 403);

        $rules = [
            'purpose' => ['required', 'string', 'max:256'],
            'borrow_date' => ['required', 'date'],
            'period_days' => ['required', 'integer', 'min:1', 'max:365'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:256'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'itemPhotos.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],   // ຮູບ ຕໍ່ ລາຍການ (ບໍ່ ບັງຄັບ)
        ];
        if ($this->borrow_type === 'others') {
            $rules['others_detail'] = ['required', 'string', 'max:500'];
        }
        if ($submit) {
            $rules['approver_user_id'] = ['required', 'exists:users,id'];
            if ($this->requires_acknowledge) {
                $rules['acknowledge_user_id'] = ['required', 'exists:users,id'];
            }
        }
        $this->validate($rules, [], ['purpose' => 'ຈຸດປະສົງ', 'approver_user_id' => 'Approver']);

        $approver = $this->approver_user_id ? User::find($this->approver_user_id) : null;
        $ack = $this->acknowledge_user_id ? User::find($this->acknowledge_user_id) : null;

        $record = app(BorrowService::class)->createDraft([
            'borrow_type' => $this->borrow_type,
            'purpose' => $this->purpose,
            'remark' => $this->remark ?: null,
            'others_detail' => $this->borrow_type === 'others' ? $this->others_detail : null,
            'borrow_date' => $this->borrow_date,
            'period_days' => $this->period_days,
            'requires_acknowledge' => $this->requires_acknowledge,
            'acknowledge_email' => $ack?->email,
            'acknowledge_name' => $ack?->display_name ?? $ack?->email,
            'approver_email' => $approver?->email,
            'approver_name' => $approver?->display_name ?? $approver?->email,
            'items' => $this->items,
        ], auth()->user());

        // ຮູບ ຕໍ່ ລາຍການ (ບໍ່ ບັງຄັບ) → borrow_item_photos kind='request'
        $created = $record->items()->orderBy('sort_order')->get()->values();
        foreach ($this->itemPhotos as $i => $photo) {
            if ($photo && isset($created[$i])) {
                $path = $photo->store('borrow/'.$record->id, 'public');
                $created[$i]->photos()->create(['kind' => 'request', 'path' => $path, 'sort_order' => 1]);
            }
        }

        if ($submit) {
            app(BorrowService::class)->transition($record, 'submit', auth()->user());
        }

        session()->flash('ok', $submit ? "ສົ່ງຄຳຂໍ {$record->request_number} ແລ້ວ" : "ບັນທຶກ draft {$record->request_number} ແລ້ວ");
        $this->redirectRoute('borrow.show', $record, navigate: true);
    }

    public function render(): View
    {
        $results = $this->borrow_type === 'new_inventory' && strlen($this->itemSearch) >= 2
            ? InventoryItem::where('is_active', true)
                ->where(fn ($q) => $q->where('name', 'like', "%{$this->itemSearch}%")->orWhere('slug', 'like', "%{$this->itemSearch}%"))
                ->limit(8)->get()
            : collect();

        $eqResults = $this->borrow_type === 'tools_equipment' && strlen($this->equipmentSearch) >= 2
            ? Equipment::where(fn ($q) => $q->where('name', 'like', "%{$this->equipmentSearch}%")
                ->orWhere('asset_code', 'like', "%{$this->equipmentSearch}%")
                ->orWhere('fixed_asset_no', 'like', "%{$this->equipmentSearch}%"))
                ->orderBy('asset_code')->limit(8)->get()
            : collect();

        return view('livewire.borrow.create', [
            'invResults' => $results,
            'eqResults' => $eqResults,
            'users' => User::orderBy('display_name')->get(['id', 'display_name', 'email']),
            'returnDate' => Carbon::parse($this->borrow_date)->addDays(max(1, $this->period_days))->toDateString(),
        ]);
    }
}
