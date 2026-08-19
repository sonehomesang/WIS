<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnsiItem extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'stock' => 'boolean',
        'hazardous' => 'boolean',
        'criticality' => 'boolean',
        'price_usd' => 'decimal:2',
        'qty_order' => 'integer',
        'min_qty' => 'integer',
        'max_qty' => 'integer',
        'sort_order' => 'integer',
    ];

    public const SPECIAL_STORAGE = ['Normal', 'Air Cond room'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(AnsiApplication::class, 'application_id');
    }
}
