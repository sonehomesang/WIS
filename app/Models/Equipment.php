<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ທະບຽນ ເຄື່ອງມື/ເຄື່ອງຈັກ (Equipment & Tools register). ເຄື່ອງ 1 ລາຍການ ມີ
 * ຫຼາຍ ໜ່ວຍ (quantity + unit) ແລະ ສະຖານະ ແຍກ ຕາມ ຈຳນວນ (status_counts).
 */
class Equipment extends Model
{
    use SoftDeletes;

    protected $table = 'equipment';

    /** Core statuses (fixed). */
    public const STATUSES = ['active', 'repair', 'retired'];

    protected $fillable = [
        'asset_code', 'fixed_asset_no', 'name', 'category', 'department_id', 'brand_model', 'serial_no',
        'quantity', 'unit_id', 'status_counts',
        'location', 'loc_bin', 'responsible_name', 'responsible_user_id', 'photo_path', 'purchase_date',
        'notes', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'quantity' => 'integer',
        'status_counts' => 'array',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'unit_id');
    }

    /** ພະແນກ ເຈົ້າຂອງ (Owner). */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** ຜູ້ຮັບຜິດຊອບ (ລິ້ງ ຫາ ຜູ້ໃຊ້). */
    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    /** ຊື່ ຜູ້ຮັບຜິດຊອບ ສະແດງ — ຜູ້ໃຊ້ ທີ່ ລິ້ງ ກ່ອນ, ບໍ່ ດັ່ງນັ້ນ ຄ່າ ຂໍ້ຄວາມ ເກົ່າ. */
    public function responsibleLabel(): ?string
    {
        return $this->responsibleUser?->display_name ?? $this->responsible_name;
    }

    public function photos(): HasMany
    {
        return $this->hasMany(EquipmentPhoto::class)->orderBy('sort_order');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(EquipmentInspection::class)->latest('inspected_at');
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(EquipmentMaintenance::class)->latest('maintenance_date');
    }

    /** ລາຍການ ຢືມ ທີ່ ຍັງ ໃຊ້ ຢູ່ (active/overdue) — ໃຊ້ ຫາ ຜູ້ຢືມ ປັດຈຸບັນ. */
    public function activeBorrowItems(): HasMany
    {
        return $this->hasMany(BorrowItem::class)
            ->whereHas('record', fn ($q) => $q->whereIn('status', ['active', 'overdue']));
    }

    /** ຊື່ ຜູ້ຢືມ ປັດຈຸບັນ (distinct) — ວ່າງ ຖ້າ ບໍ່ ໄດ້ ຖືກ ຢືມ. */
    public function currentBorrowers(): array
    {
        return $this->activeBorrowItems
            ->map(fn ($i) => $i->record?->borrower_name)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** ຈຳນວນ ຕໍ່ ສະຖານະ ພ້ອມ key ຄົບ (default 0). */
    public function statusBreakdown(): array
    {
        $c = $this->status_counts ?? [];

        return [
            'active' => (int) ($c['active'] ?? 0),
            'repair' => (int) ($c['repair'] ?? 0),
            'retired' => (int) ($c['retired'] ?? 0),
        ];
    }
}
