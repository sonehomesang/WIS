<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** ລາຍການ ໜຶ່ງ ໃນ ໃບ ຈຳໜ່າຍ. */
class DisposalItem extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'qty' => 'integer',
        'estimated_value' => 'decimal:2',
        'sort_order' => 'integer',
        'history' => 'array',
        'photos' => 'array',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(DisposalRecord::class, 'record_id');
    }
}
