<?php

namespace App\Livewire\Request;

use App\Models\MaterialRequest;
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
        abort_unless(auth()->user()->can('request.view'), 403);
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

        return $u->is_super_admin || $u->can('request.edit');
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
        $r = MaterialRequest::onlyTrashed()->find($id);
        if ($r) {
            $u = auth()->user();
            $r->restore();
            $r->forceFill(['deleted_reason' => null, 'deleted_by' => null])->save();
            $r->history()->create([
                'action' => 'restore', 'status' => $r->status, 'user_id' => $u->id,
                'user_name' => $u->display_name ?? $u->email, 'comment' => 'restore', 'created_at' => now(),
            ]);
            session()->flash('ok', '✓ ກູ້คืນ '.$r->request_number);
        }
    }

    /** Visibility: staff all · approver assigned · supplier own-supplier · requester own. */
    protected function scopedQuery()
    {
        $u = auth()->user();
        $q = MaterialRequest::query()->with(['supplier', 'unit']);

        if ($this->showDeleted && $this->canManageDeleted()) {
            $q->onlyTrashed();
        }

        if ($u->is_super_admin || $u->hasAnyRole(['admin', 'warehouse_staff'])) {
            return $q;
        }
        if ($u->hasRole('supplier')) {
            return $q->where('assigned_supplier_id', $u->supplier_id);
        }
        if ($u->hasAnyRole(['approver', 'line_manager'])) {
            return $q->where(fn ($w) => $w->where('approver_user_id', $u->id)->orWhere('requester_user_id', $u->id));
        }

        return $q->where('requester_user_id', $u->id);
    }

    public function render(): View
    {
        $items = $this->scopedQuery()
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w->where('request_number', 'like', "%{$this->search}%")
                ->orWhere('requester_name', 'like', "%{$this->search}%")
                ->orWhere('purpose', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.request.index', [
            'records' => $items,
            'canManageDeleted' => $this->canManageDeleted(),
        ]);
    }
}
