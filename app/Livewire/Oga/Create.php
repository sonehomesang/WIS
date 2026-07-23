<?php

namespace App\Livewire\Oga;

use App\Models\DiscrepancyAdvice;
use App\Models\Supplier;
use App\Services\OgaService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Create extends Component
{
    use WithFileUploads;

    public string $date;

    public string $po_number = '';

    public string $ship_via = 'road';

    public ?int $source_da_id = null;

    public ?int $supplier_id = null;

    public string $dispatch_to_contact_person = '';

    public string $dispatch_to_address = '';

    public string $dispatch_to_phone = '';

    public string $goods_consigned = '';

    public string $dimension = '';

    public ?string $gross_weight_kg = null;

    public string $reason_of_despatch = '';

    public string $driver_name = '';

    public string $truck_plate_number = '';

    public string $comments = '';

    /** @var array<int, array{description:string, unit:string, qty:int, unit_weight_kg:string}> */
    public array $items = [];

    public $photoLoaded;

    public $photoSealed;

    public $photoPaperPli;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('oga.create'), 403);
        $this->date = Carbon::today()->toDateString();
        $this->items = [$this->blank()];
        // pre-fill ຈາກ DA ຖ້າ link ມາ
        if (request('da')) {
            $da = DiscrepancyAdvice::find((int) request('da'));
            if ($da && $da->next_step === 'oga') {
                $this->source_da_id = $da->id;
                $this->supplier_id = $da->supplier_id;
                $this->po_number = $da->po_number ?? '';
            }
        }
    }

    protected function blank(): array
    {
        return ['description' => '', 'unit' => '', 'qty' => 1, 'unit_weight_kg' => ''];
    }

    public function addItem(): void
    {
        $this->items[] = $this->blank();
    }

    public function removeItem(int $i): void
    {
        unset($this->items[$i]);
        $this->items = array_values($this->items);
        if (empty($this->items)) {
            $this->items = [$this->blank()];
        }
    }

    public function save(bool $dispatch = false): void
    {
        abort_unless(auth()->user()->can('oga.create'), 403);

        $this->validate([
            'date' => ['required', 'date'],
            'po_number' => ['nullable', 'string', 'max:128'],
            'ship_via' => ['required', 'in:road,air'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'source_da_id' => ['nullable', 'exists:discrepancy_advices,id'],
            'goods_consigned' => ['required', 'string', 'max:500'],
            'gross_weight_kg' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'photoLoaded' => ['nullable', 'image', 'max:5120'],
            'photoSealed' => ['nullable', 'image', 'max:5120'],
            'photoPaperPli' => ['nullable', 'image', 'max:5120'],
        ], [], ['goods_consigned' => 'ສິນຄ້າ', 'ship_via' => 'ວິທີສົ່ງ']);

        $record = app(OgaService::class)->createDraft([
            'date' => $this->date,
            'po_number' => $this->po_number ?: null,
            'ship_via' => $this->ship_via,
            'source_da_id' => $this->source_da_id ?: null,
            'supplier_id' => $this->supplier_id ?: null,
            'dispatch_to_contact_person' => $this->dispatch_to_contact_person ?: null,
            'dispatch_to_address' => $this->dispatch_to_address ?: null,
            'dispatch_to_phone' => $this->dispatch_to_phone ?: null,
            'goods_consigned' => $this->goods_consigned,
            'dimension' => $this->dimension ?: null,
            'gross_weight_kg' => $this->gross_weight_kg ?: null,
            'reason_of_despatch' => $this->reason_of_despatch ?: null,
            'driver_name' => $this->driver_name ?: null,
            'truck_plate_number' => $this->truck_plate_number ?: null,
            'comments' => $this->comments ?: null,
            'items' => $this->items,
        ], auth()->user());

        foreach (['loaded' => $this->photoLoaded, 'sealed' => $this->photoSealed, 'paper_pli' => $this->photoPaperPli] as $kind => $file) {
            if ($file) {
                $path = $file->store("oga/{$record->id}", 'public');
                $record->photos()->create(['kind' => $kind, 'path' => $path, 'sort_order' => 0]);
            }
        }

        if ($dispatch) {
            app(OgaService::class)->transition($record, 'confirmDispatch', auth()->user(), [
                'driver_name' => $this->driver_name ?: null,
                'truck_plate_number' => $this->truck_plate_number ?: null,
            ]);
        }

        session()->flash('ok', $dispatch ? "Dispatch {$record->oga_number} ແລ້ວ" : "ບັນທຶກ draft {$record->oga_number} ແລ້ວ");
        $this->redirectRoute('oga.show', $record, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.oga.create', [
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'sourceDas' => DiscrepancyAdvice::where('status', 'resolved')->where('next_step', 'oga')->orderByDesc('id')->get(['id', 'da_number', 'supplier_name']),
        ]);
    }
}
