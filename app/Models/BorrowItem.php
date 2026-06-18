<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BorrowItem extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'qty' => 'integer',
        'return_qty' => 'integer',
        'sort_order' => 'integer',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(BorrowRecord::class, 'record_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(BorrowItemPhoto::class, 'borrow_item_id')->orderBy('sort_order')->orderBy('id');
    }
}
