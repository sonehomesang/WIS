<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscrepancyAdviceItem extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'qty_ordered' => 'integer',
        'qty_delivered' => 'integer',
        'qty_received' => 'integer',
        'sort_order' => 'integer',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(DiscrepancyAdvice::class, 'record_id');
    }
}
