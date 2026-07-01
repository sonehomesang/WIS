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
        'asset_code', 'fixed_asset_no', 'name', 'category', 'brand_model', 'serial_no',
        'quantity', 'unit_id', 'status_counts',
        'location', 'responsible_name', 'photo_path', 'purchase_date',
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

    public function photos(): HasMany
    {
        return $this->hasMany(EquipmentPhoto::class)->orderBy('sort_order');
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
