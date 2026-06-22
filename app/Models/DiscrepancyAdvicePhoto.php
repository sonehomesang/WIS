<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DiscrepancyAdvicePhoto extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function record(): BelongsTo
    {
        return $this->belongsTo(DiscrepancyAdvice::class, 'record_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
