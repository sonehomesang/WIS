<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        // Served through the auth-gated route (PII card lives on the private disk), not a public URL.
        return $this->business_card_path ? route('expo.card', $this) : null;
    }
}
