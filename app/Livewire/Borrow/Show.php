<?php

namespace App\Livewire\Borrow;

use App\Models\BorrowRecord;
use App\Services\BorrowService;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public BorrowRecord $record;

    public string $cancelReason = '';

    public bool $showCancel = false;

    public function mount(BorrowRecord $record): void
    {
        abort_unless(auth()->user()->can('borrow.view'), 403);
        $this->record = $record;
    }

    protected function act(string $action, array $opts = []): void
    {
        try {
            app(BorrowService::class)->transition($this->record, $action, auth()->user(), $opts);
            $this->record->refresh();
            session()->flash('ok', "✓ {$action} ສຳເລັດ");
        } catch (ValidationException $e) {
            $this->addError('action', $e->validator->errors()->first());
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

    public function confirmTake(): void
    {
        $this->act('confirmTake');
    }

    public function confirmReturn(): void
    {
        $this->act('confirmReturn');
    }

    public function cancel(): void
    {
        $this->act('cancel', ['reason' => $this->cancelReason]);
        $this->showCancel = false;
    }

    public function render(): View
    {
        $steps = app(BorrowService::class)->effectiveSteps($this->record);

        return view('livewire.borrow.show', [
            'record' => $this->record->load(['items', 'history']),
            'steps' => $steps,
        ]);
    }
}
