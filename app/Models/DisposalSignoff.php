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
        'notified_at' => 'datetime',
        'stage_order' => 'integer',
    ];

    /** ມອບໝາຍ ໃຫ້ ຄົນ ແລ້ວ ແຕ່ ຍັງ ບໍ່ ທັນ ເຊັນ. */
    public function isPending(): bool
    {
        return $this->user_id !== null && $this->signed_at === null && $this->decision !== 'rejected';
    }

    public function isSigned(): bool
    {
        return $this->signed_at !== null && $this->decision === 'approved';
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(DisposalRecord::class, 'record_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
