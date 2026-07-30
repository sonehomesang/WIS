<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** ຂັ້ນ ເຊັນ ຮັບຮອງ ໜຶ່ງ ຄົນ ຂອງ ໃບ ຈຳໜ່າຍ. */
class DisposalSignoff extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'signed_at' => 'datetime',
        'stage_order' => 'integer',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(DisposalRecord::class, 'record_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
