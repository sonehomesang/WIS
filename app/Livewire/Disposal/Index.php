<?php

namespace App\Livewire\Disposal;

use App\Livewire\Concerns\SoftDeletesWithReason;
use App\Models\DisposalRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use SoftDeletesWithReason, WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('disposal.view'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    // ── delete-with-reason (trait) ──
    protected function deleteModelClass(): string
    {
        return DisposalRecord::class;
    }

    protected function deletePermission(): string
    {
        return 'disposal.delete';
    }

    protected function deleteLabel(Model $record): string
    {
        return $record->request_number;
    }

    protected function deleteNoun(): string
    {
        return 'ໃບ ຈຳໜ່າຍ';
    }

    /** ຄັດ ຕາມ scope: warehouse/approver/admin ເຫັນ ໝົດ · dept-admin ເຫັນ ພະແນກ ຕົນ · ອື່ນ ເຫັນ ຂອງ ຕົນ. */
    protected function scopeFor(Builder $q): Builder
    {
        $u = auth()->user();
        if ($u->is_super_admin || $u->hasAnyRole(['admin', 'warehouse_staff', 'approver', 'line_manager'])) {
            return $q;
        }
        if ($u->hasRole('department_admin')) {
            return $q->where('department_id', $u->department_id);
        }

        return $q->where('prepared_by_user_id', $u->id);
    }

    /** ລຶບ/ກູ້ຄືນ ຕ້ອງ ຢູ່ ໃນ scope ດຽວ ກັບ ການ ເບິ່ງ — ກັນ dept/self-scoped ຈັດການ ໃບ ພະແນກ ອື່ນ (IDOR). */
    protected function deleteGuard(Model $record): void
    {
        $u = auth()->user();
        if ($u->is_super_admin || $u->hasAnyRole(['admin', 'warehouse_staff', 'approver', 'line_manager'])) {
            return;
        }
        $ok = $u->hasRole('department_admin')
            ? $record->department_id === $u->department_id
            : $record->prepared_by_user_id === $u->id;
        abort_unless($ok, 403);
    }

    public function render(): View
    {
        $showingDeleted = $this->showDeleted && $this->canManageDeleted();

        $records = DisposalRecord::query()
            ->with(['preparedBy', 'department.unit', 'items'])
            ->withCount('items')
            ->when($showingDeleted, fn ($q) => $q->onlyTrashed()->with('deletedBy'))
            ->where(fn ($q) => $this->scopeFor($q))
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('request_number', 'like', "%{$this->search}%")
                ->orWhere('title', 'like', "%{$this->search}%")
                ->orWhere('prepared_by_name', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($showingDeleted, fn ($q) => $q->orderByDesc('deleted_at'), fn ($q) => $q->orderByDesc('created_at'))
            ->paginate(5);

        return view('livewire.disposal.index', [
            'records' => $records,
            'statusLabels' => DisposalRecord::STATUS_LABELS,
            'canManageDeleted' => $this->canManageDeleted(),
        ]);
    }
}
