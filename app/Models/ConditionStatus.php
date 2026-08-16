<?php

namespace App\Models;

use App\Support\ConditionStatus as ConditionStatusCatalog;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin-managed catalogue of item condition statuses (ສະຖານະພາບ ເຄື່ອງ).
 * The single source of truth; App\Support\ConditionStatus reads (cached) from here.
 */
class ConditionStatus extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_disposable' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        // any change invalidates the cached catalogue used app-wide
        static::saved(fn () => ConditionStatusCatalog::forget());
        static::deleted(fn () => ConditionStatusCatalog::forget());
    }
}
