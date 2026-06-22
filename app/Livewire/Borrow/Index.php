<?php

namespace App\Livewire\Borrow;

use App\Models\BorrowRecord;
use Illuminate\Support\Carbon;
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

    public string $fromDate = '';

    public string $toDate = '';

    /** ສະແດງ Deleted Log (onlyTrashed) ແທນລາຍການปົກກะຕิ. */
    public bool $showDeleted = false;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('borrow.view'), 403);
    }

    protected function canManageDeleted(): bool
    {
        $u = auth()->user();

        return $u->is_super_admin || $u->can('borrow.edit');
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
        $r = BorrowRecord::onlyTrashed()->find($id);
        if ($r) {
            $u = auth()->user();
            $r->restore();
            $r->forceFill(['deleted_reason' => null, 'deleted_by' => null])->save();
            $r->history()->create([
                'action' => 'restore', 'status' => $r->status, 'user_id' => $u->id,
                'user_name' => $u->display_name ?? $u->email, 'comment' => 'restore', 'created_at' => now(),
            ]);
            session()->flash('ok', '✓ ກູ້คืนລາຍການ '.$r->request_number);
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    /** Daily check — ນັບ overdue (active ກາຍກຳນົດ). Reminder ຈິງ = Phase 6.10. */
    public function runDailyCheck(): void
    {
        $overdue = BorrowRecord::where('status', 'active')
            ->whereDate('planned_return_date', '<', Carbon::today())->count();
        session()->flash('ok', $overdue > 0
            ? "⏰ ພົບ {$overdue} ລາຍການເກີນກຳນົດ (reminder ຈะ wire ຕอน 6.10)"
            : '✓ ບໍ່ມີລາຍການເກີນກຳນົດ');
    }

    /** Visibility: admin/warehouse see all; ຄົນອື່ນ ເຫັນຂອງຕົນ/ທີ່ assign ໃຫ້. */
    protected function scopedQuery()
    {
        $u = auth()->user();
        $q = BorrowRecord::query()->with(['items.inventoryItem.primaryPhoto', 'items.photos', 'unit']);

        if ($this->showDeleted && $this->canManageDeleted()) {
            $q->onlyTrashed();
        }

        if (! ($u->is_super_admin || $u->hasAnyRole(['admin', 'warehouse_staff']))) {
            $email = mb_strtolower($u->email);
            $q->where(fn ($w) => $w->where('borrower_user_id', $u->id)
                ->orWhere('approver_email', $email)
                ->orWhere('acknowledge_email', $email));
        }

        return $q;
    }

    public function render(): View
    {
        $items = $this->scopedQuery()
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w->where('request_number', 'like', "%{$this->search}%")
                ->orWhere('borrower_name', 'like', "%{$this->search}%")))
            ->when($this->statusFilter === 'overdue',
                fn ($q) => $q->where('status', 'active')->whereDate('planned_return_date', '<', Carbon::today()))
            ->when($this->statusFilter && $this->statusFilter !== 'overdue',
                fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter, fn ($q) => $q->where('borrow_type', $this->typeFilter))
            ->when($this->fromDate, fn ($q) => $q->whereDate('borrow_date', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('borrow_date', '<=', $this->toDate))
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.borrow.index', [
            'records' => $items,
            'canManageDeleted' => $this->canManageDeleted(),
        ]);
    }
}
