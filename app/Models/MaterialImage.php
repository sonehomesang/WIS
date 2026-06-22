<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MaterialImage extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['sort_order' => 'integer'];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
