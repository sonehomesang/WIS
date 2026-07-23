<?php

namespace App\Livewire\Borrow;

use App\Models\BorrowRecord;
use App\Services\BorrowReminderService;
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

    /** ສະແດງ Deleted Log (onlyTrashed) ແທນລາຍການປົກກະຕິ. */
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

    /** Org-wide actions (daily reminder sweep) — staff only. */
    protected function isStaff(): bool
    {
        $u = auth()->user();

        return $u->is_super_admin || $u->hasAnyRole(['admin', 'warehouse_staff']);
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
            session()->flash('ok', '✓ ກູ້ຄືນລາຍການ '.$r->request_number);
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

    /**
     * Daily check — ສ້າງ notification ໃຫ້ຜູ້ຢືມ ສຳລັບລາຍການ ໃກ້/ເກີນ ກຳນົດຄືນ.
     * ເຄົາລົບ feature flag `notifications.borrow_reminder` + master switch.
     * ກັນ flood: 1 reminder/record/ມື້ (ກວດ notification ມື້ນີ້).
     */
    public function runDailyCheck(): void
    {
        abort_unless($this->isStaff(), 403);

        $r = app(BorrowReminderService::class)->sweep();

        session()->flash('ok', $r['enabled']
            ? "⏰ ພົບ {$r['due']} ລາຍການ ໃກ້/ເກີນ ກຳນົດ · ສົ່ງເຕືອນ {$r['sent']} ລາຍການ"
            : "⏰ ພົບ {$r['due']} ລາຍການ ໃກ້/ເກີນ ກຳນົດ — ການເຕືອນ ປິດຢູ່ (Settings › Notifications)");
    }

    /** Visibility: admin/warehouse see all; ຄົນອື່ນ ເຫັນຂອງຕົນ/ທີ່ assign ໃຫ້. */
    protected function scopedQuery()
    {
        $u = auth()->user();
        $q = BorrowRecord::query()->with(['items.inventoryItem.primaryPhoto', 'items.photos', 'unit']);

        if ($this->showDeleted && $this->canManageDeleted()) {
            $q->onlyTrashed();
        }

        if ($u->is_super_admin || $u->hasAnyRole(['admin', 'warehouse_staff'])) {
            return $q;   // ເຫັນ ໝົດ
        }
        if ($u->transactionScope() === 'department' && $u->department_id) {
            return $q->where('borrower_dept_id', $u->department_id);   // ໝົດ ພະແນກ ຕົນ
        }
        $email = mb_strtolower($u->email);
        $q->where(fn ($w) => $w->where('borrower_user_id', $u->id)
            ->orWhere('approver_email', $email)
            ->orWhere('acknowledge_email', $email));

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
            ->paginate(9);

        return view('livewire.borrow.index', [
            'records' => $items,
            'canManageDeleted' => $this->canManageDeleted(),
            'canDailyCheck' => $this->isStaff(),
            'chips' => $this->statusChips(),
        ]);
    }

    /** Sub-dashboard: ນັບ ຕໍ່ສະຖານະ (visibility scope, ບໍ່ນັບ deleted). */
    protected function statusChips(): array
    {
        $base = $this->scopedQuery();
        $counts = (clone $base)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');
        $overdue = (clone $base)->where('status', 'active')->whereDate('planned_return_date', '<', Carbon::today())->count();
        $chip = fn ($k, $l, $c, $a = false) => ['key' => $k, 'label' => $l, 'count' => $c, 'alert' => $a];

        return [
            $chip('', 'ທັງໝົດ', $counts->sum()),
            $chip('active', 'ໃຊ້ຢູ່', $counts['active'] ?? 0),
            $chip('overdue', 'ເກີນກຳນົດ', $overdue, true),
            $chip('returned', 'ສົ່ງຄືນ', $counts['returned'] ?? 0),
            $chip('approved', 'ອະນຸມັດ', $counts['approved'] ?? 0),
            $chip('draft', 'draft', $counts['draft'] ?? 0),
            $chip('cancelled', 'ຍົກເລີກ', $counts['cancelled'] ?? 0),
        ];
    }
}
