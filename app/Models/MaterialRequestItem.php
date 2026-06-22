<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialRequestItem extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'supplier_quantity' => 'integer',
        'receiver_confirmed' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(MaterialRequest::class, 'record_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(MaterialRequestItemPhoto::class, 'request_item_id')->orderBy('sort_order')->orderBy('id');
    }

    public function getLineTotalAttribute(): float
    {
        return (float) $this->unit_price * (int) $this->quantity;
    }
}
