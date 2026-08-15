<?php

namespace App\Livewire\Disposal;

use App\Models\Department;
use App\Models\DisposalRecord;
use App\Models\Uom;
use App\Services\DisposalService;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Show extends Component
{
    use WithFileUploads;

    public DisposalRecord $record;

    // ── edit mode (ໃບ ຍັງ ດຳເນີນ ຢູ່ = ແກ້ໄຂ ໄດ້ · ສຳເລັດ ແລ້ວ = ລັອກ) ──
    public bool $editing = false;

    public string $editTitle = '';

    public ?int $editDept = null;

    public string $editNote = '';

    /** @var array<int, array<string, mixed>> item edit rows */
    public array $ef = [];

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile[]> new photo uploads per item index */
    public array $newPhotos = [];

    /** ຜູ້ ຮັບຮອງ ທີ່ ມອບໝາຍ ຕໍ່ ບົດບາດ (ໃນ ໂໝດ ແກ້ໄຂ). role_key => ['user_id'=>?int, 'title'=>?string] */
    public array $assignees = [];

    // endorse — ຜູ້ ຖືກ ມອບໝາຍ ເຊັນ ຊ່ອງ ຕົນ (inline form)
    public ?string $endorsingRole = null;

    public bool $endorseRejectMode = false;

    public string $endComment = '';

    public string $endRecommendation = '';

    public string $endRejectReason = '';

    // cancel / dispose / delete
    public bool $showCancel = false;

    public string $cancelReason = '';

    public bool $showDispose = false;

    public bool $updateRegisters = true;

    public bool $showDelete = false;

    public string $deleteReason = '';

    public function mount(DisposalRecord $record): void
    {
        abort_unless(static::canOpen($record), 403);
        $this->record = $record;

        // ?edit=1 ຈາກ ລິສ → ເປີດ ໂໝດ ແກ້ໄຂ ໂລດ (ຖ້າ ຍັງ ແກ້ ໄດ້)
        if (request()->boolean('edit') && $this->canEditRecord()) {
            $this->openEdit();
        }
    }

    /** ໃບ ຍັງ ດຳເນີນ ຢູ່ (ບໍ່ ແມ່ນ ຈຳໜ່າຍ/ຍົກເລີກ/ຕີ ກັບ) + ມີ ສິດ = ແກ້ໄຂ ໄດ້. */
    public function canEditRecord(): bool
    {
        $u = auth()->user();

        return ! in_array($this->record->status, ['disposed', 'cancelled', 'rejected'], true)
            && ($u->can('disposal.edit') || $this->record->prepared_by_user_id === $u->id || $u->is_super_admin);
    }

    /** ຜູ້ ເປີດ ໃບ ໄດ້: (ມີ ສິດ disposal.view + ຢູ່ ໃນ scope) ຫຼື ເປັນ ຜູ້ ຮັບຮອງ ທີ່ ຖືກ ມອບໝາຍ
     *  (ໄດ້ ຮັບ ອີເມລ ລິ້ງ) — ເຖິງ ບໍ່ ມີ ສິດ disposal.view ກໍ ເປີດ ໄດ້ ເພື່ອ ຮັບຮອງ. */
    public static function canOpen(DisposalRecord $record): bool
    {
        $u = auth()->user();
        if (! $u) {
            return false;
        }
        if ($record->signoffs()->where('user_id', $u->id)->exists()) {
            return true;
        }

        return $u->can('disposal.view') && static::canAccess($record);
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

    protected function refreshRecord(): void
    {
        $this->record = $this->record->fresh(['items', 'signoffs', 'preparedBy', 'department']);
    }

    public function submit(): void
    {
        $u = auth()->user();
        abort_unless($u->can('disposal.create') && ($this->record->prepared_by_user_id === $u->id || $u->can('disposal.edit') || $u->is_super_admin), 403);
        try {
            $svc = app(DisposalService::class);
            $svc->transition($this->record, 'submit', $u);
            $this->refreshRecord();
            $sent = $svc->notifyPendingEndorsers($this->record);
            session()->flash('ok', '✓ ສົ່ງ ຂໍ ຮັບຮອງ ແລ້ວ'.($sent ? " · ແຈ້ງ ຜູ້ ຮັບຮອງ {$sent} ຄົນ ທາງ ອີເມລ" : ''));
        } catch (ValidationException $e) {
            $this->addError('action', $e->validator->errors()->first());
        }
    }

    // ── endorsement (ຜູ້ ຖືກ ມອບໝາຍ ເຊັນ ຊ່ອງ ຕົນ) ──
    /** signoff ຂອງ ບົດບາດ ນີ້ (ຫຼື null). */
    protected function signoffFor(string $roleKey): ?\App\Models\DisposalSignoff
    {
        return $this->record->signoffs->firstWhere('role_key', $roleKey);
    }

    /** auth user ເປັນ ຜູ້ ຖືກ ມອບໝາຍ ຂອງ ບົດບາດ ນີ້ + ຍັງ ບໍ່ ເຊັນ + ໃບ ຢູ່ ຮອບ ຮັບຮອງ. */
    public function canEndorse(string $roleKey): bool
    {
        $u = auth()->user();
        $s = $this->signoffFor($roleKey);

        return $this->record->status === 'in_review'
            && $s !== null && $s->user_id !== null && $s->signed_at === null
            && ((int) $s->user_id === (int) $u->id || $u->is_super_admin);
    }

    public function openEndorse(string $roleKey): void
    {
        abort_unless($this->canEndorse($roleKey), 403);
        $this->endorsingRole = $roleKey;
        $this->endorseRejectMode = false;
        $this->endComment = '';
        $this->endRecommendation = '';
        $this->endRejectReason = '';
        $this->resetErrorBag();
    }

    public function openEndorseReject(string $roleKey): void
    {
        abort_unless($this->canEndorse($roleKey), 403);
        $this->endorsingRole = $roleKey;
        $this->endorseRejectMode = true;
        $this->endRejectReason = '';
        $this->resetErrorBag();
    }

    public function cancelEndorse(): void
    {
        $this->endorsingRole = null;
        $this->endorseRejectMode = false;
    }

    public function confirmEndorse(): void
    {
        $role = (string) $this->endorsingRole;
        abort_unless($this->canEndorse($role), 403);
        try {
            app(DisposalService::class)->endorse($this->record, $role, auth()->user(), [
                'comment' => $this->endComment ?: null,
                'recommendation' => $this->endRecommendation ?: null,
            ]);
            $this->endorsingRole = null;
            $this->refreshRecord();
            session()->flash('ok', '✓ ບັນທຶກ ການ ຮັບຮອງ ຂອງ ທ່ານ ແລ້ວ');
        } catch (ValidationException $e) {
            $this->addError('action', $e->validator->errors()->first());
        }
    }

    public function confirmEndorseReject(): void
    {
        $role = (string) $this->endorsingRole;
        abort_unless($this->canEndorse($role), 403);
        if (trim($this->endRejectReason) === '') {
            $this->addError('endRejectReason', 'ຕ້ອງ ໃສ່ ເຫດຜົນ ຕີ ກັບ.');

            return;
        }
        try {
            app(DisposalService::class)->rejectEndorsement($this->record, $role, auth()->user(), ['reason' => $this->endRejectReason]);
            $this->endorsingRole = null;
            $this->endorseRejectMode = false;
            $this->refreshRecord();
            session()->flash('ok', '↩ ຕີ ກັບ ໃບ ນີ້ ແລ້ວ — ກັບ ໄປ ຮ່າງ ເພື່ອ ແກ້ໄຂ');
        } catch (ValidationException $e) {
            $this->addError('action', $e->validator->errors()->first());
        }
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

    // ── edit mode ──────────────────────────────────────────────────
    protected function blankEditItem(): array
    {
        return [
            'id' => null, 'source_type' => 'new', 'source_id' => null,
            'item_name' => '', 'asset_code' => '', 'fixed_asset_no' => '',
            'qty' => 1, 'unit' => '', 'condition' => '',
            'reason' => '', 'reason_detail' => '',
            'recommendation' => '', 'recommendation_detail' => '',
            'estimated_value' => '', 'currency' => '', 'photos' => [],
        ];
    }

    public function openEdit(): void
    {
        abort_unless($this->canEditRecord(), 403);
        $this->record->loadMissing('items');
        $this->editTitle = $this->record->title ?? '';
        $this->editDept = $this->record->department_id;
        $this->editNote = $this->record->note ?? '';
        $this->ef = $this->record->items->map(fn ($it) => [
            'id' => $it->id,
            'source_type' => $it->source_type,
            'source_id' => $it->source_id,
            'item_name' => $it->item_name,
            'asset_code' => $it->asset_code ?? '',
            'fixed_asset_no' => $it->fixed_asset_no ?? '',
            'qty' => $it->qty,
            'unit' => $it->unit ?? '',
            'condition' => $it->condition ?? '',
            'reason' => $it->reason ?? '',
            'reason_detail' => $it->reason_detail ?? '',
            'recommendation' => $it->recommendation ?? '',
            'recommendation_detail' => $it->recommendation_detail ?? '',
            'estimated_value' => $it->estimated_value !== null ? (string) $it->estimated_value : '',
            'currency' => $it->currency ?? '',
            'photos' => array_values($it->photos ?? []),
        ])->values()->all();
        if (empty($this->ef)) {
            $this->ef = [$this->blankEditItem()];
        }

        // ໂຫຼດ ການ ມອບໝາຍ ຜູ້ ຮັບຮອງ ຕໍ່ ບົດບາດ
        $this->record->loadMissing('signoffs');
        $this->assignees = [];
        foreach (array_keys(DisposalRecord::STAGES) as $roleKey) {
            $s = $this->record->signoffs->firstWhere('role_key', $roleKey);
            $this->assignees[$roleKey] = ['user_id' => $s?->user_id, 'title' => $s?->title ?? ''];
        }

        $this->newPhotos = [];
        $this->resetErrorBag();
        $this->editing = true;
    }

    public function cancelEdit(): void
    {
        $this->editing = false;
        $this->ef = [];
        $this->newPhotos = [];
        $this->resetErrorBag();
    }

    public function addEditItem(): void
    {
        $this->ef[] = $this->blankEditItem();
    }

    public function removeEditItem(int $i): void
    {
        unset($this->ef[$i], $this->newPhotos[$i]);
        $this->ef = array_values($this->ef);
        $this->newPhotos = array_values($this->newPhotos);
        if (empty($this->ef)) {
            $this->ef = [$this->blankEditItem()];
        }
    }

    /** ລຶບ ຮູບ ເກົ່າ (path) ອອກ ຈາກ ລາຍການ ແກ້ໄຂ. */
    public function removeExistingPhoto(int $i, int $p): void
    {
        if (isset($this->ef[$i]['photos'][$p])) {
            unset($this->ef[$i]['photos'][$p]);
            $this->ef[$i]['photos'] = array_values($this->ef[$i]['photos']);
        }
    }

    public function saveEdit(): void
    {
        abort_unless($this->canEditRecord(), 403);

        $this->validate([
            'editTitle' => ['nullable', 'string', 'max:256'],
            'editDept' => ['nullable', 'exists:departments,id'],
            'editNote' => ['nullable', 'string', 'max:1000'],
            'ef' => ['required', 'array', 'min:1'],
            'ef.*.item_name' => ['required', 'string', 'max:256'],
            'ef.*.qty' => ['required', 'integer', 'min:1'],
            'ef.*.asset_code' => ['nullable', 'string', 'max:64'],
            'ef.*.fixed_asset_no' => ['nullable', 'string', 'max:64'],
            'ef.*.unit' => ['nullable', 'string', 'max:32'],
            'ef.*.condition' => ['nullable', 'string', 'max:128'],
            'ef.*.reason' => ['nullable', 'string', 'max:128'],
            'ef.*.reason_detail' => ['nullable', 'string', 'max:256'],
            'ef.*.recommendation' => ['nullable', 'string', 'max:128'],
            'ef.*.recommendation_detail' => ['nullable', 'string', 'max:256'],
            'ef.*.estimated_value' => ['nullable', 'numeric', 'min:0'],
            'ef.*.currency' => ['nullable', 'in:LAK,THB,USD'],
            'newPhotos.*.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ], [], ['ef.*.item_name' => 'ຊື່ເຄື່ອງ']);

        $keepIds = [];
        foreach (array_values($this->ef) as $i => $row) {
            $payload = [
                'source_type' => in_array($row['source_type'] ?? 'new', ['inventory', 'equipment', 'deposit', 'new'], true) ? $row['source_type'] : 'new',
                'source_id' => $row['source_id'] ?: null,
                'item_name' => $row['item_name'],
                'asset_code' => ($row['asset_code'] ?? '') !== '' ? $row['asset_code'] : null,
                'fixed_asset_no' => ($row['fixed_asset_no'] ?? '') !== '' ? $row['fixed_asset_no'] : null,
                'qty' => (int) $row['qty'],
                'unit' => ($row['unit'] ?? '') !== '' ? $row['unit'] : null,
                'condition' => ($row['condition'] ?? '') !== '' ? $row['condition'] : null,
                'reason' => ($row['reason'] ?? '') !== '' ? $row['reason'] : null,
                'reason_detail' => ($row['reason_detail'] ?? '') !== '' ? $row['reason_detail'] : null,
                'recommendation' => ($row['recommendation'] ?? '') !== '' ? $row['recommendation'] : null,
                'recommendation_detail' => ($row['recommendation_detail'] ?? '') !== '' ? $row['recommendation_detail'] : null,
                'estimated_value' => ($row['estimated_value'] ?? '') !== '' ? $row['estimated_value'] : null,
                'currency' => ($row['currency'] ?? '') !== '' ? $row['currency'] : null,
                'sort_order' => $i,
            ];

            if (! empty($row['id'])) {
                $item = $this->record->items()->whereKey($row['id'])->first();
                if (! $item) {
                    continue;
                }
                $item->update($payload);
            } else {
                $item = $this->record->items()->create($payload + ['photos' => null]);
            }

            // final photos = kept existing + newly uploaded for this row
            $photos = array_values($row['photos'] ?? []);
            foreach (array_values($this->newPhotos[$i] ?? []) as $file) {
                $photos[] = $file->store("disposal/{$this->record->id}/{$item->id}", 'public');
            }
            $item->update(['photos' => $photos ?: null]);
            $keepIds[] = $item->id;
        }

        // ລາຍການ ທີ່ ຖືກ ຖອດ ອອກ ຕອນ ແກ້ໄຂ → ລຶບ
        $this->record->items()->whereNotIn('id', $keepIds)->delete();

        $this->record->update([
            'title' => $this->editTitle ?: null,
            'department_id' => $this->editDept,
            'note' => $this->editNote ?: null,
        ]);

        // ມອບໝາຍ ຜູ້ ຮັບຮອງ ຕໍ່ 5 ບົດບາດ + ແຈ້ງ ຄົນ ໃໝ່ (ຖ້າ ໃບ ຢູ່ ຮອບ ຮັບຮອງ ແລ້ວ)
        $svc = app(DisposalService::class);
        $svc->assignEndorsers($this->record, $this->assignees, auth()->user());
        $notice = '';
        if ($this->record->status === 'in_review') {
            $sent = $svc->notifyPendingEndorsers($this->record->fresh());
            $notice = $sent ? " · ແຈ້ງ ຜູ້ ຮັບຮອງ ໃໝ່ {$sent} ຄົນ" : '';
        }

        $this->editing = false;
        $this->newPhotos = [];
        $this->refreshRecord();
        session()->flash('ok', '✓ ບັນທຶກ ການ ແກ້ໄຂ ໃບ ຈຳໜ່າຍ ແລ້ວ'.$notice);
    }

    public function render(): View
    {
        $this->record->loadMissing(['items', 'signoffs', 'preparedBy', 'department']);

        return view('livewire.disposal.show', [
            'stages' => DisposalRecord::STAGES,
            'reasons' => DisposalRecord::REASONS,
            'recommendations' => DisposalRecord::RECOMMENDATIONS,
            'departments' => $this->editing ? Department::where('is_active', true)->orderBy('name')->get(['id', 'name']) : collect(),
            'uoms' => $this->editing ? Uom::where('is_active', true)->orderBy('name')->get(['id', 'name']) : collect(),
            'ownerUsers' => $this->editing ? \App\Models\User::where('status', 'active')->orderBy('display_name')->get(['id', 'display_name', 'email']) : collect(),
            'canEdit' => $this->canEditRecord(),
        ]);
    }
}
