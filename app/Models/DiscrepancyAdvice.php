<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscrepancyAdvice extends Model
{
    use SoftDeletes;

    protected $table = 'discrepancy_advices';

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'po_date' => 'date',
        'discrepancy_types' => 'array',
        'purchasing_decisions' => 'array',
        'raised_at' => 'datetime',
        'purchasing_decided_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(DiscrepancyAdviceItem::class, 'record_id')->orderBy('sort_order')->orderBy('id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(DiscrepancyAdvicePhoto::class, 'record_id')->orderBy('sort_order')->orderBy('id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(DiscrepancyAdviceHistory::class, 'record_id')->orderBy('id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
