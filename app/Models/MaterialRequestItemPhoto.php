<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MaterialRequestItemPhoto extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(MaterialRequestItem::class, 'request_item_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
