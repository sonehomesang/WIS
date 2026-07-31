<?php

namespace App\Livewire\Da;

use App\Models\Supplier;
use App\Services\DiscrepancyService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Create extends Component
{
    use WithFileUploads;

    public const TYPES = ['incorrect_supplied', 'oversupplied', 'undersupplied', 'damaged', 'no_paperwork', 'other'];

    public string $date;

    public string $po_number = '';

    public ?string $po_date = null;

    public ?int $supplier_id = null;

    public string $purchasing_officer_name = '';

    public string $vendor_packing_list = '';

    public string $vendor_invoice_number = '';

    /** @var string[] */
    public array $discrepancy_types = [];

    public string $comments = '';

    public string $warehouse_recommendation_kind = '';

    public string $warehouse_recommendation_text = '';

    /** @var array<int, array{stock_code:string, description:string, qty_ordered:int, qty_delivered:int, qty_received:int, comments:string}> */
    public array $items = [];

    /** photo uploads keyed by kind */
    public $photoOverview;

    public $photoDefect;

    public $photoComparison;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('da.create'), 403);
        $this->date = Carbon::today()->toDateString();
        $this->items = [$this->blank()];
    }

    protected function blank(): array
    {
        return ['stock_code' => '', 'description' => '', 'qty_ordered' => 0, 'qty_delivered' => 0, 'qty_received' => 0, 'comments' => ''];
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

    public function save(bool $submit = false): void
    {
        abort_unless(auth()->user()->can('da.create'), 403);

        $this->validate([
            'date' => ['required', 'date'],
            'po_number' => ['required', 'string', 'max:128'],
            'po_date' => ['nullable', 'date'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'discrepancy_types' => ['required', 'array', 'min:1'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'photoOverview' => ['nullable', 'image', 'max:5120'],
            'photoDefect' => ['nullable', 'image', 'max:5120'],
            'photoComparison' => ['nullable', 'image', 'max:5120'],
        ], [], ['po_number' => 'PO number', 'discrepancy_types' => 'ປະເພດຄວາມຜິດ']);

        $record = app(DiscrepancyService::class)->createDraft([
            'date' => $this->date,
            'po_number' => $this->po_number,
            'po_date' => $this->po_date ?: null,
            'supplier_id' => $this->supplier_id ?: null,
            'purchasing_officer_name' => $this->purchasing_officer_name ?: null,
            'vendor_packing_list' => $this->vendor_packing_list ?: null,
            'vendor_invoice_number' => $this->vendor_invoice_number ?: null,
            'discrepancy_types' => array_values($this->discrepancy_types),
            'comments' => $this->comments ?: null,
            'warehouse_recommendation_kind' => $this->warehouse_recommendation_kind ?: null,
            'warehouse_recommendation_text' => $this->warehouse_recommendation_text ?: null,
            'items' => $this->items,
        ], auth()->user());

        foreach (['overview' => $this->photoOverview, 'defect' => $this->photoDefect, 'comparison' => $this->photoComparison] as $kind => $file) {
            if ($file) {
                $path = $file->store("da/{$record->id}", 'public');
                $record->photos()->create(['kind' => $kind, 'path' => $path, 'sort_order' => 0]);
            }
        }

        if ($submit) {
            app(DiscrepancyService::class)->transition($record, 'submit', auth()->user());
        }

        session()->flash('ok', $submit ? "ສົ່ງ DA {$record->da_number} ແລ້ວ" : "ບັນທຶກ draft {$record->da_number} ແລ້ວ");
        $this->redirectRoute('da.show', $record, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.da.create', [
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'typeOptions' => self::TYPES,
        ]);
    }
}
