<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DepositItemPhoto extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function depositItem(): BelongsTo
    {
        return $this->belongsTo(DepositItem::class, 'deposit_item_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
