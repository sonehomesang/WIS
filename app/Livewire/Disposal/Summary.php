<?php

namespace App\Livewire\Disposal;

use App\Models\Department;
use App\Models\DisposalItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** ລິສ ລວມ — ລາຍການ ຈຳໜ່າຍ ທັງໝົດ ຕາມ ໄລຍະ/ພະແນກ/ສະຖານະ + ຍອດ ລວມ. */
#[Layout('layouts.app')]
class Summary extends Component
{
    public string $from = '';

    public string $to = '';

    public ?int $department_id = null;

    public string $status = 'disposed';  // disposed | approved | all

    public function mount(): void
    {
        abort_unless(auth()->user()->can('disposal.view'), 403);
        $this->from = now()->startOfYear()->toDateString();
        $this->to = now()->toDateString();
    }

    /** ຄັດ ພະແນກ ຕາມ scope: dept-admin ຖືກ ບັງຄັບ ໃຫ້ ພະແນກ ຕົນ (ຫ້າມ ເບິ່ງ ພະແນກ ອື່ນ). */
    public static function scopedDeptId($requested): ?int
    {
        $u = auth()->user();
        if ($u && $u->hasRole('department_admin') && ! $u->is_super_admin && ! $u->hasAnyRole(['admin', 'warehouse_staff', 'approver', 'line_manager'])) {
            return $u->department_id;
        }

        return $requested ? (int) $requested : null;
    }

    /** @return array<int, string> */
    public static function statusesFor(string $status): array
    {
        return match ($status) {
            'approved' => ['approved'],
            'all' => ['approved', 'disposed'],
            default => ['disposed'],
        };
    }

    /** Query ຮ່ວມ — ໃຊ້ ທັງ ໜ້າ ແລະ PDF. */
    public static function itemsQuery(?string $from, ?string $to, ?int $deptId, string $status): Builder
    {
        return DisposalItem::query()
            ->whereHas('record', function ($q) use ($from, $to, $deptId, $status) {
                $q->whereIn('status', self::statusesFor($status))
                    ->when($from, fn ($x) => $x->whereDate('created_at', '>=', $from))
                    ->when($to, fn ($x) => $x->whereDate('created_at', '<=', $to))
                    ->when($deptId, fn ($x) => $x->where('department_id', $deptId));
            })
            ->with('record')
            ->orderBy('record_id');
    }

    public function render(): View
    {
        $items = self::itemsQuery($this->from ?: null, $this->to ?: null, self::scopedDeptId($this->department_id), $this->status)->get();

        return view('livewire.disposal.summary', [
            'items' => $items,
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'totalQty' => $items->sum('qty'),
            'totalValue' => $items->sum(fn ($it) => (float) $it->estimated_value),
        ]);
    }
}
