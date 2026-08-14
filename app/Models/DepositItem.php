<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepositItem extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'qty' => 'integer',
        'estimated_value' => 'decimal:2',
        'sort_order' => 'integer',
        'condition_set_at' => 'datetime',
    ];

    /** Deposit items whose lifecycle status makes them eligible for disposal. */
    public function scopeDisposable($query)
    {
        return $query->whereIn('condition_status', \App\Support\ConditionStatus::DISPOSABLE);
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(DepositRecord::class, 'record_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(DepositItemPhoto::class, 'deposit_item_id')->orderBy('sort_order')->orderBy('id');
    }
}
