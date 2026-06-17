<?php

namespace App\Livewire\Inventory;

use App\Models\Building;
use App\Models\InventoryItem;
use App\Models\Location;
use App\Models\Room;
use App\Models\Uom;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    public bool $showModal = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $description = '';
    public string $category = '';
    public string $brand = '';
    public string $model = '';
    public string $serial_number = '';
    public int $quantity = 0;
    public int $min_quantity = 0;
    public string $unit = '';
    public ?int $location_id = null;
    public ?int $building_id = null;
    public ?int $room_id = null;
    public string $shelf_label = '';
    public string $status = 'available';
    public bool $is_active = true;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('inventory.view'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedLocationId(): void
    {
        $this->building_id = null;
        $this->room_id = null;
    }

    public function updatedBuildingId(): void
    {
        $this->room_id = null;
    }

    public function newItem(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function editItem(int $id): void
    {
        $m = InventoryItem::findOrFail($id);
        $this->editingId = $m->id;
        $this->name = $m->name;
        $this->description = $m->description ?? '';
        $this->category = $m->category ?? '';
        $this->brand = $m->brand ?? '';
        $this->model = $m->model ?? '';
        $this->serial_number = $m->serial_number ?? '';
        $this->quantity = $m->quantity;
        $this->min_quantity = $m->min_quantity;
        $this->unit = $m->unit ?? '';
        $this->location_id = $m->location_id;
        $this->building_id = $m->building_id;
        $this->room_id = $m->room_id;
        $this->shelf_label = $m->shelf_label ?? '';
        $this->status = $m->status;
        $this->is_active = (bool) $m->is_active;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('inventory.'.($this->editingId ? 'edit' : 'create')), 403);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:256'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:128'],
            'brand' => ['nullable', 'string', 'max:128'],
            'model' => ['nullable', 'string', 'max:128'],
            'serial_number' => ['nullable', 'string', 'max:128'],
            'quantity' => ['required', 'integer', 'min:0'],
            'min_quantity' => ['required', 'integer', 'min:0'],
            'unit' => ['nullable', 'string', 'max:32'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'building_id' => ['nullable', 'exists:buildings,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'shelf_label' => ['nullable', 'string', 'max:64'],
            'status' => ['required', 'in:available,borrowed,maintenance,low-stock'],
            'is_active' => ['boolean'],
        ], [], ['name' => 'ຊື່']);

        $uid = auth()->id();
        $payload = collect($data)->map(fn ($v) => $v === '' ? null : $v)->toArray();
        $payload['is_active'] = $this->is_active;
        $payload['updated_by'] = $uid;

        if ($this->editingId) {
            InventoryItem::findOrFail($this->editingId)->update($payload);
        } else {
            $payload['slug'] = $this->uniqueSlug($data['name']);
            $payload['created_by'] = $uid;
            InventoryItem::create($payload);
        }

        $this->showModal = false;
        $this->dispatch('saved');
    }

    public function toggle(int $id): void
    {
        $m = InventoryItem::findOrFail($id);
        abort_unless(auth()->user()->can('inventory.'.($m->is_active ? 'deactivate' : 'activate')), 403);
        $m->update(['is_active' => ! $m->is_active, 'updated_by' => auth()->id()]);
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can('inventory.delete'), 403);
        InventoryItem::findOrFail($id)->delete();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->description = '';
        $this->category = '';
        $this->brand = '';
        $this->model = '';
        $this->serial_number = '';
        $this->quantity = 0;
        $this->min_quantity = 0;
        $this->unit = '';
        $this->location_id = null;
        $this->building_id = null;
        $this->room_id = null;
        $this->shelf_label = '';
        $this->status = 'available';
        $this->is_active = true;
        $this->resetValidation();
    }

    protected function uniqueSlug(string $base): string
    {
        $slug = Str::slug($base) ?: 'item';
        $original = $slug;
        $i = 2;
        while (DB::table('inventory_items')->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$i++;
        }

        return $slug;
    }

    public function render(): View
    {
        $items = InventoryItem::with(['location', 'building', 'room'])
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$this->search}%")
                ->orWhere('category', 'like', "%{$this->search}%")
                ->orWhere('brand', 'like', "%{$this->search}%")
                ->orWhere('serial_number', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.inventory.index', [
            'items' => $items,
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
            'formBuildings' => $this->location_id ? Building::where('location_id', $this->location_id)->where('is_active', true)->orderBy('name')->get() : collect(),
            'formRooms' => $this->building_id ? Room::where('building_id', $this->building_id)->where('is_active', true)->orderBy('name')->get() : collect(),
        ]);
    }
}
