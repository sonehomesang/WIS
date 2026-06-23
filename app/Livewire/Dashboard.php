<?php

namespace App\Livewire;

use App\Models\BorrowRecord;
use App\Models\DepositRecord;
use App\Models\DiscrepancyAdvice;
use App\Models\InventoryItem;
use App\Models\Material;
use App\Models\MaterialRequest;
use App\Models\OutwardsGoodsAdvice;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    protected function isStaff(): bool
    {
        $u = auth()->user();

        return $u->is_super_admin || $u->hasAnyRole(['admin', 'warehouse_staff']);
    }

    /** @return array<int,array> summary cards (gated by *.view permission). */
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

    public function render(): View
    {
        return view('livewire.dashboard', ['cards' => $this->cards()]);
    }
}
