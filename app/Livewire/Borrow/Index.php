<?php

namespace App\Livewire\Borrow;

use App\Models\BorrowRecord;
use App\Models\Notification;
use App\Models\Setting;
use App\Services\NotificationService;
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

    /**
     * Daily check — ສ້າງ notification ໃຫ້ຜູ້ຢືມ ສຳລັບລາຍການ ໃກ້/ເກີນ ກຳນົດคืน.
     * ເຄົາລົບ feature flag `notifications.borrow_reminder` + master switch.
     * ກັນ flood: 1 reminder/record/ມື້ (ກວດ notification ມື້ນີ້).
     */
    public function runDailyCheck(): void
    {
        abort_unless($this->isStaff(), 403);

        $tomorrow = Carbon::today()->addDay();
        $due = BorrowRecord::where('status', 'active')
            ->whereDate('planned_return_date', '<=', $tomorrow)
            ->get(['id', 'request_number', 'borrower_user_id', 'planned_return_date']);

        $flags = Setting::get('notifications', ['enabled' => true, 'borrow_reminder' => true]);
        $reminderOn = NotificationService::enabled() && ($flags['borrow_reminder'] ?? true);

        if (! $reminderOn) {
            session()->flash('ok', "⏰ ພົບ {$due->count()} ລາຍການ ໃກ້/ເກີນ ກຳນົດ — ການເຕືອນ ປິດຢູ່ (Settings › Notifications)");

            return;
        }

        $svc = app(NotificationService::class);
        $sent = 0;
        foreach ($due as $r) {
            if (! $r->borrower_user_id) {
                continue;
            }
            $link = route('borrow.show', $r);
            $already = Notification::where('user_id', $r->borrower_user_id)
                ->where('link', $link)->whereDate('created_at', Carbon::today())->exists();
            if ($already) {
                continue;
            }
            $svc->notifyTemplate($r->borrower_user_id, 'warning', 'borrow.reminder', [
                'number' => $r->request_number,
                'date' => Carbon::parse($r->planned_return_date)->format('d/m/Y'),
            ], $link);
            $sent++;
        }

        session()->flash('ok', "⏰ ພົບ {$due->count()} ລາຍການ ໃກ້/ເກີນ ກຳນົດ · ສົ່ງເຕືອນ {$sent} ລາຍການ");
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
            ->paginate(9);

        return view('livewire.borrow.index', [
            'records' => $items,
            'canManageDeleted' => $this->canManageDeleted(),
            'canDailyCheck' => $this->isStaff(),
            'chips' => $this->statusChips(),
        ]);
    }

    /** Sub-dashboard: ນັບ ຕໍ່ສະຖานะ (visibility scope, ບໍ່ນັບ deleted). */
    protected function statusChips(): array
    {
        $base = $this->scopedQuery();
        $counts = (clone $base)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');
        $overdue = (clone $base)->where('status', 'active')->whereDate('planned_return_date', '<', Carbon::today())->count();
        $chip = fn ($k, $l, $c, $a = false) => ['key' => $k, 'label' => $l, 'count' => $c, 'alert' => $a];

        return [
            $chip('', 'ທັງໝົด', $counts->sum()),
            $chip('active', 'ໃຊ້ຢູ່', $counts['active'] ?? 0),
            $chip('overdue', 'ເກີນກຳນົດ', $overdue, true),
            $chip('returned', 'ສົ່ງคืน', $counts['returned'] ?? 0),
            $chip('approved', 'ອະນຸมัด', $counts['approved'] ?? 0),
            $chip('draft', 'draft', $counts['draft'] ?? 0),
            $chip('cancelled', 'ຍົກເລີກ', $counts['cancelled'] ?? 0),
        ];
    }
}
