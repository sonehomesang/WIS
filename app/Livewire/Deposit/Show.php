<?php

namespace App\Livewire\Deposit;

use App\Models\Department;
use App\Models\DepositItemPhoto;
use App\Models\DepositRecord;
use App\Models\User;
use App\Services\DepositService;
use App\Support\ConditionStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Show extends Component
{
    use WithFileUploads;

    public DepositRecord $record;

    // ── cancel ──
    public bool $showCancel = false;

    public string $cancelReason = '';

    // ── accept (assign storage) ──
    public bool $showAccept = false;

    public string $afLocation = '';

    public string $afShelf = '';

    public string $afInstructions = '';

    // ── confirmStored / confirmFixed ──
    public bool $showStore = false;

    public string $storeMode = 'confirmStored'; // or confirmFixed

    /** @var array<int, TemporaryUploadedFile[]> [deposit_item_id => files] */
    public array $storePhotos = [];

    // ── flag issue ──
    public bool $showFlag = false;

    public string $flagReason = '';

    // ── claim ──
    public bool $showClaim = false;

    public string $claimDate = '';

    public array $claimPhotos = [];

    /** @var array<int,string> [deposit_item_id => condition] */
    public array $claimCondition = [];

    // ── delete ──
    // ── admin: ຄືນ ສະຖານະ ໃບ (super_admin ເທົ່ານັ້ນ — ແກ້ ຄວາມ ຜິດ / ທົດສອບ) ──
    public bool $showStatusReset = false;

    public string $resetStatus = '';

    public bool $showDelete = false;

    public string $deleteReason = '';

    // ── admin edit ──
    public bool $showEdit = false;

    public array $ef = [];   // record-level

    public array $ei = [];   // per-item

    /** new photos: [kind => [itemId => files]] */
    public array $ep = [];

    public function mount(DepositRecord $record): void
    {
        abort_unless(auth()->user()->can('deposit.view'), 403);
        $u = auth()->user();
        $deptScoped = $u->transactionScope() === 'department' && $u->department_id && $record->owner_dept_id === $u->department_id;
        abort_unless($this->isStaff() || $record->owner_user_id === $u->id || $deptScoped, 403);
        $this->record = $record;

        // ເປີດ ໂດຍ ກົງ ຈາກ ປຸ່ມ "ແກ້ໄຂ" ໃນ ໜ້າ ລິສ (?edit=1)
        if (request()->boolean('edit') && $this->canEdit()) {
            $this->openEdit();
        }
    }

    protected function isStaff(): bool
    {
        $u = auth()->user();

        return $u->is_super_admin || $u->hasAnyRole(['admin', 'warehouse_staff']);
    }

    /** Server-side authorization per transition (owner submits/cancels; warehouse handles the rest). */
    protected function authorizeAction(string $action): void
    {
        if ($this->isStaff()) {
            return;
        }
        $isOwner = $this->record->owner_user_id === auth()->id();
        $ok = match ($action) {
            'submit', 'cancel' => $isOwner,
            default => false,   // accept / confirmStored / confirmFixed / flagIssue / confirmClaim = warehouse only
        };
        abort_unless($ok, 403);
    }

    protected function act(string $action, array $opts = []): bool
    {
        $this->authorizeAction($action);

        try {
            app(DepositService::class)->transition($this->record, $action, auth()->user(), $opts);
            $this->record->refresh();
            session()->flash('ok', "✓ {$action} ສຳເລັດ");

            return true;
        } catch (ValidationException $e) {
            $this->addError('action', $e->validator->errors()->first());

            return false;
        }
    }

    public function submit(): void
    {
        $this->act('submit');
    }

    // ── cancel ──
    public function cancel(): void
    {
        $this->act('cancel', ['reason' => $this->cancelReason]);
        $this->showCancel = false;
    }

    // ── accept ──
    public function openAccept(): void
    {
        $this->reset(['afLocation', 'afShelf', 'afInstructions']);
        $this->resetErrorBag();
        $this->showAccept = true;
    }

    public function accept(): void
    {
        if ($this->act('accept', [
            'storage_location' => $this->afLocation,
            'storage_shelf_label' => $this->afShelf,
            'warehouse_instructions' => $this->afInstructions ?: null,
        ])) {
            $this->reset(['showAccept', 'afLocation', 'afShelf', 'afInstructions']);
        }
    }

    // ── confirmStored / confirmFixed (ຮູບ ≥1/item) ──
    public function openStore(string $mode = 'confirmStored'): void
    {
        $this->storeMode = $mode === 'confirmFixed' ? 'confirmFixed' : 'confirmStored';
        $this->reset(['storePhotos']);
        $this->resetErrorBag();
        $this->showStore = true;
    }

    public function confirmStore(): void
    {
        $this->authorizeAction($this->storeMode);         // ກວດ ສິດ ກ່ອນ ຂຽນ ຮູບ (warehouse ເທົ່ານັ້ນ)
        if (! in_array($this->record->status, ['accepted', 'needs_fix'], true)) {
            return;                                       // ຜິດ state — ບໍ່ ຂຽນ ຫຍັງ
        }
        $this->requirePhotoPerItem($this->storePhotos, 'storePhotos');

        foreach ($this->record->items as $it) {
            $this->storeItemPhotos($it, $this->storePhotos[$it->id] ?? [], 'stored');
        }

        if ($this->act($this->storeMode)) {
            $this->reset(['showStore', 'storePhotos']);
        }
    }

    // ── flag issue ──
    public function openFlag(): void
    {
        $this->reset(['flagReason']);
        $this->resetErrorBag();
        $this->showFlag = true;
    }

    public function flagIssue(): void
    {
        if ($this->act('flagIssue', ['reason' => $this->flagReason])) {
            $this->reset(['showFlag', 'flagReason']);
        }
    }

    // ── claim (ຮູບ ≥1/item + condition) ──
    public function openClaim(): void
    {
        $this->reset(['claimPhotos', 'claimCondition']);
        $this->claimDate = Carbon::today()->toDateString();
        $this->resetErrorBag();
        $this->showClaim = true;
    }

    public function confirmClaim(): void
    {
        $this->authorizeAction('confirmClaim');           // ກວດ ສິດ ກ່ອນ ຂຽນ ຮູບ/condition (warehouse ເທົ່ານັ້ນ)
        if ($this->record->status !== 'stored') {
            return;                                       // ຜິດ state — ບໍ່ ຂຽນ ຫຍັງ
        }
        $this->requirePhotoPerItem($this->claimPhotos, 'claimPhotos');

        foreach ($this->record->items as $it) {
            $this->storeItemPhotos($it, $this->claimPhotos[$it->id] ?? [], 'claim');
            $it->condition_on_claim = $this->claimCondition[$it->id] ?? null;
            $it->save();
        }

        if ($this->act('confirmClaim', ['claim_date' => $this->claimDate])) {
            $this->reset(['showClaim', 'claimPhotos', 'claimCondition']);
        }
    }

    /** ບັງຄັບ ≥1 ຮູບ ຕໍ່ລາຍການ. */
    protected function requirePhotoPerItem(array $photos, string $field): void
    {
        foreach ($this->record->items as $it) {
            if (empty($photos[$it->id])) {
                $this->addError($field, "ຕ້ອງມີຢ່າງໜ້ອຍ 1 ຮູບ ສຳລັບ \"{$it->item_name}\".");
                throw ValidationException::withMessages([$field => 'photo required']);
            }
        }
        $this->validate([$field.'.*.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096']]);
    }

    protected function storeItemPhotos($item, array $files, string $kind): void
    {
        foreach (array_values($files) as $i => $file) {
            $path = $file->store("deposit/{$this->record->id}/{$item->id}", 'public');
            $item->photos()->create(['kind' => $kind, 'path' => $path, 'sort_order' => $i]);
        }
    }

    // ── soft delete (admin/deposit.edit; ສະເພາະ draft/cancelled/claimed) ──
    protected function canEdit(): bool
    {
        $u = auth()->user();

        // ຖືກ ດຶງ ເຂົ້າ ໃບ ຈຳໜ່າຍ (disposal) ຫຼື ຈຳໜ່າຍ ແລ້ວ (disposed) = ລິສ ຕາຍ, ລັອກ ແກ້ໄຂ
        return ($u->is_super_admin || $u->can('deposit.edit'))
            && ! in_array($this->record->status, ['disposal', 'disposed'], true);
    }

    protected function canDelete(): bool
    {
        $u = auth()->user();

        return ($u->is_super_admin || $u->can('deposit.delete'))
            && in_array($this->record->status, ['draft', 'cancelled', 'claimed'], true);
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

        return $this->redirect(route('deposit'), navigate: true);
    }

    // ── admin: ຄືນ ສະຖານະ ໃບ (super_admin) ──
    public function openStatusReset(): void
    {
        abort_unless(auth()->user()->is_super_admin, 403);
        $this->resetStatus = $this->record->status;
        $this->resetErrorBag();
        $this->showStatusReset = true;
    }

    public function applyStatusReset(): void
    {
        abort_unless(auth()->user()->is_super_admin, 403);
        $valid = ['draft', 'submitted', 'accepted', 'stored', 'needs_fix', 'claimed', 'cancelled', 'disposal', 'disposed'];
        $this->validate(['resetStatus' => ['required', Rule::in($valid)]]);

        $old = $this->record->status;
        $this->record->update(['status' => $this->resetStatus]);
        $u = auth()->user();
        $this->record->history()->create([
            'action' => 'status_reset', 'status' => $this->resetStatus, 'user_id' => $u->id,
            'user_name' => $u->display_name ?? $u->email, 'comment' => "admin ຄືນ ສະຖານະ: {$old} → {$this->resetStatus}", 'created_at' => now(),
        ]);
        $this->record->refresh();
        $this->showStatusReset = false;
        session()->flash('ok', '✓ ຕັ້ງ ສະຖານະ ໃໝ່ (admin): '.$this->resetStatus);
    }

    // ── admin edit ──
    public function openEdit(): void
    {
        abort_unless($this->canEdit(), 403);
        $r = $this->record;
        $this->ef = [
            'owner_user_id' => $r->owner_user_id, 'owner_dept_id' => $r->owner_dept_id,
            'item_category' => $r->item_category, 'origin_source' => $r->origin_source,
            'functional_status' => $r->functional_status,
            'original_deposit_date' => $r->original_deposit_date?->toDateString(), 'original_receiver' => $r->original_receiver,
            'deposit_reason' => $r->deposit_reason, 'expected_duration' => $r->expected_duration,
            'deposit_date' => $r->deposit_date?->toDateString(), 'expected_claim_date' => $r->expected_claim_date?->toDateString(),
            'storage_location' => $r->storage_location, 'storage_shelf_label' => $r->storage_shelf_label,
            'warehouse_instructions' => $r->warehouse_instructions, 'remark' => $r->remark,
        ];
        $this->ei = $r->items->mapWithKeys(fn ($it) => [$it->id => [
            'item_name' => $it->item_name, 'asset_code' => $it->asset_code, 'fixed_asset_no' => $it->fixed_asset_no,
            'description' => $it->description, 'qty' => $it->qty, 'unit' => $it->unit,
            'estimated_value' => $it->estimated_value, 'currency' => $it->currency,
            'condition_on_deposit' => $it->condition_on_deposit, 'condition_on_claim' => $it->condition_on_claim,
            'storage_location' => $it->storage_location,
            'condition_status' => $it->condition_status ?? 'in_service',
        ]])->all();
        $this->ep = [];
        $this->resetErrorBag();
        $this->showEdit = true;
    }

    public function saveEdit(): void
    {
        abort_unless($this->canEdit(), 403);
        $this->validate([
            'ef.owner_user_id' => ['required', 'exists:users,id'],
            'ef.owner_dept_id' => ['nullable', 'exists:departments,id'],
            'ef.item_category' => ['nullable', 'string', 'max:256'],
            'ef.origin_source' => ['nullable', 'string', 'max:500'],
            'ef.functional_status' => ['nullable', 'in:usable,partial,unusable'],
            'ef.original_deposit_date' => ['nullable', 'date'],
            'ef.original_receiver' => ['nullable', 'string', 'max:256'],
            'ef.deposit_reason' => ['nullable', 'string', 'max:1000'],
            'ef.expected_duration' => ['nullable', 'string', 'max:128'],
            'ef.deposit_date' => ['nullable', 'date'],
            'ef.expected_claim_date' => ['nullable', 'date'],
            'ef.storage_location' => ['nullable', 'string', 'max:256'],
            'ef.storage_shelf_label' => ['nullable', 'string', 'max:128'],
            'ef.warehouse_instructions' => ['nullable', 'string', 'max:2000'],
            'ef.remark' => ['nullable', 'string', 'max:500'],
            'ei.*.item_name' => ['required', 'string', 'max:256'],
            'ei.*.asset_code' => ['nullable', 'string', 'max:64'],
            'ei.*.fixed_asset_no' => ['nullable', 'string', 'max:64'],
            'ei.*.qty' => ['required', 'integer', 'min:1'],
            'ei.*.estimated_value' => ['nullable', 'numeric', 'min:0'],
            'ei.*.currency' => ['nullable', 'in:LAK,THB,USD'],
            'ei.*.condition_status' => ['required', ConditionStatus::rule()],
            'ei.*.storage_location' => ['nullable', 'string', 'max:256'],
            'ep.*.*.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $r = $this->record;
        $deptId = $this->ef['owner_dept_id'] ?: null;
        $owner = User::find($this->ef['owner_user_id'] ?? null);
        $r->fill([
            'owner_user_id' => $owner?->id ?? $r->owner_user_id,
            'owner_name' => $owner ? ($owner->display_name ?? $owner->email) : $r->owner_name,
            'owner_email' => $owner ? mb_strtolower($owner->email) : $r->owner_email,
            'owner_dept_id' => $deptId,
            'owner_unit_id' => $deptId ? Department::find($deptId)?->unit_id : null,
            'item_category' => $this->ef['item_category'] ?: null,
            'origin_source' => $this->ef['origin_source'] ?: null,
            'functional_status' => $this->ef['functional_status'] ?: null,
            'original_deposit_date' => $this->ef['original_deposit_date'] ?: null,
            'original_receiver' => $this->ef['original_receiver'] ?: null,
            'deposit_reason' => $this->ef['deposit_reason'] ?: null,
            'expected_duration' => $this->ef['expected_duration'] ?: null,
            'deposit_date' => $this->ef['deposit_date'] ?: $r->deposit_date,
            'expected_claim_date' => $this->ef['expected_claim_date'] ?: null,
            'storage_location' => $this->ef['storage_location'] ?: null,
            'storage_shelf_label' => $this->ef['storage_shelf_label'] ?: null,
            'warehouse_instructions' => $this->ef['warehouse_instructions'] ?: null,
            'remark' => $this->ef['remark'] ?: null,
            'updated_by' => auth()->id(),
        ])->save();

        foreach ($r->items as $it) {
            $f = $this->ei[$it->id] ?? null;
            if ($f) {
                $it->update([
                    'item_name' => $f['item_name'],
                    'asset_code' => ($f['asset_code'] ?? null) ?: null,
                    'fixed_asset_no' => ($f['fixed_asset_no'] ?? null) ?: null,
                    'description' => $f['description'] ?: null,
                    'qty' => max(1, (int) $f['qty']),
                    'unit' => $f['unit'] ?: null,
                    'estimated_value' => ($f['estimated_value'] !== null && $f['estimated_value'] !== '') ? $f['estimated_value'] : null,
                    'currency' => $f['currency'] ?: null,
                    'condition_on_deposit' => $f['condition_on_deposit'] ?: null,
                    'storage_location' => ($f['storage_location'] ?? '') ?: null,
                    'condition_on_claim' => $f['condition_on_claim'] ?: null,
                    'condition_status' => $cs = ($f['condition_status'] ?? 'in_service'),
                    'condition_set_at' => $it->condition_status !== $cs ? now() : $it->condition_set_at,
                    'condition_set_by' => $it->condition_status !== $cs ? auth()->id() : $it->condition_set_by,
                ]);
            }
            foreach (['deposit', 'stored', 'claim'] as $kind) {
                $this->storeItemPhotos($it, $this->ep[$kind][$it->id] ?? [], $kind);
            }
        }

        $u = auth()->user();
        $r->history()->create([
            'action' => 'edit', 'status' => $r->status, 'user_id' => $u->id,
            'user_name' => $u->display_name ?? $u->email, 'comment' => 'admin edit', 'created_at' => now(),
        ]);

        $this->record->refresh();
        session()->flash('ok', '✓ ບັນທຶກການແກ້ໄຂ');
        $this->reset(['showEdit', 'ef', 'ei', 'ep']);
    }

    public function removePhoto(int $photoId): void
    {
        abort_unless($this->canEdit(), 403);
        // ຜູກ photoId ກັບ record ນີ້ ເທົ່ານັ້ນ (ກັນ ລຶບ ຮູບ ໃບ ຝາກ ຂອງ ຄົນ ອື່ນ ຜ່ານ id ປອມ).
        $p = DepositItemPhoto::whereHas('depositItem', fn ($q) => $q->where('record_id', $this->record->id))->find($photoId);
        if ($p) {
            Storage::disk('public')->delete($p->path);
            $p->delete();
            $this->record->refresh();
        }
    }

    public function render(): View
    {
        return view('livewire.deposit.show', [
            'record' => $this->record->load(['items.photos', 'history', 'unit', 'department', 'owner']),
            'editable' => $this->canEdit(),
            'deletable' => $this->canDelete(),
            'isOwner' => auth()->id() === $this->record->owner_user_id,
            'departments' => Department::where('is_active', true)->with('unit:id,name')->orderBy('name')->get(['id', 'name', 'unit_id']),
            'ownerUsers' => User::where('status', 'active')->orderBy('display_name')->get(['id', 'display_name', 'email']),
        ]);
    }
}
