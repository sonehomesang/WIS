<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OgaHistory extends Model
{
    public const UPDATED_AT = null; // append-only

    protected $table = 'oga_history';

    protected $guarded = ['id'];

    public function record(): BelongsTo
    {
        return $this->belongsTo(OutwardsGoodsAdvice::class, 'record_id');
    }
}
