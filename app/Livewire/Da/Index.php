<?php

namespace App\Livewire\Da;

use App\Models\DiscrepancyAdvice;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public bool $showDeleted = false;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('da.view'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    protected function canManageDeleted(): bool
    {
        $u = auth()->user();

        return $u->is_super_admin || $u->can('da.edit');
    }

    public function toggleDeleted(): void
    {
        abort_unless($this->canManageDeleted(), 403);
        $this->showDeleted = ! $this->showDeleted;
        $this->resetPage();
    }

    public function restore(int $id): void
    {
        abort_unless($this->canManageDeleted(), 403);
        $r = DiscrepancyAdvice::onlyTrashed()->find($id);
        if ($r) {
            $u = auth()->user();
            $r->restore();
            $r->forceFill(['deleted_reason' => null, 'deleted_by' => null])->save();
            $r->history()->create([
                'action' => 'restore', 'status' => $r->status, 'user_id' => $u->id,
                'user_name' => $u->display_name ?? $u->email, 'comment' => 'restore', 'created_at' => now(),
            ]);
            session()->flash('ok', '✓ ກູ້คืນ '.$r->da_number);
        }
    }

    protected function scopedQuery()
    {
        $u = auth()->user();
        $q = DiscrepancyAdvice::query()->with('supplier');

        if ($this->showDeleted && $this->canManageDeleted()) {
            $q->onlyTrashed();
        }

        if (! ($u->is_super_admin || $u->hasAnyRole(['admin', 'warehouse_staff', 'approver', 'line_manager']))) {
            $q->where('raised_by', $u->id);
        }

        return $q;
    }

    public function render(): View
    {
        $items = $this->scopedQuery()
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w->where('da_number', 'like', "%{$this->search}%")
                ->orWhere('po_number', 'like', "%{$this->search}%")
                ->orWhere('supplier_name', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.da.index', [
            'records' => $items,
            'canManageDeleted' => $this->canManageDeleted(),
        ]);
    }
}
