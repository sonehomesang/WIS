<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ExpoCompanyFile extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(ExpoCompany::class, 'company_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
