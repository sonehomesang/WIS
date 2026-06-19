<?php

namespace App\Livewire\Borrow;

use App\Models\BorrowRecord;
use App\Services\BorrowService;
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

    public function render(): View
    {
        $steps = app(BorrowService::class)->effectiveSteps($this->record);

        return view('livewire.borrow.show', [
            'record' => $this->record->load(['items.inventoryItem.primaryPhoto', 'items.photos', 'history', 'unit', 'department']),
            'steps' => $steps,
        ]);
    }
}
