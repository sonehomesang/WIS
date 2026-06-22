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

    public function mount(OutwardsGoodsAdvice $record): void
    {
        abort_unless(auth()->user()->can('oga.view'), 403);
        $this->record = $record;
    }

    protected function act(string $action, array $opts = []): bool
    {
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
        $this->validate([
            'photoDelivered' => ['nullable', 'image', 'max:5120'],
            'photoHandover' => ['nullable', 'image', 'max:5120'],
            'photoReceipt' => ['nullable', 'image', 'max:5120'],
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
        $this->act('cancel', ['reason' => $this->cancelReason]);
        $this->showCancel = false;
    }

    protected function canDelete(): bool
    {
        return $this->canEdit() && in_array($this->record->status, ['draft', 'cancelled', 'delivered', 'returned'], true);
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
