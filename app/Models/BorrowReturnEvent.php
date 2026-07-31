<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** ໜຶ່ງ "ຄັ້ງ" ຂອງ ການ ຮັບຄືນ ບາງ ສ່ວນ (progressive partial return). */
class BorrowReturnEvent extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'seq' => 'integer',
        'returned_on' => 'date',
        'created_at' => 'datetime',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(BorrowRecord::class, 'record_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BorrowReturnEventLine::class, 'event_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(BorrowItemPhoto::class, 'return_event_id')->orderBy('sort_order')->orderBy('id');
    }
}
