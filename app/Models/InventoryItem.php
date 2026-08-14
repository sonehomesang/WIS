<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class InventoryItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slug', 'name', 'description', 'category', 'brand', 'model', 'serial_number',
        'quantity', 'min_quantity', 'unit',
        'location_id', 'building_id', 'room_id', 'shelf_label', 'department_id',
        'status', 'is_active', 'qr_code', 'created_by', 'updated_by', 'deleted_reason', 'deleted_by',
        'condition_status', 'condition_note', 'condition_set_at', 'condition_set_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'quantity' => 'integer',
        'min_quantity' => 'integer',
        'condition_set_at' => 'datetime',
    ];

    /** Items whose lifecycle status makes them eligible for disposal. */
    public function scopeDisposable($query)
    {
        return $query->whereIn('condition_status', \App\Support\ConditionStatus::DISPOSABLE);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /** ພະແນກ ເຈົ້າ ຂອງ (Org Unit derived via department.unit). */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** ຜູ້ ທີ່ ລຶບ item ນີ້ (ສຳລັບ deleted log). */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(InventoryItemPhoto::class, 'item_id')->orderBy('sort_order')->orderBy('id');
    }

    /** ຮູບໃບທຳອິດ (ໃຊ້ເປັນ thumbnail ໃນ list). "one of many" — eager-load ຖືກ ຕ້ອງ ຕໍ່ ແຖວ
     *  (hasMany()->limit(1) ຈະ ຕີ LIMIT 1 ໃສ່ ທັງ batch → ໂຊ ຮູບ ແຖວ ດຽວ/ໜ້າ). */
    public function primaryPhoto(): HasOne
    {
        return $this->hasOne(InventoryItemPhoto::class, 'item_id')->ofMany(['sort_order' => 'min', 'id' => 'min']);
    }

    /** ໂຕເລກ Material No. (slug) — ນັບຕາມໝວດ (prefix N ໂຕໜ້າ). Dashboard ໃຊ້ຕໍ່ໄດ້.
     *
     * @return Collection<int, object{prefix:string, total:int}>
     */
    public static function prefixCounts(int $len = 2): Collection
    {
        return static::query()
            ->selectRaw('SUBSTR(slug, 1, '.(int) $len.') as prefix, COUNT(*) as total')
            ->groupBy('prefix')
            ->orderByDesc('total')
            ->get();
    }
}
