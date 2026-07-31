<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BorrowItem extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'qty' => 'integer',
        'return_qty' => 'integer',
        'sort_order' => 'integer',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(BorrowRecord::class, 'record_id');
    }

    /** Inventory item ຕົ້ນທາງ (null ສຳລັບ free-text). ໃຊ້ສະແດງຮູບ catalog. */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    /** Equipment register — set when borrow_type = tools_equipment (null otherwise). */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(BorrowItemPhoto::class, 'borrow_item_id')->orderBy('sort_order')->orderBy('id');
    }

    public function returnLines(): HasMany
    {
        return $this->hasMany(BorrowReturnEventLine::class, 'borrow_item_id');
    }

    /** ຈຳນວນ ທີ່ ຮັບຄືນ ແລ້ວ (ສະສົມ). */
    public function getReceivedQtyAttribute(): int
    {
        return (int) ($this->return_qty ?? 0);
    }

    /** ຈຳນວນ ທີ່ ຍັງ ຄ້າງ ຢູ່ ນຳ ຜູ້ຢືມ. */
    public function getOutstandingQtyAttribute(): int
    {
        return max(0, (int) $this->qty - $this->received_qty);
    }
}
