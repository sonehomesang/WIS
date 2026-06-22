<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscrepancyAdviceHistory extends Model
{
    public const UPDATED_AT = null; // append-only

    protected $table = 'discrepancy_advice_history';

    protected $guarded = ['id'];

    public function record(): BelongsTo
    {
        return $this->belongsTo(DiscrepancyAdvice::class, 'record_id');
    }
}
