<?php

namespace App\Livewire\Borrow;

use App\Models\InventoryItem;
use App\Models\User;
use App\Services\BorrowService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    public string $borrow_type = 'new_inventory';

    public string $purpose = '';

    public string $remark = '';

    public string $others_detail = '';

    public string $borrow_date;

    public int $period_days = 7;

    public bool $requires_acknowledge = false;

    public ?int $acknowledge_user_id = null;

    public ?int $approver_user_id = null;

    /** @var array<int, array{item_id:?int, item_name:string, qty:int}> */
    public array $items = [];

    public string $itemSearch = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('borrow.create'), 403);
        $this->borrow_date = Carbon::today()->toDateString();
    }

    public function updatedBorrowType(): void
    {
        $this->items = [];
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
        $this->items[] = ['item_id' => $inv->id, 'item_name' => $inv->name, 'qty' => 1];
        $this->itemSearch = '';
    }

    public function addFreeItem(): void
    {
        $this->items[] = ['item_id' => null, 'item_name' => '', 'qty' => 1];
    }

    public function removeItem(int $i): void
    {
        unset($this->items[$i]);
        $this->items = array_values($this->items);
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

        return view('livewire.borrow.create', [
            'invResults' => $results,
            'users' => User::orderBy('display_name')->get(['id', 'display_name', 'email']),
            'returnDate' => Carbon::parse($this->borrow_date)->addDays(max(1, $this->period_days))->toDateString(),
        ]);
    }
}
