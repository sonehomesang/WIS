<?php

namespace App\Livewire\Deposit;

use App\Models\DepositRecord;
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

    public string $typeFilter = '';

    public bool $showDeleted = false;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('deposit.view'), 403);
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

        return $u->is_super_admin || $u->can('deposit.edit');
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
        $r = DepositRecord::onlyTrashed()->find($id);
        if ($r) {
            $u = auth()->user();
            $r->restore();
            $r->forceFill(['deleted_reason' => null, 'deleted_by' => null])->save();
            $r->history()->create([
                'action' => 'restore', 'status' => $r->status, 'user_id' => $u->id,
                'user_name' => $u->display_name ?? $u->email, 'comment' => 'restore', 'created_at' => now(),
            ]);
            session()->flash('ok', '✓ ກູ້คืນລາຍການ '.$r->request_number);
        }
    }

    /** Visibility: admin/warehouse see all; ຄົນອື່ນ ເຫັນຂອງຕົນ. */
    protected function scopedQuery()
    {
        $u = auth()->user();
        $q = DepositRecord::query()->with(['items.photos', 'unit']);

        if ($this->showDeleted && $this->canManageDeleted()) {
            $q->onlyTrashed();
        }

        if (! ($u->is_super_admin || $u->hasAnyRole(['admin', 'warehouse_staff']))) {
            $q->where('owner_user_id', $u->id);
        }

        return $q;
    }

    public function render(): View
    {
        $items = $this->scopedQuery()
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w->where('request_number', 'like', "%{$this->search}%")
                ->orWhere('owner_name', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter, fn ($q) => $q->where('request_type', $this->typeFilter))
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.deposit.index', [
            'records' => $items,
            'canManageDeleted' => $this->canManageDeleted(),
            'chips' => $this->statusChips(),
        ]);
    }

    protected function statusChips(): array
    {
        $counts = $this->scopedQuery()->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');
        $chip = fn ($k, $l, $c, $a = false) => ['key' => $k, 'label' => $l, 'count' => $c, 'alert' => $a];

        return [
            $chip('', 'ທັງໝົด', $counts->sum()),
            $chip('submitted', 'ລໍຮັບ', $counts['submitted'] ?? 0, true),
            $chip('accepted', 'ຮັບແລ້ວ', $counts['accepted'] ?? 0),
            $chip('stored', 'ເກັບໄວ້', $counts['stored'] ?? 0),
            $chip('needs_fix', 'ຕ້ອງแก้', $counts['needs_fix'] ?? 0, true),
            $chip('claimed', 'ເອົາคืนแล้ว', $counts['claimed'] ?? 0),
            $chip('draft', 'draft', $counts['draft'] ?? 0),
            $chip('cancelled', 'ຍົກເລີກ', $counts['cancelled'] ?? 0),
        ];
    }
}
