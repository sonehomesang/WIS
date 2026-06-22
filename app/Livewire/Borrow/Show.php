<?php

namespace App\Livewire\Borrow;

use App\Models\BorrowItemPhoto;
use App\Models\BorrowRecord;
use App\Services\BorrowService;
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

    public BorrowRecord $record;

    public string $cancelReason = '';

    public bool $showCancel = false;

    // ── take / return forms ──
    public bool $showTake = false;

    public bool $showReturn = false;

    public bool $showExtension = false;

    public string $extReason = '';

    public string $extProposedDate = '';

    /** @var array<int,string> [borrow_item_id => condition] */
    public array $takeCondition = [];

    public array $returnCondition = [];

    /** @var array<int,int> [borrow_item_id => returnQty] */
    public array $returnQty = [];

    /** @var array<int,array> [borrow_item_id => TemporaryUploadedFile[]] */
    public array $takePhotos = [];

    public array $returnPhotos = [];

    // ── borrower ແຈ້ງສົ່ງຄືນ (return step 1) ──
    public bool $showRequestReturn = false;

    public string $rrDate = '';

    public string $rrRemarks = '';

    // ── delete (soft) ──
    public bool $showDelete = false;

    public string $deleteReason = '';

    // ── admin edit ──
    public bool $showEdit = false;

    /** record-level editable fields */
    public array $ef = [];

    /** per-item: [id => [item_name, qty, return_qty, condition_on_take, condition_on_return]] */
    public array $ei = [];

    /** new photos: ["itemId:kind" => TemporaryUploadedFile[]] */
    public array $ep = [];

    public function mount(BorrowRecord $record): void
    {
        abort_unless(auth()->user()->can('borrow.view'), 403);
        $this->record = $record;
    }

    protected function act(string $action, array $opts = []): bool
    {
        try {
            app(BorrowService::class)->transition($this->record, $action, auth()->user(), $opts);
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

    public function acknowledge(): void
    {
        $this->act('acknowledge');
    }

    public function approve(): void
    {
        $this->act('approve');
    }

    public function cancel(): void
    {
        $this->act('cancel', ['reason' => $this->cancelReason]);
        $this->showCancel = false;
    }

    // ── extension ──
    public function openExtension(): void
    {
        $this->reset(['extReason', 'extProposedDate']);
        $this->extProposedDate = $this->record->planned_return_date?->copy()->addDays(7)->toDateString() ?? '';
        $this->showExtension = true;
    }

    public function requestExtension(): void
    {
        if ($this->act('requestExtension', ['reason' => $this->extReason, 'proposed_date' => $this->extProposedDate])) {
            $this->showExtension = false;
        }
    }

    public function approveExtension(): void
    {
        $this->act('approveExtension');
    }

    public function rejectExtension(): void
    {
        $this->act('rejectExtension');
    }

    // ── confirmTake (ມອບເຄື່ອງ) — ຮູບ condition ບັງຄັບ ──
    public function openTake(): void
    {
        $this->reset(['takeCondition', 'takePhotos']);
        $this->resetErrorBag();
        $this->showTake = true;
    }

    public function confirmTake(): void
    {
        $this->requirePhotoPerItem($this->takePhotos, 'takePhotos');

        foreach ($this->record->items as $it) {
            $this->storeItemPhotos($it, $this->takePhotos[$it->id] ?? [], 'take');
            $it->condition_on_take = $this->takeCondition[$it->id] ?? null;
            $it->save();
        }

        if ($this->act('confirmTake')) {
            $this->reset(['showTake', 'takeCondition', 'takePhotos']);
        }
    }

    // ── confirmReturn (ຮັບคืน) — returnQty (partial) + ຮູບ condition ບັງຄັບ ──
    public function openReturn(): void
    {
        $this->reset(['returnCondition', 'returnPhotos']);
        $this->returnQty = $this->record->items->mapWithKeys(fn ($it) => [$it->id => $it->qty])->all();
        $this->resetErrorBag();
        $this->showReturn = true;
    }

    public function confirmReturn(): void
    {
        $this->requirePhotoPerItem($this->returnPhotos, 'returnPhotos');

        foreach ($this->record->items as $it) {
            $this->storeItemPhotos($it, $this->returnPhotos[$it->id] ?? [], 'return');
            $it->condition_on_return = $this->returnCondition[$it->id] ?? null;
            $it->save();
        }

        $qtyMap = collect($this->returnQty)->map(fn ($q) => max(0, (int) $q))->all();
        if ($this->act('confirmReturn', ['return_qty' => $qtyMap])) {
            $this->reset(['showReturn', 'returnCondition', 'returnPhotos', 'returnQty']);
        }
    }

    // ── borrower ແຈ້ງສົ່ງຄືນ (step 1: ຍັງບໍ່ປ່ຽນ status, ລໍ warehouse ຢືນຢັນ) ──
    public function openRequestReturn(): void
    {
        $this->reset(['rrDate', 'rrRemarks']);
        $this->rrDate = Carbon::today()->toDateString();
        $this->resetErrorBag();
        $this->showRequestReturn = true;
    }

    public function requestReturn(): void
    {
        $this->validate([
            'rrDate' => ['required', 'date'],
            'rrRemarks' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($this->act('requestReturn', ['return_date' => $this->rrDate, 'remarks' => $this->rrRemarks])) {
            $this->reset(['showRequestReturn', 'rrDate', 'rrRemarks']);
        }
    }

    // ── soft delete (admin/borrow.edit; ສະເພาະ draft/cancelled/rejected/returned) ──
    protected function canDelete(): bool
    {
        return $this->canEdit()
            && in_array($this->record->status, ['draft', 'cancelled', 'rejected', 'returned'], true);
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
        $this->record->forceFill([
            'deleted_reason' => $this->deleteReason,
            'deleted_by' => $u->id,
        ])->save();
        $this->record->history()->create([
            'action' => 'delete', 'status' => $this->record->status, 'user_id' => $u->id,
            'user_name' => $u->display_name ?? $u->email, 'comment' => $this->deleteReason, 'created_at' => now(),
        ]);
        $this->record->delete();

        session()->flash('ok', '✓ ລຶບ (ຍ້າຍໄປ deleted log)');

        return $this->redirect(route('borrow'), navigate: true);
    }

    /** ບັງຄັບ ≥1 ຮູບ ຕໍ່ລາຍການ (WORKFLOWS §2.7). */
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
            $path = $file->store("borrow/{$this->record->id}/{$item->id}", 'public');
            $item->photos()->create(['kind' => $kind, 'path' => $path, 'sort_order' => $i]);
        }
    }

    // ── admin edit (super_admin / borrow.edit) ──
    protected function canEdit(): bool
    {
        $u = auth()->user();

        return $u->is_super_admin || $u->can('borrow.edit');
    }

    public function openEdit(): void
    {
        abort_unless($this->canEdit(), 403);
        $r = $this->record;
        $this->ef = [
            'borrower_name' => $r->borrower_name, 'purpose' => $r->purpose,
            'borrow_date' => $r->borrow_date?->toDateString(), 'planned_return_date' => $r->planned_return_date?->toDateString(),
            'period_days' => $r->period_days, 'approver_name' => $r->approver_name, 'acknowledge_name' => $r->acknowledge_name,
            'return_remarks' => $r->return_remarks, 'admin_notes' => $r->admin_notes,
            'borrower_return_date' => $r->borrower_return_date?->toDateString(),
        ];
        $this->ei = $r->items->mapWithKeys(fn ($it) => [$it->id => [
            'item_name' => $it->item_name, 'qty' => $it->qty, 'return_qty' => $it->return_qty,
            'condition_on_take' => $it->condition_on_take, 'condition_on_return' => $it->condition_on_return,
        ]])->all();
        $this->ep = [];
        $this->resetErrorBag();
        $this->showEdit = true;
    }

    public function saveEdit(): void
    {
        abort_unless($this->canEdit(), 403);
        $this->validate([
            'ef.borrower_name' => ['required', 'string', 'max:256'],
            'ef.purpose' => ['nullable', 'string', 'max:1000'],
            'ef.borrow_date' => ['nullable', 'date'],
            'ef.planned_return_date' => ['nullable', 'date'],
            'ef.period_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'ef.return_remarks' => ['nullable', 'string', 'max:1000'],
            'ef.admin_notes' => ['nullable', 'string', 'max:1000'],
            'ef.borrower_return_date' => ['nullable', 'date'],
            'ei.*.item_name' => ['required', 'string', 'max:256'],
            'ei.*.qty' => ['required', 'integer', 'min:1'],
            'ei.*.return_qty' => ['nullable', 'integer', 'min:0'],
            'ep.*.*.*' => ['image', 'max:4096'],
        ]);

        $r = $this->record;
        $r->fill([
            'borrower_name' => $this->ef['borrower_name'],
            'purpose' => $this->ef['purpose'] ?: null,
            'borrow_date' => $this->ef['borrow_date'] ?: $r->borrow_date,
            'planned_return_date' => $this->ef['planned_return_date'] ?: $r->planned_return_date,
            'period_days' => $this->ef['period_days'] ?: $r->period_days,
            'approver_name' => $this->ef['approver_name'] ?: null,
            'acknowledge_name' => $this->ef['acknowledge_name'] ?: null,
            'return_remarks' => $this->ef['return_remarks'] ?: null,
            'admin_notes' => $this->ef['admin_notes'] ?: null,
            'borrower_return_date' => $this->ef['borrower_return_date'] ?: null,
            'updated_by' => auth()->id(),
        ])->save();

        foreach ($r->items as $it) {
            $f = $this->ei[$it->id] ?? null;
            if ($f) {
                $it->update([
                    'item_name' => $f['item_name'],
                    'qty' => max(1, (int) $f['qty']),
                    'return_qty' => ($f['return_qty'] !== null && $f['return_qty'] !== '') ? (int) $f['return_qty'] : null,
                    'condition_on_take' => $f['condition_on_take'] ?: null,
                    'condition_on_return' => $f['condition_on_return'] ?: null,
                ]);
            }
            foreach (['take', 'return'] as $kind) {
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
        $p = BorrowItemPhoto::find($photoId);
        if ($p) {
            Storage::disk('public')->delete($p->path);
            $p->delete();
            $this->record->refresh();
        }
    }

    public function render(): View
    {
        $steps = app(BorrowService::class)->effectiveSteps($this->record);

        return view('livewire.borrow.show', [
            'record' => $this->record->load(['items.inventoryItem.primaryPhoto', 'items.photos', 'history', 'unit', 'department', 'borrower']),
            'steps' => $steps,
            'editable' => $this->canEdit(),
            'deletable' => $this->canDelete(),
            'isBorrower' => auth()->id() === $this->record->borrower_user_id,
        ]);
    }
}
