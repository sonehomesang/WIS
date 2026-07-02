<?php

namespace App\Livewire\Equipment;

use App\Models\Equipment;
use App\Models\EquipmentInspection;
use App\Models\EquipmentPhoto;
use App\Models\Uom;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithFileUploads, WithPagination;

    /** ຮູບ ສູງສຸດ ຕໍ່ ເຄື່ອງ. */
    public const MAX_PHOTOS = 3;

    public string $search = '';

    public string $categoryFilter = '';

    public string $statusFilter = '';

    // Modal + form (register)
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $asset_code = '';

    public string $fixed_asset_no = '';

    public string $name = '';

    public string $category = '';

    public string $brand_model = '';

    public string $serial_no = '';

    public string $location = '';

    public string $responsible_name = '';

    // ຈຳນວນ ລວມ + ຫົວໜ່ວຍ + ຈຳນວນ ທີ່ ຊ່ອມ/ຢຸດ (ໃຊ້ງານ = quantity − repair − retired)
    public int $quantity = 1;

    public ?int $unit_id = null;

    public int $qtyRepair = 0;

    public int $qtyRetired = 0;

    public ?string $purchase_date = null;

    public string $notes = '';

    /** @var array ຮູບ ໃໝ່ ທີ່ ອັບໂຫຼດ (TemporaryUploadedFile) — ຈາກ ກ້ອງ ຫຼື ແກເລີຣີ */
    public array $newPhotos = [];

    /** @var array<int,array{id:int,url:string}> ຮູບ ທີ່ ບັນທຶກ ແລ້ວ (ຕອນ ແກ້ໄຂ) */
    public array $existingPhotos = [];

    // ── ແທັບ 2: ການ ກວດກາ (Inspection) ──
    public bool $showInspectionModal = false;

    public string $insSearch = '';

    public ?int $insEquipmentId = null;

    public string $insEquipmentLabel = '';

    public int $insEquipmentQty = 1;

    public ?string $insDate = null;

    public string $insInspector = '';

    public string $insResult = 'pass';

    public string $insNotes = '';

    public ?string $insNextDue = null;

    public $insPhoto = null;

    public bool $insUpdateStatus = false;

    public int $insRepair = 0;

    public int $insRetired = 0;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('equipment.view'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function newItem(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function editItem(int $id): void
    {
        $e = Equipment::findOrFail($id);
        $this->editingId = $e->id;
        $this->asset_code = $e->asset_code;
        $this->fixed_asset_no = $e->fixed_asset_no ?? '';
        $this->name = $e->name;
        $this->category = $e->category ?? '';
        $this->brand_model = $e->brand_model ?? '';
        $this->serial_no = $e->serial_no ?? '';
        $this->location = $e->location ?? '';
        $this->responsible_name = $e->responsible_name ?? '';
        $this->quantity = $e->quantity ?: 1;
        $this->unit_id = $e->unit_id;
        $b = $e->statusBreakdown();
        $this->qtyRepair = $b['repair'];
        $this->qtyRetired = $b['retired'];
        $this->purchase_date = $e->purchase_date?->toDateString();
        $this->notes = $e->notes ?? '';
        $this->existingPhotos = $e->photos->map(fn ($p) => ['id' => $p->id, 'url' => Storage::url($p->path)])->all();
        $this->newPhotos = [];
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('equipment.'.($this->editingId ? 'edit' : 'create')), 403);

        $data = $this->validate([
            'asset_code' => ['required', 'string', 'max:32', Rule::unique('equipment', 'asset_code')->ignore($this->editingId)],
            'fixed_asset_no' => ['nullable', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:256'],
            'category' => ['nullable', 'string', 'max:128'],
            'brand_model' => ['nullable', 'string', 'max:256'],
            'serial_no' => ['nullable', 'string', 'max:128'],
            'location' => ['nullable', 'string', 'max:128'],
            'responsible_name' => ['nullable', 'string', 'max:128'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'unit_id' => ['nullable', 'exists:uoms,id'],
            'qtyRepair' => ['integer', 'min:0'],
            'qtyRetired' => ['integer', 'min:0'],
            'purchase_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'newPhotos' => ['array', 'max:'.self::MAX_PHOTOS],
            'newPhotos.*' => ['image', 'max:4096'],
        ], [], ['newPhotos.*' => 'ຮູບ']);

        if (count($this->existingPhotos) + count($this->newPhotos) > self::MAX_PHOTOS) {
            $this->addError('newPhotos', 'ຮູບ ໄດ້ ສູງສຸດ '.self::MAX_PHOTOS.' ໃບ ຕໍ່ ເຄື່ອງ.');

            return;
        }

        // ໃຊ້ງານ = ຈຳນວນ ລວມ − ຊ່ອມ − ຢຸດ (ຕ້ອງ ບໍ່ ຕິດລົບ)
        $active = $this->quantity - $this->qtyRepair - $this->qtyRetired;
        if ($active < 0) {
            $this->addError('qtyRepair', 'ຊ່ອມແປງ + ຢຸດໃຊ້ ຕ້ອງ ບໍ່ ເກີນ ຈຳນວນ ລວມ.');

            return;
        }

        $attrs = [
            'asset_code' => $data['asset_code'],
            'fixed_asset_no' => $data['fixed_asset_no'] ?: null,
            'name' => $data['name'],
            'category' => $data['category'] ?: null,
            'brand_model' => $data['brand_model'] ?: null,
            'serial_no' => $data['serial_no'] ?: null,
            'location' => $data['location'] ?: null,
            'responsible_name' => $data['responsible_name'] ?: null,
            'quantity' => $this->quantity,
            'unit_id' => $data['unit_id'] ?: null,
            'status_counts' => ['active' => $active, 'repair' => $this->qtyRepair, 'retired' => $this->qtyRetired],
            'purchase_date' => $data['purchase_date'] ?: null,
            'notes' => $data['notes'] ?: null,
            'updated_by' => auth()->id(),
        ];

        if ($this->editingId) {
            $e = Equipment::findOrFail($this->editingId);
            $e->update($attrs);
        } else {
            $e = Equipment::create($attrs + ['created_by' => auth()->id()]);
        }

        $this->storePhotos($e);

        $this->showModal = false;
        $this->dispatch('saved');
    }

    /** ບັນທຶກ ຮູບ ໃໝ່ (ຈາກ ກ້ອງ/ແກເລີຣີ) ໃສ່ public disk. */
    protected function storePhotos(Equipment $e): void
    {
        if (empty($this->newPhotos)) {
            return;
        }
        $start = (int) $e->photos()->max('sort_order');
        foreach (array_values($this->newPhotos) as $i => $photo) {
            $path = $photo->store('equipment/'.$e->id, 'public');
            $e->photos()->create(['path' => $path, 'sort_order' => $start + $i + 1]);
        }
        $this->newPhotos = [];
    }

    public function removePhoto(int $photoId): void
    {
        abort_unless(auth()->user()->can('equipment.edit'), 403);
        if ($p = EquipmentPhoto::find($photoId)) {
            Storage::disk('public')->delete($p->path);
            $p->delete();
        }
        $this->existingPhotos = array_values(array_filter($this->existingPhotos, fn ($x) => $x['id'] !== $photoId));
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can('equipment.delete'), 403);
        Equipment::whereKey($id)->delete();
    }

    // ─────────── ແທັບ 2: ການ ກວດກາ ───────────

    public function newInspection(): void
    {
        abort_unless(auth()->user()->can('equipment.edit'), 403);
        $this->reset([
            'insSearch', 'insEquipmentId', 'insEquipmentLabel', 'insInspector',
            'insNotes', 'insNextDue', 'insPhoto', 'insUpdateStatus', 'insRepair', 'insRetired',
        ]);
        $this->insDate = now()->toDateString();
        $this->insResult = 'pass';
        $this->insEquipmentQty = 1;
        $this->resetValidation();
        $this->showInspectionModal = true;
    }

    public function pickInspectionEquipment(int $id): void
    {
        $e = Equipment::find($id);
        if (! $e) {
            return;
        }
        $this->insEquipmentId = $e->id;
        $this->insEquipmentLabel = $e->asset_code.' · '.$e->name;
        $this->insEquipmentQty = $e->quantity ?: 1;
        $b = $e->statusBreakdown();
        $this->insRepair = $b['repair'];
        $this->insRetired = $b['retired'];
        $this->insSearch = '';
    }

    public function saveInspection(): void
    {
        abort_unless(auth()->user()->can('equipment.edit'), 403);

        $this->validate([
            'insEquipmentId' => ['required', 'exists:equipment,id'],
            'insDate' => ['required', 'date'],
            'insInspector' => ['nullable', 'string', 'max:128'],
            'insResult' => ['required', 'in:pass,fail,follow_up'],
            'insNotes' => ['nullable', 'string', 'max:2000'],
            'insNextDue' => ['nullable', 'date'],
            'insPhoto' => ['nullable', 'image', 'max:8192'],
            'insRepair' => ['integer', 'min:0'],
            'insRetired' => ['integer', 'min:0'],
        ]);

        $e = Equipment::findOrFail($this->insEquipmentId);

        // ອັບເດດ ສະຖານະ ຈາກ ຜົນ ກວດກາ (ຖ້າ ເລືອກ)
        if ($this->insUpdateStatus) {
            $active = $e->quantity - $this->insRepair - $this->insRetired;
            if ($active < 0) {
                $this->addError('insRepair', 'ຊ່ອມແປງ + ຢຸດໃຊ້ ຕ້ອງ ບໍ່ ເກີນ ຈຳນວນ.');

                return;
            }
            $e->update(['status_counts' => ['active' => $active, 'repair' => $this->insRepair, 'retired' => $this->insRetired]]);
        }

        $path = $this->insPhoto ? $this->stampAndStore($this->insPhoto, 'equipment/inspections/'.$e->id) : null;

        $e->inspections()->create([
            'inspected_at' => $this->insDate,
            'inspector_name' => $this->insInspector ?: null,
            'result' => $this->insResult,
            'notes' => $this->insNotes ?: null,
            'next_due_date' => $this->insNextDue ?: null,
            'photo_path' => $path,
            'created_by' => auth()->id(),
        ]);

        $this->showInspectionModal = false;
        $this->dispatch('saved');
    }

    /** ຝັງ ວັນທີ+ເວລາ (ຕອນ upload) ໃສ່ ຮູບ ດ້ວຍ GD ແລ້ວ ບັນທຶກ; fallback ຖ້າ GD ລົ້ມ. */
    protected function stampAndStore($photo, string $dir): string
    {
        $stamp = now()->timezone('Asia/Vientiane')->format('Y-m-d H:i');
        try {
            $img = @imagecreatefromstring(file_get_contents($photo->getRealPath()));
            if ($img !== false) {
                $w = imagesx($img);
                $h = imagesy($img);
                $tw = imagefontwidth(5) * strlen($stamp);
                $th = imagefontheight(5);
                $tmp = imagecreatetruecolor($tw, $th);
                imagefill($tmp, 0, 0, imagecolorallocate($tmp, 0, 0, 0));
                imagestring($tmp, 5, 0, 0, $stamp, imagecolorallocate($tmp, 255, 255, 255));
                $scale = max(2, (int) floor($w / 500));
                $dw = $tw * $scale;
                $dh = $th * $scale;
                $pad = max(4, (int) round($dh * 0.35));
                $bar = imagecolorallocatealpha($img, 0, 0, 0, 55);
                imagefilledrectangle($img, 0, $h - $dh - 2 * $pad, $dw + 2 * $pad, $h, $bar);
                imagecopyresized($img, $tmp, $pad, $h - $dh - $pad, 0, 0, $dw, $dh, $tw, $th);
                imagedestroy($tmp);
                ob_start();
                imagejpeg($img, null, 85);
                $bytes = ob_get_clean();
                imagedestroy($img);
                $path = rtrim($dir, '/').'/'.uniqid('ins_').'.jpg';
                Storage::disk('public')->put($path, $bytes);

                return $path;
            }
        } catch (\Throwable $ex) {
            // fall through to plain store
        }

        return $photo->store($dir, 'public');
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->asset_code = '';
        $this->fixed_asset_no = '';
        $this->name = '';
        $this->category = '';
        $this->brand_model = '';
        $this->serial_no = '';
        $this->location = '';
        $this->responsible_name = '';
        $this->quantity = 1;
        $this->unit_id = null;
        $this->qtyRepair = 0;
        $this->qtyRetired = 0;
        $this->purchase_date = null;
        $this->notes = '';
        $this->newPhotos = [];
        $this->existingPhotos = [];
        $this->resetValidation();
    }

    public function render(): View
    {
        $items = Equipment::query()
            ->with(['photos', 'unit'])
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('asset_code', 'like', "%{$this->search}%")
                ->orWhere('serial_no', 'like', "%{$this->search}%")))
            ->when($this->categoryFilter, fn ($q) => $q->where('category', $this->categoryFilter))
            // ກັ່ນຕອງ ສະຖານະ: ມີ ≥1 ໜ່ວຍ ໃນ ສະຖານະ ນັ້ນ
            ->when($this->statusFilter, fn ($q) => $q->where('status_counts->'.$this->statusFilter, '>', 0))
            ->orderBy('asset_code')
            ->paginate(10);

        $insResults = $this->showInspectionModal && strlen($this->insSearch) >= 2
            ? Equipment::where(fn ($q) => $q->where('name', 'like', "%{$this->insSearch}%")
                ->orWhere('asset_code', 'like', "%{$this->insSearch}%"))
                ->orderBy('asset_code')->limit(8)->get()
            : collect();

        return view('livewire.equipment.index', [
            'items' => $items,
            'units' => Uom::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => Equipment::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'inspections' => EquipmentInspection::with('equipment')->orderByDesc('inspected_at')->orderByDesc('id')->limit(50)->get(),
            'insResults' => $insResults,
        ]);
    }
}
