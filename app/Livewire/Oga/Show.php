<?php

namespace App\Livewire\Oga;

use App\Models\OutwardsGoodsAdvice;
use App\Services\OgaService;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Show extends Component
{
    use WithFileUploads;

    public OutwardsGoodsAdvice $record;

    // dispatch
    public bool $showDispatch = false;

    public string $driverName = '';

    public string $truckPlate = '';

    // delivery (+ 3 photos)
    public bool $showDelivery = false;

    public $photoDelivered;

    public $photoHandover;

    public $photoReceipt;

    public bool $showReturn = false;

    public string $returnReason = '';

    public bool $showCancel = false;

    public string $cancelReason = '';

    public bool $showDelete = false;

    public string $deleteReason = '';

    // edit draft items
    public bool $showItems = false;

    public array $oi = [];

    public function mount(OutwardsGoodsAdvice $record): void
    {
        abort_unless(auth()->user()->can('oga.view'), 403);
        $u = auth()->user();
        // suppliers may only open OGAs dispatched to their own supplier
        if ($u->hasRole('supplier') && ! $u->is_super_admin && ! $u->hasAnyRole(['admin', 'warehouse_staff'])) {
            abort_unless($u->supplier_id && $record->supplier_id === $u->supplier_id, 403);
        }
        $this->record = $record;
    }

    protected function act(string $action, array $opts = []): bool
    {
        // OGA is warehouse-only: every transition requires edit rights.
        abort_unless($this->canEdit(), 403);

        try {
            app(OgaService::class)->transition($this->record, $action, auth()->user(), $opts);
            $this->record->refresh();
            session()->flash('ok', "✓ {$action} ສຳເລັດ");

            return true;
        } catch (ValidationException $e) {
            $this->addError('action', $e->validator->errors()->first());

            return false;
        }
    }

    protected function canEdit(): bool
    {
        $u = auth()->user();

        return $u->is_super_admin || $u->can('oga.edit');
    }

    /** Edit line items — draft only. */
    public function openItems(): void
    {
        abort_unless($this->canEdit() && $this->record->status === 'draft', 403);
        $this->oi = $this->record->items->map(fn ($it) => [
            'description' => $it->description, 'unit' => $it->unit, 'qty' => $it->qty,
            'unit_weight_kg' => $it->unit_weight_kg,
        ])->all();
        if (empty($this->oi)) {
            $this->oi = [['description' => '', 'unit' => '', 'qty' => 1, 'unit_weight_kg' => '']];
        }
        $this->showItems = true;
    }

    public function addOiItem(): void
    {
        $this->oi[] = ['description' => '', 'unit' => '', 'qty' => 1, 'unit_weight_kg' => ''];
    }

    public function removeOiItem(int $i): void
    {
        unset($this->oi[$i]);
        $this->oi = array_values($this->oi);
    }

    public function saveItems(): void
    {
        abort_unless($this->canEdit() && $this->record->status === 'draft', 403);
        app(OgaService::class)->replaceItems($this->record, $this->oi, auth()->user());
        $this->record->refresh()->load('items');
        $this->reset(['showItems', 'oi']);
        session()->flash('ok', '✓ ບັນທຶກ ລາຍການ');
    }

    public function openDispatch(): void
    {
        $this->driverName = $this->record->driver_name ?? '';
        $this->truckPlate = $this->record->truck_plate_number ?? '';
        $this->resetErrorBag();
        $this->showDispatch = true;
    }

    public function confirmDispatch(): void
    {
        if ($this->act('confirmDispatch', ['driver_name' => $this->driverName ?: null, 'truck_plate_number' => $this->truckPlate ?: null])) {
            $this->reset(['showDispatch', 'driverName', 'truckPlate']);
        }
    }

    public function openDelivery(): void
    {
        $this->reset(['photoDelivered', 'photoHandover', 'photoReceipt']);
        $this->resetErrorBag();
        $this->showDelivery = true;
    }

    public function confirmDelivery(): void
    {
        abort_unless($this->canEdit(), 403);              // ກວດ ສິດ ກ່ອນ ຂຽນ ຮູບ (warehouse ເທົ່ານັ້ນ)
        if ($this->record->status !== 'dispatched') {
            return;                                       // ຜິດ state — ບໍ່ ຂຽນ ຫຍັງ
        }
        $this->validate([
            'photoDelivered' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'photoHandover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'photoReceipt' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        foreach (['delivered' => $this->photoDelivered, 'handover' => $this->photoHandover, 'receipt' => $this->photoReceipt] as $kind => $file) {
            if ($file) {
                $path = $file->store("oga/{$this->record->id}", 'public');
                $this->record->photos()->create(['kind' => $kind, 'path' => $path, 'sort_order' => 0]);
            }
        }

        if ($this->act('confirmDelivery')) {
            $this->reset(['showDelivery', 'photoDelivered', 'photoHandover', 'photoReceipt']);
        }
    }

    public function returnRejected(): void
    {
        if ($this->act('returnRejected', ['reason' => $this->returnReason])) {
            $this->reset(['showReturn', 'returnReason']);
        }
    }

    public function cancel(): void
    {
        if ($this->act('cancel', ['reason' => $this->cancelReason])) {
            $this->showCancel = false;   // ປິດ modal ສະເພາະ ຕອນ ສຳເລັດ
        }
    }

    protected function canDelete(): bool
    {
        $u = auth()->user();

        return ($u->is_super_admin || $u->can('oga.delete'))
            && in_array($this->record->status, ['draft', 'cancelled', 'delivered', 'returned'], true);
    }

    public function openDelete(): void
    {
        abort_unless($this->canDelete(), 403);
        $this->reset(['deleteReason']);
        $this->resetErrorBag();
        $this->showDelete = true;
    }

    public function deleteRecord()
    {
        abort_unless($this->canDelete(), 403);
        $this->validate(['deleteReason' => ['required', 'string', 'max:500']]);
        $u = auth()->user();
        $this->record->forceFill(['deleted_reason' => $this->deleteReason, 'deleted_by' => $u->id])->save();
        $this->record->history()->create([
            'action' => 'delete', 'status' => $this->record->status, 'user_id' => $u->id,
            'user_name' => $u->display_name ?? $u->email, 'comment' => $this->deleteReason, 'created_at' => now(),
        ]);
        $this->record->delete();
        session()->flash('ok', '✓ ລຶບ (ຍ້າຍໄປ deleted log)');

        return $this->redirect(route('oga'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.oga.show', [
            'record' => $this->record->load(['items', 'photos', 'history', 'supplier']),
            'editable' => $this->canEdit(),
            'deletable' => $this->canDelete(),
        ]);
    }
}
