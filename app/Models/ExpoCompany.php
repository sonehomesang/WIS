<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpoCompany extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'score' => 'integer',
        'sort_order' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(ExpoEvent::class, 'event_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(ExpoContact::class, 'company_id')->orderBy('sort_order')->orderBy('id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ExpoCompanyFile::class, 'company_id')->orderBy('sort_order')->orderBy('id');
    }
}
