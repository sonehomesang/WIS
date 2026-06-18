<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class InventoryItemPhoto extends Model
{
    protected $fillable = ['item_id', 'path', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    /** Public URL ສຳລັບສະແດງ (ຜ່ານ storage symlink). */
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
