<?php

namespace App\Livewire\Request;

use App\Models\MaterialRequest;
use App\Services\RequestService;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public MaterialRequest $record;

    public bool $showCancel = false;

    public string $cancelReason = '';

    public bool $showReject = false;

    public string $rejectReason = '';

    public bool $showDispatch = false;

    public string $deliveryMethod = 'supplier_delivery';

    public string $plannedDeliveryDate = '';

    public bool $showReceive = false;

    public bool $rcInvoice = false;

    public bool $rcDeliveryNote = false;

    public bool $rcSpecMatch = false;

    /** Per-item received qty for the receipt modal: [item_id => qty]. */
    public array $rcQty = [];

    public bool $showClose = false;

    public string $invoiceNumber = '';

    public string $sapReference = '';

    /** SAP PR/FR status ເລືອກຕອນ close (ວ່າງ = ບໍ່ລະບຸ). */
    public string $sapStatus = '';

    public bool $showDelete = false;

    public string $deleteReason = '';

    public function mount(MaterialRequest $record): void
    {
        abort_unless(auth()->user()->can('request.view'), 403);
        abort_unless($this->canSee($record), 403);
        $this->record = $record;
    }

    protected function isStaff(): bool
    {
        $u = auth()->user();

        return $u->is_super_admin || $u->hasAnyRole(['admin', 'warehouse_staff']);
    }

    protected function isAssignedSupplier(): bool
    {
        $u = auth()->user();

        return $u->hasRole('supplier') && $u->supplier_id && $this->record->assigned_supplier_id === $u->supplier_id;
    }

    protected function canSee(MaterialRequest $r): bool
    {
        $u = auth()->user();

        $deptScoped = $u->transactionScope() === 'department' && $u->department_id && $r->requester_dept_id === $u->department_id;

        return $this->isStaff() || $r->requester_user_id === $u->id || $r->approver_user_id === $u->id || $deptScoped
            || ($u->hasRole('supplier') && $u->supplier_id && $r->assigned_supplier_id === $u->supplier_id);
    }

    /** Server-side authorization per transition. */
    protected function authorizeAction(string $action): void
    {
        abort_unless($this->canSee($this->record), 403);
        if ($this->isStaff()) {
            return;
        }
        $ok = match ($action) {
            'submit', 'cancel', 'confirmReceipt' => $this->isRequester(),
            'approve', 'reject' => $this->canApprove(),
            'validate', 'dispatch' => $this->isAssignedSupplier(),
            default => false,   // close = staff only
        };
        abort_unless($ok, 403);
    }

    protected function act(string $action, array $opts = []): bool
    {
        $this->authorizeAction($action);

        try {
            app(RequestService::class)->transition($this->record, $action, auth()->user(), $opts);
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

        return $u->is_super_admin || $u->can('request.edit');
    }

    protected function canApprove(): bool
    {
        $u = auth()->user();

        return $this->canEdit() || $u->can('request.activate') || $this->record->approver_user_id === $u->id;
    }

    protected function isRequester(): bool
    {
        return auth()->id() === $this->record->requester_user_id;
    }

    public function submit(): void
    {
        $this->act('submit');
    }

    public function approve(): void
    {
        $this->act('approve');
    }

    public function validateRequest(): void
    {
        $this->act('validate');
    }

    public function cancel(): void
    {
        $this->act('cancel', ['reason' => $this->cancelReason]);
        $this->showCancel = false;
    }

    public function reject(): void
    {
        if ($this->act('reject', ['reason' => $this->rejectReason])) {
            $this->reset(['showReject', 'rejectReason']);
        }
    }

    public function openDispatch(): void
    {
        $this->reset(['deliveryMethod', 'plannedDeliveryDate']);
        $this->deliveryMethod = 'supplier_delivery';
        $this->resetErrorBag();
        $this->showDispatch = true;
    }

    public function doDispatch(): void
    {
        if ($this->act('dispatch', ['delivery_method' => $this->deliveryMethod, 'planned_delivery_date' => $this->plannedDeliveryDate ?: null])) {
            $this->reset(['showDispatch', 'deliveryMethod', 'plannedDeliveryDate']);
        }
    }

    public function openReceive(): void
    {
        abort_unless($this->canSee($this->record), 403);
        // prefill each item's input with the remaining (ordered − already received)
        $this->rcQty = $this->record->items
            ->mapWithKeys(fn ($it) => [$it->id => max(0, $it->quantity - $it->received_qty)])->all();
        $this->rcInvoice = $this->rcDeliveryNote = $this->rcSpecMatch = false;
        $this->showReceive = true;
    }

    public function receiveAll(): void
    {
        $this->rcQty = $this->record->items->mapWithKeys(fn ($it) => [$it->id => $it->quantity])->all();
    }

    public function confirmReceipt(): void
    {
        // received qty = already-received + entered-this-pass (absolute, clamped in service)
        $received = [];
        foreach ($this->record->items as $it) {
            $received[$it->id] = (int) $it->received_qty + (int) ($this->rcQty[$it->id] ?? 0);
        }
        if ($this->act('confirmReceipt', [
            'received' => $received,
            'invoice_received' => $this->rcInvoice,
            'delivery_note_received' => $this->rcDeliveryNote,
            'spec_match' => $this->rcSpecMatch,
        ])) {
            $this->reset(['showReceive', 'rcInvoice', 'rcDeliveryNote', 'rcSpecMatch', 'rcQty']);
        }
    }

    public function close(): void
    {
        $this->validate([
            'invoiceNumber' => ['required', 'string', 'max:128'],
            'sapReference' => ['required', 'string', 'max:128'],
            // '' = ບໍ່ລະບຸ (ທາງເລືອກ); ຫຼື ໜຶ່ງໃນ key ທີ່ອະນຸຍາດ
            'sapStatus' => [\Illuminate\Validation\Rule::in(array_merge([''], array_keys(MaterialRequest::sapStatuses())))],
        ]);
        if ($this->act('close', [
            'invoice_number' => $this->invoiceNumber,
            'sap_reference' => $this->sapReference,
            'sap_status' => $this->sapStatus ?: null,
        ])) {
            $this->reset(['showClose', 'invoiceNumber', 'sapReference', 'sapStatus']);
        }
    }

    // ── soft delete ──
    protected function canDelete(): bool
    {
        return $this->canEdit() && in_array($this->record->status, ['draft', 'cancelled', 'rejected', 'completed'], true);
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

        return $this->redirect(route('request'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.request.show', [
            'record' => $this->record->load(['items.photos', 'history', 'supplier', 'unit', 'department', 'requester']),
            'editable' => $this->canEdit(),
            'canApprove' => $this->canApprove(),
            'isRequester' => $this->isRequester(),
            'deletable' => $this->canDelete(),
        ]);
    }
}
