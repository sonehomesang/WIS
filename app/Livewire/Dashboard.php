<?php

namespace App\Livewire;

use App\Models\BorrowRecord;
use App\Models\DepositRecord;
use App\Models\DiscrepancyAdvice;
use App\Models\InventoryItem;
use App\Models\Material;
use App\Models\MaterialRequest;
use App\Models\OutwardsGoodsAdvice;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    /** Per-user widget toggles (merged over User::DASHBOARD_DEFAULTS). */
    public array $prefs = [];

    public bool $showSettings = false;

    public function mount(): void
    {
        $this->prefs = auth()->user()->dashboardPrefs();
    }

    /** Flip one widget on/off and persist to the user. */
    public function toggle(string $key): void
    {
        if (! array_key_exists($key, User::DASHBOARD_DEFAULTS)) {
            return;
        }
        $this->prefs[$key] = ! ($this->prefs[$key] ?? true);
        $u = auth()->user();
        $u->dashboard_prefs = $this->prefs;
        $u->save();
    }

    protected function isStaff(): bool
    {
        $u = auth()->user();

        return $u->is_super_admin || $u->hasAnyRole(['admin', 'warehouse_staff']);
    }

    /** @return array<int,array> summary KPI cards (gated by *.view permission). */
    protected function cards(): array
    {
        $u = auth()->user();
        $staff = $this->isStaff();
        $cards = [];

        if ($u->can('borrow.view')) {
            $q = BorrowRecord::query()->when(! $staff, fn ($w) => $w->where('borrower_user_id', $u->id));
            $active = (clone $q)->whereIn('status', ['active', 'overdue'])->count();
            $overdue = (clone $q)->where('status', 'active')->whereDate('planned_return_date', '<', Carbon::today())->count();
            $cards[] = ['label' => 'ການຢືມ (Borrow)', 'route' => 'borrow', 'tone' => 'indigo',
                'big' => $active, 'big_label' => 'ກຳລັງໃຊ້', 'sub' => $overdue > 0 ? "⏰ ເກີນກຳນົດ {$overdue}" : 'ບໍ່ມີເກີນກຳນົດ', 'alert' => $overdue > 0];
        }

        if ($u->can('request.view')) {
            $q = MaterialRequest::query();
            if ($u->hasRole('supplier') && ! $staff) {
                $q->where('assigned_supplier_id', $u->supplier_id);
            } elseif (! $staff) {
                $q->where('requester_user_id', $u->id);
            }
            $open = (clone $q)->whereNotIn('status', ['completed', 'rejected', 'cancelled'])->count();
            $pending = (clone $q)->where('status', 'submitted')->count();
            $cards[] = ['label' => 'ໃບເບີກ (Request)', 'route' => 'request', 'tone' => 'sky',
                'big' => $open, 'big_label' => 'ກຳລັງดำเนิน', 'sub' => $pending > 0 ? "ລໍ approve {$pending}" : '—', 'alert' => false];
        }

        if ($u->can('deposit.view')) {
            $q = DepositRecord::query()->when(! $staff, fn ($w) => $w->where('owner_user_id', $u->id));
            $stored = (clone $q)->where('status', 'stored')->count();
            $needsFix = (clone $q)->where('status', 'needs_fix')->count();
            $cards[] = ['label' => 'ການຝາກ (Deposit)', 'route' => 'deposit', 'tone' => 'emerald',
                'big' => $stored, 'big_label' => 'ເກັບໄວ້', 'sub' => $needsFix > 0 ? "⚠ ຕ້ອງແກ້ {$needsFix}" : '—', 'alert' => $needsFix > 0];
        }

        if ($u->can('da.view')) {
            $open = DiscrepancyAdvice::whereNotIn('status', ['resolved', 'cancelled'])->count();
            $cards[] = ['label' => 'DA Claims', 'route' => 'da', 'tone' => 'violet',
                'big' => $open, 'big_label' => 'ຍັງไม่ปิด', 'sub' => '—', 'alert' => $open > 0];
        }

        if ($u->can('oga.view')) {
            $q = OutwardsGoodsAdvice::query();
            if ($u->hasRole('supplier') && ! $staff) {
                $q->where('supplier_id', $u->supplier_id);
            }
            $transit = (clone $q)->where('status', 'dispatched')->count();
            $cards[] = ['label' => 'OGA', 'route' => 'oga', 'tone' => 'amber',
                'big' => $transit, 'big_label' => 'ກຳລັງສົ່ງ', 'sub' => '—', 'alert' => false];
        }

        if ($u->can('inventory.view')) {
            $total = InventoryItem::count();
            $low = InventoryItem::whereColumn('quantity', '<=', 'min_quantity')->where('min_quantity', '>', 0)->count();
            $cards[] = ['label' => 'ສາງເຄື່ອງ (Inventory)', 'route' => 'inventory', 'tone' => 'gray',
                'big' => $total, 'big_label' => 'ລາຍການ', 'sub' => $low > 0 ? "🔻 ໃກ້ໝົດ {$low}" : '—', 'alert' => $low > 0];
        }

        if ($u->can('catalog.view')) {
            $q = Material::query();
            if ($u->hasRole('supplier') && ! $staff) {
                $q->where('supplier_id', $u->supplier_id);
            }
            $cards[] = ['label' => 'Shops Material', 'route' => 'catalog', 'tone' => 'gray',
                'big' => (clone $q)->count(), 'big_label' => 'ສິນຄ້າ', 'sub' => (clone $q)->where('is_active', false)->count().' inactive', 'alert' => false];
        }

        return $cards;
    }

    /** @return array<int,array{label:string,count:int,route:string,alert:bool}> role-specific to-do rows (count>0 only). */
    protected function actionRows(): array
    {
        $u = auth()->user();
        $uid = $u->id;
        $staff = $this->isStaff();
        $supplier = $u->hasRole('supplier') && ! $staff;
        $rows = [];

        if ($staff) {
            $rows[] = ['label' => 'ໃບເບີກ ລໍ້ dispatch', 'route' => 'request',
                'count' => MaterialRequest::whereIn('status', ['approved', 'validated'])->count(), 'alert' => false];
            $rows[] = ['label' => 'ໃບເບີກ ລໍ້ receive', 'route' => 'request',
                'count' => MaterialRequest::where('status', 'dispatched')->count(), 'alert' => false];
            $rows[] = ['label' => 'ການຢືມ ເກີນກຳນົດ', 'route' => 'borrow',
                'count' => BorrowRecord::where('status', 'active')->whereDate('planned_return_date', '<', Carbon::today())->count(), 'alert' => true];
            $rows[] = ['label' => 'ການຝາກ ລໍ້ຮັບເຂົ້າ', 'route' => 'deposit',
                'count' => DepositRecord::whereIn('status', ['submitted', 'accepted'])->count(), 'alert' => false];
            $rows[] = ['label' => 'DA ລໍ້ approve', 'route' => 'da',
                'count' => DiscrepancyAdvice::whereIn('status', ['submitted', 'purchasing_review', 'pending_approval'])->count(), 'alert' => false];
            $rows[] = ['label' => 'OGA ລໍ້ dispatch', 'route' => 'oga',
                'count' => OutwardsGoodsAdvice::where('status', 'draft')->count(), 'alert' => false];
        }

        if ($u->hasRole('approver') && ! $staff) {
            $rows[] = ['label' => 'ໃບເບີກ ລໍ້ approve', 'route' => 'request',
                'count' => MaterialRequest::whereIn('status', ['submitted', 'acknowledged'])->count(), 'alert' => true];
            $rows[] = ['label' => 'ການຢືມ ລໍ້ approve', 'route' => 'borrow',
                'count' => BorrowRecord::whereIn('status', ['draft', 'acknowledged'])->count(), 'alert' => false];
        }

        if ($supplier) {
            $sid = $u->supplier_id;
            $rows[] = ['label' => 'orders ລໍ້ validate', 'route' => 'request',
                'count' => MaterialRequest::where('assigned_supplier_id', $sid)->where('status', 'approved')->count(), 'alert' => true];
            $rows[] = ['label' => 'orders ລໍ້ dispatch', 'route' => 'request',
                'count' => MaterialRequest::where('assigned_supplier_id', $sid)->where('status', 'validated')->count(), 'alert' => false];
        }

        // My-side rows — everyone who can create sees their own pending work.
        if ($u->can('borrow.view') || $u->can('request.view') || $u->can('deposit.view')) {
            $myDrafts = BorrowRecord::where('borrower_user_id', $uid)->where('status', 'draft')->count()
                + MaterialRequest::where('requester_user_id', $uid)->where('status', 'draft')->count()
                + DepositRecord::where('owner_user_id', $uid)->where('status', 'draft')->count();
            $rows[] = ['label' => 'ຮ່າງຂອງຂ້ອຍ ລໍ້ submit', 'route' => 'borrow', 'count' => $myDrafts, 'alert' => false];

            $myOverdue = BorrowRecord::where('borrower_user_id', $uid)->where('status', 'active')
                ->whereDate('planned_return_date', '<', Carbon::today())->count();
            $rows[] = ['label' => 'ການຢືມຂອງຂ້ອຍ ເກີນກຳນົດ', 'route' => 'borrow', 'count' => $myOverdue, 'alert' => true];
        }

        return array_values(array_filter($rows, fn ($r) => $r['count'] > 0));
    }

    /** @return array<int,array> last 10 status transitions across workflows (role-scoped). */
    protected function recentActivity(): array
    {
        $u = auth()->user();
        $staff = $this->isStaff();
        $supplier = $u->hasRole('supplier') && ! $staff;
        $items = [];

        $pull = function (string $hTable, string $pTable, string $numberCol, string $kind, string $route, ?string $ownerCol, $ownerVal) {
            $q = DB::table("{$hTable} as h")
                ->join("{$pTable} as p", 'p.id', '=', 'h.record_id')
                ->whereNull('p.deleted_at')
                ->selectRaw("h.status, h.user_name, h.created_at, p.{$numberCol} as number")
                ->orderByDesc('h.id')->limit(15);
            if ($ownerCol !== null) {
                $q->where("p.{$ownerCol}", $ownerVal);
            }

            return $q->get()->map(fn ($r) => [
                'kind' => $kind, 'route' => $route, 'number' => $r->number,
                'status' => $r->status, 'actor' => $r->user_name ?? '—',
                'when' => $r->created_at ? Carbon::parse($r->created_at) : null,
            ])->all();
        };

        if ($u->can('borrow.view')) {
            $items = array_merge($items, $pull('borrow_history', 'borrow_records', 'request_number', 'borrow', 'borrow', $staff ? null : 'borrower_user_id', $u->id));
        }
        if ($u->can('request.view')) {
            [$col, $val] = $staff ? [null, null] : ($supplier ? ['assigned_supplier_id', $u->supplier_id] : ['requester_user_id', $u->id]);
            $items = array_merge($items, $pull('material_request_history', 'material_requests', 'request_number', 'request', 'request', $col, $val));
        }
        if ($u->can('deposit.view')) {
            $items = array_merge($items, $pull('deposit_history', 'deposit_records', 'request_number', 'deposit', 'deposit', $staff ? null : 'owner_user_id', $u->id));
        }
        if ($u->can('da.view') && $staff) {
            $items = array_merge($items, $pull('discrepancy_advice_history', 'discrepancy_advices', 'da_number', 'da', 'da', null, null));
        }
        if ($u->can('oga.view')) {
            $items = array_merge($items, $pull('oga_history', 'outwards_goods_advices', 'oga_number', 'oga', 'oga', $supplier ? 'supplier_id' : null, $u->supplier_id));
        }

        usort($items, fn ($a, $b) => ($b['when']?->timestamp ?? 0) <=> ($a['when']?->timestamp ?? 0));

        return array_slice($items, 0, 6);
    }

    /** Chart data for staff: request-status distribution + 6-month created counts. */
    protected function chartData(): array
    {
        $statusCounts = MaterialRequest::select('status', DB::raw('count(*) as c'))
            ->groupBy('status')->pluck('c', 'status')->all();

        $months = [];
        $borrow = $request = $deposit = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = Carbon::today()->subMonths($i);
            $start = $m->copy()->startOfMonth();
            $end = $m->copy()->endOfMonth();
            $months[] = $m->format('M');
            $borrow[] = BorrowRecord::whereBetween('created_at', [$start, $end])->count();
            $request[] = MaterialRequest::whereBetween('created_at', [$start, $end])->count();
            $deposit[] = DepositRecord::whereBetween('created_at', [$start, $end])->count();
        }

        return [
            'pie' => ['labels' => array_keys($statusCounts), 'data' => array_values($statusCounts)],
            'bar' => ['labels' => $months, 'borrow' => $borrow, 'request' => $request, 'deposit' => $deposit],
        ];
    }

    public function render(): View
    {
        $prefs = $this->prefs;
        $staff = $this->isStaff();

        return view('livewire.dashboard', [
            'prefs' => $prefs,
            'cards' => ($prefs['kpi'] ?? true) ? $this->cards() : [],
            'actionRows' => ($prefs['queue'] ?? true) ? $this->actionRows() : [],
            'activity' => ($prefs['activity'] ?? true) ? $this->recentActivity() : [],
            'showCharts' => $staff && ($prefs['charts'] ?? true),
            'chart' => ($staff && ($prefs['charts'] ?? true)) ? $this->chartData() : null,
        ]);
    }
}
