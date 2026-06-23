<?php

namespace App\Livewire\Catalog;

use App\Models\Material;
use App\Models\MaterialImage;
use App\Models\Supplier;
use App\Models\Uom;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithFileUploads, WithPagination;

    public const MAX_PHOTOS = 10;

    public string $search = '';

    public string $supplierFilter = '';

    public string $categoryFilter = '';

    public string $statusFilter = '';

    // ── modal / form ──
    public bool $showModal = false;

    public ?int $editingId = null;

    public ?int $supplier_id = null;

    public string $material_nbr = '';

    public string $category = '';

    public string $description = '';

    public string $unit = '';

    public ?string $unit_price = null;

    public string $currency = 'THB';

    public string $lead_time = '';

    public string $contract_number = '';

    public ?string $contract_date = null;

    public ?string $contract_start_date = null;

    public ?string $contract_end_date = null;

    public string $remark = '';

    public bool $is_active = true;

    /** @var TemporaryUploadedFile[] */
    public array $newPhotos = [];

    // ── price-history viewer ──
    public bool $showHistory = false;

    public ?Material $historyMaterial = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('catalog.view'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    protected function isSupplierScoped(): bool
    {
        $u = auth()->user();

        return $u->hasRole('supplier') && ! $u->is_super_admin && ! $u->hasAnyRole(['admin', 'warehouse_staff']);
    }

    protected function scopedQuery()
    {
        $q = Material::query()->with(['supplier', 'images']);
        if ($this->isSupplierScoped()) {
            $q->where('supplier_id', auth()->user()->supplier_id);
        }

        return $q;
    }

    public function newItem(): void
    {
        abort_unless(auth()->user()->can('catalog.create'), 403);
        $this->resetForm();
        $this->showModal = true;
    }

    public function editItem(int $id): void
    {
        abort_unless(auth()->user()->can('catalog.edit'), 403);
        $m = $this->scopedQuery()->findOrFail($id);
        $this->editingId = $m->id;
        $this->supplier_id = $m->supplier_id;
        $this->material_nbr = $m->material_nbr ?? '';
        $this->category = $m->category;
        $this->description = $m->description;
        $this->unit = $m->unit ?? '';
        $this->unit_price = $m->unit_price !== null ? (string) $m->unit_price : null;
        $this->currency = $m->currency ?? 'THB';
        $this->lead_time = $m->lead_time ?? '';
        $this->contract_number = $m->contract_number ?? '';
        $this->contract_date = $m->contract_date?->toDateString();
        $this->contract_start_date = $m->contract_start_date?->toDateString();
        $this->contract_end_date = $m->contract_end_date?->toDateString();
        $this->remark = $m->remark ?? '';
        $this->is_active = (bool) $m->is_active;
        $this->newPhotos = [];
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('catalog.'.($this->editingId ? 'edit' : 'create')), 403);

        $data = $this->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'material_nbr' => ['nullable', 'string', 'max:64'],
            'category' => ['required', 'string', 'max:128'],
            'description' => ['required', 'string', 'max:1000'],
            'unit' => ['nullable', 'string', 'max:32'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'in:LAK,THB,USD'],
            'lead_time' => ['nullable', 'string', 'max:64'],
            'contract_number' => ['nullable', 'string', 'max:128'],
            'contract_date' => ['nullable', 'date'],
            'contract_start_date' => ['nullable', 'date'],
            'contract_end_date' => ['nullable', 'date'],
            'remark' => ['nullable', 'string', 'max:500'],
            'newPhotos.*' => ['image', 'max:5120'],
        ], [], ['supplier_id' => 'Supplier', 'category' => 'ປະເພດ', 'description' => 'ລາຍລະອຽດ']);

        // supplier-scoped user ບໍ່ໃຫ້ ກຳນົດ/ປ່ຽນ supplier ໄປ supplier ອື່ນ (ບັງຄັບເປັນຂອງຕົນ)
        $supplierId = $this->isSupplierScoped() ? auth()->user()->supplier_id : $data['supplier_id'];

        $attrs = [
            'supplier_id' => $supplierId,
            'material_nbr' => $data['material_nbr'] ?: null,
            'category' => $data['category'],
            'description' => $data['description'],
            'unit' => $data['unit'] ?: null,
            'unit_price' => ($data['unit_price'] !== null && $data['unit_price'] !== '') ? $data['unit_price'] : null,
            'currency' => $data['currency'],
            'lead_time' => $data['lead_time'] ?: null,
            'contract_number' => $data['contract_number'] ?: null,
            'contract_date' => $data['contract_date'] ?: null,
            'contract_start_date' => $data['contract_start_date'] ?: null,
            'contract_end_date' => $data['contract_end_date'] ?: null,
            'remark' => $data['remark'] ?: null,
            'is_active' => $this->is_active,
            'updated_by' => auth()->id(),
        ];

        if ($this->editingId) {
            $m = $this->scopedQuery()->findOrFail($this->editingId);
            $m->update($attrs);   // observer logs price change if unit_price differs
        } else {
            $m = Material::create($attrs + ['created_by' => auth()->id()]); // observer logs initial price
        }

        $start = $m->images()->max('sort_order') ?? -1;
        foreach (array_values($this->newPhotos) as $i => $file) {
            $path = $file->store("materials/{$m->id}", 'public');
            $m->images()->create(['path' => $path, 'sort_order' => $start + 1 + $i]);
        }

        session()->flash('ok', $this->editingId ? '✓ ບັນທຶກການແກ້ໄຂ' : '✓ ເພີ່ມສິນຄ້າແລ້ວ');
        $this->reset(['showModal']);
        $this->resetForm();
    }

    public function toggle(int $id): void
    {
        $m = $this->scopedQuery()->findOrFail($id);
        $target = ! $m->is_active;
        abort_unless(auth()->user()->can('catalog.'.($target ? 'activate' : 'deactivate')), 403);
        $m->update(['is_active' => $target, 'updated_by' => auth()->id()]);
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can('catalog.delete'), 403);
        $this->scopedQuery()->findOrFail($id)->delete();
        session()->flash('ok', '✓ ລຶບສິນຄ້າແລ້ວ');
    }

    public function removePhoto(int $photoId): void
    {
        abort_unless(auth()->user()->can('catalog.edit'), 403);
        $p = MaterialImage::find($photoId);
        if ($p && (! $this->isSupplierScoped() || $p->material->supplier_id === auth()->user()->supplier_id)) {
            Storage::disk('public')->delete($p->path);
            $p->delete();
        }
    }

    public function openHistory(int $id): void
    {
        $this->historyMaterial = $this->scopedQuery()->with('priceHistory')->findOrFail($id);
        $this->showHistory = true;
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->supplier_id = $this->isSupplierScoped() ? auth()->user()->supplier_id : null;
        $this->material_nbr = '';
        $this->category = '';
        $this->description = '';
        $this->unit = '';
        $this->unit_price = null;
        $this->currency = 'THB';
        $this->lead_time = '';
        $this->contract_number = '';
        $this->contract_date = null;
        $this->contract_start_date = null;
        $this->contract_end_date = null;
        $this->remark = '';
        $this->is_active = true;
        $this->newPhotos = [];
        $this->resetValidation();
    }

    public function render(): View
    {
        $items = $this->scopedQuery()
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w->where('description', 'like', "%{$this->search}%")
                ->orWhere('material_nbr', 'like', "%{$this->search}%")
                ->orWhere('category', 'like', "%{$this->search}%")))
            ->when($this->supplierFilter, fn ($q) => $q->where('supplier_id', $this->supplierFilter))
            ->when($this->categoryFilter, fn ($q) => $q->where('category', $this->categoryFilter))
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderByDesc('id')
            ->paginate(12);

        $total = $this->scopedQuery()->count();
        $inactive = $this->scopedQuery()->where('is_active', false)->count();

        return view('livewire.catalog.index', [
            'materials' => $items,
            'chips' => [
                ['key' => '', 'label' => 'ທັງໝົด', 'count' => $total, 'alert' => false],
                ['key' => 'active', 'label' => 'active', 'count' => $total - $inactive, 'alert' => false],
                ['key' => 'inactive', 'label' => 'inactive', 'count' => $inactive, 'alert' => false],
            ],
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => $this->scopedQuery()->distinct()->orderBy('category')->pluck('category')->filter()->values(),
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
            'canManage' => auth()->user()->can('catalog.create') || auth()->user()->can('catalog.edit'),
        ]);
    }
}
