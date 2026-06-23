<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ExpoContact extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(ExpoCompany::class, 'company_id');
    }

    public function getCardUrlAttribute(): ?string
    {
        return $this->business_card_path ? Storage::disk('public')->url($this->business_card_path) : null;
    }
}
