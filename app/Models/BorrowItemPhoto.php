<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BorrowItemPhoto extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function borrowItem(): BelongsTo
    {
        return $this->belongsTo(BorrowItem::class, 'borrow_item_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
