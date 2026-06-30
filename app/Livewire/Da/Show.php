<?php

namespace App\Livewire\Da;

use App\Models\DiscrepancyAdvice;
use App\Models\OutwardsGoodsAdvice;
use App\Services\DiscrepancyService;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public DiscrepancyAdvice $record;

    public bool $showCancel = false;

    public string $cancelReason = '';

    // purchasing decide (section B)
    public bool $showDecide = false;

    public array $decisions = [];

    public string $purchasingNote = '';

    public string $transportAccount = '';

    public string $transportMode = '';

    public string $carrierName = '';

    public string $carrierPhone = '';

    // approve (section C)
    public bool $showApprove = false;

    public string $resolution = '';

    public string $approvedTitle = '';

    public string $nextStep = 'finished';

    public bool $showReject = false;

    public string $rejectReason = '';

    public bool $showDelete = false;

    public string $deleteReason = '';

    // edit draft items (section A)
    public bool $showItems = false;

    public array $di = [];

    public const DECISIONS = ['part_correct', 'end_user_accept', 'supplier_resend', 'return_supplier', 'do_not_return', 'supplier_refuses', 'po_amended', 'other'];

    public function mount(DiscrepancyAdvice $record): void
    {
        $u = auth()->user();
        abort_unless($u->can('da.view'), 403);
        // ownership scope — mirrors Da\Index (non-privileged see only what they raised)
        abort_unless(
            $u->is_super_admin || $u->can('da.edit') || $u->can('da.activate') || $record->raised_by === $u->id,
            403
        );
        $this->record = $record;
    }

    /** Edit line items — draft only, warehouse (canEdit). */
    public function openItems(): void
    {
        abort_unless($this->canEdit() && $this->record->status === 'draft', 403);
        $this->di = $this->record->items->map(fn ($it) => [
            'stock_code' => $it->stock_code, 'description' => $it->description,
            'qty_ordered' => $it->qty_ordered, 'qty_delivered' => $it->qty_delivered,
            'qty_received' => $it->qty_received, 'comments' => $it->comments,
        ])->all();
        if (empty($this->di)) {
            $this->di = [['stock_code' => '', 'description' => '', 'qty_ordered' => 0, 'qty_delivered' => 0, 'qty_received' => 0, 'comments' => '']];
        }
        $this->showItems = true;
    }

    public function addDiItem(): void
    {
        $this->di[] = ['stock_code' => '', 'description' => '', 'qty_ordered' => 0, 'qty_delivered' => 0, 'qty_received' => 0, 'comments' => ''];
    }

    public function removeDiItem(int $i): void
    {
        unset($this->di[$i]);
        $this->di = array_values($this->di);
    }

    public function saveItems(): void
    {
        abort_unless($this->canEdit() && $this->record->status === 'draft', 403);
        app(DiscrepancyService::class)->replaceItems($this->record, $this->di, auth()->user());
        $this->record->refresh()->load('items');
        $this->reset(['showItems', 'di']);
        session()->flash('ok', '✓ ບັນທຶກ ລາຍການ');
    }

    /** Server-side authorization: warehouse runs submit/start/cancel; purchasing/leader run the decisions. */
    protected function authorizeAction(string $action): void
    {
        $ok = match ($action) {
            'submit', 'purchasingStart', 'cancel' => $this->canEdit(),
            'purchasingDecide', 'approve', 'reject' => $this->canAct(),
            default => false,
        };
        abort_unless($ok, 403);
    }

    protected function act(string $action, array $opts = []): bool
    {
        $this->authorizeAction($action);

        try {
            app(DiscrepancyService::class)->transition($this->record, $action, auth()->user(), $opts);
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

        return $u->is_super_admin || $u->can('da.edit');
    }

    protected function canAct(): bool
    {
        $u = auth()->user();

        return $this->canEdit() || $u->can('da.activate');
    }

    public function submit(): void
    {
        $this->act('submit');
    }

    public function purchasingStart(): void
    {
        $this->act('purchasingStart');
    }

    public function cancel(): void
    {
        $this->act('cancel', ['reason' => $this->cancelReason]);
        $this->showCancel = false;
    }

    public function purchasingDecide(): void
    {
        if ($this->act('purchasingDecide', [
            'decisions' => array_values($this->decisions),
            'note' => $this->purchasingNote ?: null,
            'transport_account' => $this->transportAccount ?: null,
            'transport_mode' => $this->transportMode ?: null,
            'carrier_name' => $this->carrierName ?: null,
            'carrier_phone' => $this->carrierPhone ?: null,
        ])) {
            $this->reset(['showDecide', 'decisions', 'purchasingNote', 'transportAccount', 'transportMode', 'carrierName', 'carrierPhone']);
        }
    }

    public function approve(): void
    {
        if ($this->act('approve', ['resolution' => $this->resolution ?: null, 'title' => $this->approvedTitle ?: null, 'next_step' => $this->nextStep])) {
            $this->reset(['showApprove', 'resolution', 'approvedTitle', 'nextStep']);
            $this->nextStep = 'finished';
        }
    }

    public function reject(): void
    {
        if ($this->act('reject', ['reason' => $this->rejectReason])) {
            $this->reset(['showReject', 'rejectReason']);
        }
    }

    protected function canDelete(): bool
    {
        return $this->canEdit() && in_array($this->record->status, ['draft', 'cancelled', 'resolved'], true);
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

        return $this->redirect(route('da'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.da.show', [
            'record' => $this->record->load(['items', 'photos', 'history', 'supplier']),
            'editable' => $this->canEdit(),
            'canAct' => $this->canAct(),
            'deletable' => $this->canDelete(),
            'decisionOptions' => self::DECISIONS,
            'linkedOgas' => OutwardsGoodsAdvice::where('source_da_id', $this->record->id)->orderByDesc('id')->get(['id', 'oga_number', 'status']),
        ]);
    }
}
