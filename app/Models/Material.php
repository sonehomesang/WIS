<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'is_active' => 'boolean',
        'contract_date' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'last_price_update' => 'date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(MaterialImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function priceHistory(): HasMany
    {
        return $this->hasMany(MaterialPriceHistory::class)->orderByDesc('update_date')->orderByDesc('id');
    }
}
