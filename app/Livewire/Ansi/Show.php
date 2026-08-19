<?php

namespace App\Livewire\Ansi;

use App\Models\AnsiApplication;
use App\Models\Uom;
use App\Models\User;
use App\Services\AnsiService;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public AnsiApplication $record;

    // reject
    public bool $showReject = false;

    public string $rejectStage = '';

    public string $rejectReason = '';

    // warehouse complete
    public bool $showWarehouse = false;

    public array $itemNumbers = [];   // [itemId => number]

    public string $prNumber = '';

    public string $warehouseNote = '';

    // draft edit
    public bool $showEdit = false;

    public array $ef = [];   // general fields

    public array $ei = [];   // items

    public function mount(AnsiApplication $record): void
    {
        abort_unless(auth()->user()->can('ansi.view'), 403);
        abort_unless($this->canView($record), 403);
        $this->record = $record;
    }

    // ── authorization helpers ───────────────────────────────────────────────
    protected function isWarehouse(): bool
    {
        $u = auth()->user();

        return $u->is_super_admin || $u->hasAnyRole(['admin', 'warehouse_staff']);
    }

    protected function canView(AnsiApplication $r): bool
    {
        $u = auth()->user();

        return $this->isWarehouse() || in_array($u->id, [$r->originator_user_id, $r->hos_user_id, $r->manager_user_id], true);
    }

    public function isOriginator(): bool
    {
        return auth()->id() === $this->record->originator_user_id;
    }

    public function canEndorse(): bool
    {
        return $this->record->status === 'pending_hos' && auth()->id() === $this->record->hos_user_id;
    }

    public function canApprove(): bool
    {
        return $this->record->status === 'pending_manager' && auth()->id() === $this->record->manager_user_id;
    }

    public function canWarehouse(): bool
    {
        return $this->record->status === 'pending_warehouse' && $this->isWarehouse() && auth()->user()->can('ansi.edit');
    }

    protected function svc(): AnsiService
    {
        return app(AnsiService::class);
    }

    protected function act(callable $fn): void
    {
        try {
            $fn();
            $this->record->refresh();
        } catch (ValidationException $e) {
            $this->addError('act', $e->validator->errors()->first());
        }
    }

    // ── originator ──
    public function submit(): void
    {
        abort_unless($this->isOriginator() && $this->record->status === 'draft', 403);
        $this->act(fn () => $this->svc()->submit($this->record, auth()->user()));
        if (! $this->getErrorBag()->has('act')) {
            session()->flash('ok', '✓ Submitted for endorsement');
        }
    }

    public function cancel(): void
    {
        abort_unless($this->isOriginator(), 403);
        $this->act(fn () => $this->svc()->cancel($this->record, auth()->user()));
        session()->flash('ok', '✓ Cancelled');
    }

    // ── HoS / Manager ──
    public function endorse(): void
    {
        abort_unless($this->canEndorse(), 403);
        $this->act(fn () => $this->svc()->endorse($this->record, auth()->user()));
        session()->flash('ok', '✓ Endorsed → Manager');
    }

    public function approve(): void
    {
        abort_unless($this->canApprove(), 403);
        $this->act(fn () => $this->svc()->approve($this->record, auth()->user()));
        session()->flash('ok', '✓ Approved → Warehouse');
    }

    // ── reject (shared) ──
    public function openReject(string $stage): void
    {
        $this->rejectStage = $stage;
        $this->rejectReason = '';
        $this->resetErrorBag();
        $this->showReject = true;
    }

    public function reject(): void
    {
        $ok = match ($this->rejectStage) {
            'hos' => $this->canEndorse(),
            'manager' => $this->canApprove(),
            'warehouse' => $this->canWarehouse(),
            default => false,
        };
        abort_unless($ok, 403);
        $this->validate(['rejectReason' => ['required', 'string', 'max:1000']], ['rejectReason.required' => 'A reject comment is required.']);
        $this->act(fn () => $this->svc()->reject($this->record, auth()->user(), $this->rejectStage, $this->rejectReason));
        $this->reset(['showReject', 'rejectStage', 'rejectReason']);
        session()->flash('ok', '✓ Rejected — returned to originator');
    }

    // ── warehouse complete ──
    public function openWarehouse(): void
    {
        abort_unless($this->canWarehouse(), 403);
        $this->itemNumbers = $this->record->items->mapWithKeys(fn ($it) => [$it->id => $it->item_number ?? ''])->all();
        $this->prNumber = $this->record->pr_number ?? '';
        $this->warehouseNote = '';
        $this->resetErrorBag();
        $this->showWarehouse = true;
    }

    public function warehouseComplete(): void
    {
        abort_unless($this->canWarehouse(), 403);
        $this->validate([
            'itemNumbers.*' => ['nullable', 'string', 'max:64'],
            'prNumber' => ['nullable', 'string', 'max:64'],
            'warehouseNote' => ['nullable', 'string', 'max:1000'],
        ]);
        $this->act(fn () => $this->svc()->warehouseDone($this->record, auth()->user(), [
            'item_numbers' => $this->itemNumbers, 'pr_number' => $this->prNumber, 'warehouse_note' => $this->warehouseNote,
        ]));
        $this->reset(['showWarehouse', 'itemNumbers', 'prNumber', 'warehouseNote']);
        session()->flash('ok', '✓ Completed — item number & PR recorded, originator notified');
    }

    // ── draft edit (originator) ──
    public function openEdit(): void
    {
        abort_unless($this->isOriginator() && $this->record->status === 'draft', 403);
        $r = $this->record;
        $this->ef = [
            'hos_user_id' => $r->hos_user_id, 'manager_user_id' => $r->manager_user_id,
            'section_team' => $r->section_team, 'phone' => $r->phone, 'app_date' => $r->app_date?->toDateString(),
            'sub_assembly' => $r->sub_assembly, 'functional_system' => $r->functional_system, 'purpose' => $r->purpose,
        ];
        $this->ei = $r->items->map(fn ($it) => [
            'stock' => (bool) $it->stock, 'description' => $it->description, 'price_usd' => $it->price_usd,
            'qty_order' => $it->qty_order, 'unit' => $it->unit, 'min_qty' => $it->min_qty, 'max_qty' => $it->max_qty,
            'suggested_supplier' => $it->suggested_supplier, 'hazardous' => (bool) $it->hazardous,
            'criticality' => (bool) $it->criticality, 'special_storage' => $it->special_storage,
        ])->all();
        if (empty($this->ei)) {
            $this->ei = [$this->blankItem()];
        }
        $this->resetErrorBag();
        $this->showEdit = true;
    }

    protected function blankItem(): array
    {
        return ['stock' => false, 'description' => '', 'price_usd' => '', 'qty_order' => 1, 'unit' => '', 'min_qty' => '', 'max_qty' => '', 'suggested_supplier' => '', 'hazardous' => false, 'criticality' => false, 'special_storage' => 'Normal'];
    }

    public function addEditItem(): void
    {
        $this->ei[] = $this->blankItem();
    }

    public function removeEditItem(int $i): void
    {
        unset($this->ei[$i]);
        $this->ei = array_values($this->ei);
        if (empty($this->ei)) {
            $this->ei = [$this->blankItem()];
        }
    }

    public function saveEdit(): void
    {
        abort_unless($this->isOriginator() && $this->record->status === 'draft', 403);
        $this->validate([
            'ef.hos_user_id' => ['nullable', 'exists:users,id'],
            'ef.manager_user_id' => ['nullable', 'exists:users,id'],
            'ef.app_date' => ['required', 'date'],
            'ef.purpose' => ['nullable', 'string', 'max:2000'],
            'ei' => ['required', 'array', 'min:1'],
            'ei.*.description' => ['required', 'string', 'max:1000'],
            'ei.*.qty_order' => ['required', 'integer', 'min:1'],
            'ei.*.price_usd' => ['nullable', 'numeric', 'min:0'],
            'ei.*.special_storage' => ['nullable', 'in:Normal,Air Cond room'],
        ]);
        $hos = User::find($this->ef['hos_user_id'] ?? null);
        $mgr = User::find($this->ef['manager_user_id'] ?? null);
        $this->record->update([
            'hos_user_id' => $hos?->id, 'hos_name' => $hos ? ($hos->display_name ?? $hos->email) : null,
            'manager_user_id' => $mgr?->id, 'manager_name' => $mgr ? ($mgr->display_name ?? $mgr->email) : null,
            'section_team' => $this->ef['section_team'] ?: null, 'phone' => $this->ef['phone'] ?: null,
            'app_date' => $this->ef['app_date'], 'sub_assembly' => $this->ef['sub_assembly'] ?: null,
            'functional_system' => $this->ef['functional_system'] ?: null, 'purpose' => $this->ef['purpose'] ?: null,
            'updated_by' => auth()->id(),
        ]);
        $this->svc()->syncItems($this->record, $this->ei);
        $this->record->refresh();
        $this->reset(['showEdit', 'ef', 'ei']);
        session()->flash('ok', '✓ Draft updated');
    }

    public function render(): View
    {
        return view('livewire.ansi.show', [
            'record' => $this->record->load(['items', 'attachments', 'history', 'unit', 'department']),
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
            'people' => User::where('status', 'active')->orderBy('display_name')->get(['id', 'display_name', 'email']),
        ]);
    }
}
