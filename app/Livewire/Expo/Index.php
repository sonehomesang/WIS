<?php

namespace App\Livewire\Expo;

use App\Models\ExpoEvent;
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
        abort_unless(auth()->user()->can('expo.view'), 403);
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

        return $u->is_super_admin || $u->can('expo.edit');
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
        $r = ExpoEvent::onlyTrashed()->find($id);
        if ($r) {
            $r->restore();
            $r->forceFill(['deleted_reason' => null, 'deleted_by' => null])->save();
            session()->flash('ok', '✓ ກູ້คืນ '.$r->expo_number);
        }
    }

    protected function baseQuery()
    {
        $q = ExpoEvent::query()->withCount(['companies', 'attendees']);
        if ($this->showDeleted && $this->canManageDeleted()) {
            $q->onlyTrashed();
        }

        return $q;
    }

    public function render(): View
    {
        $items = $this->baseQuery()
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w->where('expo_number', 'like', "%{$this->search}%")
                ->orWhere('title', 'like', "%{$this->search}%")
                ->orWhere('country', 'like', "%{$this->search}%")
                ->orWhere('city', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('start_date')->orderByDesc('id')
            ->paginate(15);

        $counts = ExpoEvent::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');
        $chip = fn ($k, $l, $c) => ['key' => $k, 'label' => $l, 'count' => $c, 'alert' => false];

        return view('livewire.expo.index', [
            'records' => $items,
            'canManageDeleted' => $this->canManageDeleted(),
            'chips' => [
                $chip('', 'ທັງໝົด', $counts->sum()),
                $chip('finalized', 'finalized', $counts['finalized'] ?? 0),
                $chip('draft', 'draft', $counts['draft'] ?? 0),
            ],
        ]);
    }
}
