<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OutwardsGoodsAdvice extends Model
{
    use SoftDeletes;

    protected $table = 'outwards_goods_advices';

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'gross_weight_kg' => 'decimal:2',
        'total_weight_kg' => 'decimal:2',
        'authorized_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OgaItem::class, 'record_id')->orderBy('sort_order')->orderBy('id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(OgaPhoto::class, 'record_id')->orderBy('sort_order')->orderBy('id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(OgaHistory::class, 'record_id')->orderBy('id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
