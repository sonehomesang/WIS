<?php

namespace App\Livewire\Deposit;

use App\Models\DepositItemPhoto;
use App\Models\DepositRecord;
use App\Services\DepositService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
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
        $this->validate([$field.'.*.*' => ['image', 'max:4096']]);
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

        return $u->is_super_admin || $u->can('deposit.edit');
    }

    protected function canDelete(): bool
    {
        return $this->canEdit() && in_array($this->record->status, ['draft', 'cancelled', 'claimed'], true);
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

    // ── admin edit ──
    public function openEdit(): void
    {
        abort_unless($this->canEdit(), 403);
        $r = $this->record;
        $this->ef = [
            'owner_name' => $r->owner_name, 'item_category' => $r->item_category, 'origin_source' => $r->origin_source,
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
        ]])->all();
        $this->ep = [];
        $this->resetErrorBag();
        $this->showEdit = true;
    }

    public function saveEdit(): void
    {
        abort_unless($this->canEdit(), 403);
        $this->validate([
            'ef.owner_name' => ['required', 'string', 'max:256'],
            'ef.item_category' => ['nullable', 'string', 'max:256'],
            'ef.origin_source' => ['nullable', 'string', 'max:500'],
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
            'ep.*.*.*' => ['image', 'max:4096'],
        ]);

        $r = $this->record;
        $r->fill([
            'owner_name' => $this->ef['owner_name'],
            'item_category' => $this->ef['item_category'] ?: null,
            'origin_source' => $this->ef['origin_source'] ?: null,
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
                    'condition_on_claim' => $f['condition_on_claim'] ?: null,
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
        ]);
    }
}
