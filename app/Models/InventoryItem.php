<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slug', 'name', 'description', 'category', 'brand', 'model', 'serial_number',
        'quantity', 'min_quantity', 'unit',
        'location_id', 'building_id', 'room_id', 'shelf_label',
        'status', 'is_active', 'qr_code', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'quantity' => 'integer',
        'min_quantity' => 'integer',
    ];

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

    public function photos(): HasMany
    {
        return $this->hasMany(InventoryItemPhoto::class, 'item_id')->orderBy('sort_order')->orderBy('id');
    }

    /** ຮູບໃບທຳອິດ (ໃຊ້ເປັນ thumbnail ໃນ list). */
    public function primaryPhoto(): HasMany
    {
        return $this->photos()->limit(1);
    }
}
