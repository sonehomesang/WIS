<?php

namespace App\Livewire\Oga;

use App\Models\OutwardsGoodsAdvice;
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
        abort_unless(auth()->user()->can('oga.view'), 403);
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

        return $u->is_super_admin || $u->can('oga.edit');
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
        $r = OutwardsGoodsAdvice::onlyTrashed()->find($id);
        if ($r) {
            $u = auth()->user();
            $r->restore();
            $r->forceFill(['deleted_reason' => null, 'deleted_by' => null])->save();
            $r->history()->create([
                'action' => 'restore', 'status' => $r->status, 'user_id' => $u->id,
                'user_name' => $u->display_name ?? $u->email, 'comment' => 'restore', 'created_at' => now(),
            ]);
            session()->flash('ok', '✓ ກູ້คืນ '.$r->oga_number);
        }
    }

    protected function scopedQuery()
    {
        $u = auth()->user();
        $q = OutwardsGoodsAdvice::query()->with('supplier');

        if ($this->showDeleted && $this->canManageDeleted()) {
            $q->onlyTrashed();
        }

        if ($u->hasRole('supplier') && ! $u->is_super_admin && ! $u->hasAnyRole(['admin', 'warehouse_staff'])) {
            $q->where('supplier_id', $u->supplier_id);
        }

        return $q;
    }

    public function render(): View
    {
        $items = $this->scopedQuery()
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w->where('oga_number', 'like', "%{$this->search}%")
                ->orWhere('po_number', 'like', "%{$this->search}%")
                ->orWhere('dispatch_to_name', 'like', "%{$this->search}%")
                ->orWhere('source_da_number', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('id')
            ->paginate(9);

        return view('livewire.oga.index', [
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
            $chip('dispatched', 'ກຳລັງສົ່ງ', $counts['dispatched'] ?? 0, true),
            $chip('delivered', 'ສົ່ງເຖິງ', $counts['delivered'] ?? 0),
            $chip('returned', 'ສົ່ງกลับ', $counts['returned'] ?? 0),
            $chip('draft', 'draft', $counts['draft'] ?? 0),
            $chip('cancelled', 'ຍົກເລີກ', $counts['cancelled'] ?? 0),
        ];
    }
}
