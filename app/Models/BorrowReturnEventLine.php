<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** ຈຳນວນ ຮັບຄືນ ຕໍ່ ລາຍການ ໃນ ໜຶ່ງ ຄັ້ງ (event). */
class BorrowReturnEventLine extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'qty' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(BorrowReturnEvent::class, 'event_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(BorrowItem::class, 'borrow_item_id');
    }
}
