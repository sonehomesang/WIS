<?php

namespace App\Models;

use App\Support\RequestType as RequestTypeCatalog;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin-managed catalogue of Material Request types (ປະເພດ ການ ຂໍ ເຄື່ອງ).
 * The single source of truth; App\Support\RequestType reads (cached) from here.
 */
class RequestType extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        // any change invalidates the cached catalogue used app-wide
        static::saved(fn () => RequestTypeCatalog::forget());
        static::deleted(fn () => RequestTypeCatalog::forget());
    }
}
