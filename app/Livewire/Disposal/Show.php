<?php

namespace App\Livewire\Disposal;

use App\Models\DisposalRecord;
use App\Services\DisposalService;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public DisposalRecord $record;

    // sign (committee = ຫຼາຍ ຄົນ)
    public bool $showSign = false;

    /** @var array<int, array{name:string,title:string}> */
    public array $committee = [];

    public string $signComment = '';

    public string $signTitle = '';

    // reject / cancel / dispose / delete
    public bool $showReject = false;

    public string $rejectReason = '';

    public bool $showCancel = false;

    public string $cancelReason = '';

    public bool $showDispose = false;

    public bool $updateRegisters = true;

    public bool $showDelete = false;

    public string $deleteReason = '';

    public function mount(DisposalRecord $record): void
    {
        abort_unless(auth()->user()->can('disposal.view') && static::canAccess($record), 403);
        $this->record = $record;
        $this->committee = [['name' => '', 'title' => '']];
    }

    /** ຂອບເຂດ ການ ເຫັນ ໃບ — ຄື Index::scopeFor: admin/warehouse/approver/manager ເຫັນ ໝົດ ·
     *  dept-admin ເຫັນ ພະແນກ ຕົນ · ອື່ນ ເຫັນ ສະເພາະ ໃບ ຂອງ ຕົນ. (static → ໃຊ້ ໃນ route PDF ໄດ້ ນຳ.) */
    public static function canAccess(DisposalRecord $record): bool
    {
        $u = auth()->user();
        if (! $u) {
            return false;
        }
        if ($u->is_super_admin || $u->hasAnyRole(['admin', 'warehouse_staff', 'approver', 'line_manager'])) {
            return true;
        }
        if ($u->hasRole('department_admin')) {
            return $record->department_id === $u->department_id;
        }

        return $record->prepared_by_user_id === $u->id;
    }

    /** ຜູ້ ຍົກເລີກ ໄດ້: ຜູ້ ມີ ສິດ disposal.edit ຫຼື ຜູ້ ເຮັດລິສ ເອງ ຫຼື super admin. */
    protected function canCancelRecord(): bool
    {
        $u = auth()->user();

        return $u->can('disposal.edit') || $this->record->prepared_by_user_id === $u->id || $u->is_super_admin;
    }

    protected function canSign(): bool
    {
        return auth()->user()->can('disposal.activate') && $this->record->currentStageKey() !== null;
    }

    protected function refreshRecord(): void
    {
        $this->record = $this->record->fresh(['items', 'signoffs', 'preparedBy', 'department']);
    }

    public function submit(): void
    {
        $u = auth()->user();
        abort_unless($u->can('disposal.create') && ($this->record->prepared_by_user_id === $u->id || $u->can('disposal.edit') || $u->is_super_admin), 403);
        $this->act('submit');
    }

    public function addCommittee(): void
    {
        $this->committee[] = ['name' => '', 'title' => ''];
    }

    public function removeCommittee(int $i): void
    {
        unset($this->committee[$i]);
        $this->committee = array_values($this->committee);
        if (empty($this->committee)) {
            $this->committee = [['name' => '', 'title' => '']];
        }
    }

    public function openSign(): void
    {
        abort_unless($this->canSign(), 403);
        $this->committee = [['name' => '', 'title' => '']];
        $this->signComment = '';
        $this->signTitle = '';
        $this->showSign = true;
    }

    public function confirmSign(): void
    {
        abort_unless($this->canSign(), 403);
        $opts = ['comment' => $this->signComment ?: null, 'title' => $this->signTitle ?: null];
        if ($this->record->currentStageKey() === 'committee') {
            $opts['committee'] = $this->committee;
        }
        $this->act('sign', $opts);
        $this->showSign = false;
    }

    public function openReject(): void
    {
        abort_unless($this->canSign(), 403);
        $this->rejectReason = '';
        $this->showReject = true;
    }

    public function confirmReject(): void
    {
        abort_unless($this->canSign(), 403);
        $this->act('reject', ['reason' => $this->rejectReason]);
        $this->showReject = false;
    }

    public function openCancel(): void
    {
        abort_unless($this->canCancelRecord(), 403);
        $this->cancelReason = '';
        $this->showCancel = true;
    }

    public function confirmCancel(): void
    {
        abort_unless($this->canCancelRecord(), 403);   // action re-check — button hidden ≠ safe
        $this->act('cancel', ['reason' => $this->cancelReason ?: null]);
        $this->showCancel = false;
    }

    public function openDispose(): void
    {
        abort_unless(auth()->user()->can('disposal.activate') && $this->record->status === 'approved', 403);
        $this->updateRegisters = true;
        $this->showDispose = true;
    }

    public function confirmDispose(): void
    {
        abort_unless(auth()->user()->can('disposal.activate') && $this->record->status === 'approved', 403);
        $this->act('dispose', ['update_registers' => $this->updateRegisters]);
        $this->showDispose = false;
    }

    /** ດຳເນີນ transition + ຈັບ error. */
    protected function act(string $action, array $opts = []): void
    {
        try {
            app(DisposalService::class)->transition($this->record, $action, auth()->user(), $opts);
            $this->refreshRecord();
            session()->flash('ok', '✓ ດຳເນີນການ ສຳເລັດ');
        } catch (ValidationException $e) {
            $this->addError('action', $e->validator->errors()->first());
        }
    }

    public function openDelete(): void
    {
        abort_unless(auth()->user()->can('disposal.delete'), 403);
        $this->deleteReason = '';
        $this->showDelete = true;
    }

    public function deleteRecord(): void
    {
        abort_unless(auth()->user()->can('disposal.delete'), 403);
        $this->validate(['deleteReason' => ['required', 'string', 'max:500']], ['deleteReason.required' => 'ກະລຸນາ ໃສ່ ເຫດຜົນ ການ ລຶບ.']);
        $this->record->forceFill(['deleted_reason' => $this->deleteReason, 'deleted_by' => auth()->id()])->save();
        $this->record->delete();
        session()->flash('ok', '✓ ລຶບ '.$this->record->request_number.' (ຍ້າຍ ໄປ Deleted Log)');
        $this->redirectRoute('disposal', navigate: true);
    }

    public function render(): View
    {
        $this->record->loadMissing(['items', 'signoffs', 'preparedBy', 'department']);

        return view('livewire.disposal.show', [
            'stages' => DisposalRecord::STAGES,
            'reasons' => DisposalRecord::REASONS,
            'canSign' => $this->canSign(),
            'currentStage' => $this->record->currentStageKey(),
        ]);
    }
}
