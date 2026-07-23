<?php

namespace App\Livewire\Request;

use App\Models\Material;
use App\Models\Supplier;
use App\Models\User;
use App\Services\RequestService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    public string $purpose = '';

    public string $request_type = '';

    public string $wo_e_form = '';

    public string $wo_type = 'single';

    public string $rooms = '';

    public string $functions = '';

    public string $remark = '';

    public string $currency = 'THB';

    public ?int $assigned_supplier_id = null;

    public ?int $approver_user_id = null;

    /** @var array<int, array{material_id:?int, material_nbr:string, category:string, description:string, unit:string, quantity:int, unit_price:string}> */
    public array $items = [];

    public string $itemSearch = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('request.create'), 403);
        $this->items = [];
    }

    public function updatedAssignedSupplierId(): void
    {
        $this->itemSearch = '';
    }

    public function addMaterial(int $id): void
    {
        $m = Material::find($id);
        if (! $m) {
            return;
        }
        $this->items[] = [
            'material_id' => $m->id,
            'material_nbr' => $m->material_nbr ?? '',
            'category' => $m->category,
            'description' => $m->description,
            'unit' => $m->unit ?? '',
            'quantity' => 1,
            'unit_price' => $m->unit_price !== null ? (string) $m->unit_price : '0',
        ];
        $this->itemSearch = '';
    }

    public function addFreeItem(): void
    {
        $this->items[] = ['material_id' => null, 'material_nbr' => '', 'category' => '', 'description' => '', 'unit' => '', 'quantity' => 1, 'unit_price' => '0'];
    }

    public function removeItem(int $i): void
    {
        unset($this->items[$i]);
        $this->items = array_values($this->items);
    }

    /** ສະຫຼັບ item ໄປໃຊ້ supplier ອື່ນ (ຈາກ price comparison). */
    public function useOffer(int $i, int $materialId): void
    {
        $m = Material::find($materialId);
        if (! $m || ! isset($this->items[$i])) {
            return;
        }
        $this->items[$i]['material_id'] = $m->id;
        $this->items[$i]['material_nbr'] = $m->material_nbr ?? '';
        $this->items[$i]['description'] = $m->description;
        $this->items[$i]['unit'] = $m->unit ?? '';
        $this->items[$i]['unit_price'] = $m->unit_price !== null ? (string) $m->unit_price : '0';
    }

    public function save(bool $submit = false): void
    {
        abort_unless(auth()->user()->can('request.create'), 403);

        $fields = app(RequestService::class)->fieldConfig();
        $rules = [
            'purpose' => ['required', 'string', 'max:1000'],
            'currency' => ['required', 'in:LAK,THB,USD'],
            'assigned_supplier_id' => ['nullable', 'exists:suppliers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
        // approver ບັງຄັບຕອນສົ່ງ ສະເພາະ ຖ້າ admin ເປີດ field ໄວ້
        if ($submit && $fields['approver']) {
            $rules['approver_user_id'] = ['required', 'exists:users,id'];
        }
        $this->validate($rules, [], ['purpose' => 'ຈຸດປະສົງ', 'approver_user_id' => 'Approver']);

        $record = app(RequestService::class)->createDraft([
            'purpose' => $this->purpose,
            'request_type' => $this->request_type ?: null,
            'wo_e_form' => $this->wo_e_form ?: null,
            'wo_type' => $this->wo_type ?: null,
            'rooms' => $this->rooms ?: null,
            'functions' => $this->functions ?: null,
            'remark' => $this->remark ?: null,
            'currency' => $this->currency,
            'assigned_supplier_id' => $this->assigned_supplier_id ?: null,
            'approver_user_id' => $this->approver_user_id ?: null,
            'items' => $this->items,
        ], auth()->user());

        if ($submit) {
            app(RequestService::class)->transition($record, 'submit', auth()->user());
        }

        session()->flash('ok', $submit ? "ສົ່ງໃບເບີກ {$record->request_number} ແລ້ວ" : "ບັນທຶກ draft {$record->request_number} ແລ້ວ");
        $this->redirectRoute('request.show', $record, navigate: true);
    }

    public function render(): View
    {
        $results = strlen($this->itemSearch) >= 2
            ? Material::where('is_active', true)
                ->when($this->assigned_supplier_id, fn ($q) => $q->where('supplier_id', $this->assigned_supplier_id))
                ->where(fn ($q) => $q->where('description', 'like', "%{$this->itemSearch}%")->orWhere('material_nbr', 'like', "%{$this->itemSearch}%"))
                ->limit(8)->get()
            : collect();

        $total = collect($this->items)->sum(fn ($it) => (float) ($it['unit_price'] ?? 0) * (int) ($it['quantity'] ?? 0));

        // price comparison ຕໍ່ item ທີ່ມາຈາກ catalog (supplier ອື່ນ ຂາຍສິນຄ້າດຽວກັນ)
        $svc = app(RequestService::class);
        $comparisons = [];
        foreach ($this->items as $i => $it) {
            if (! empty($it['material_id']) && ($m = Material::find($it['material_id']))) {
                $offers = $svc->comparePrices($m);
                if ($offers) {
                    $comparisons[$i] = $offers;
                }
            }
        }

        return view('livewire.request.create', [
            'matResults' => $results,
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('display_name')->get(['id', 'display_name', 'email']),
            'liveTotal' => $total,
            'fields' => $svc->fieldConfig(),
            'comparisons' => $comparisons,
        ]);
    }
}
