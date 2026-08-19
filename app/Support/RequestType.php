<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Read facade over the admin-managed `request_types` catalogue (single source of
 * truth for the Material Request "Type" field). Rows store the string `key`, so the
 * canonical seed keys below must stay stable even as admins add/edit/disable types.
 */
class RequestType
{
    private const CACHE_KEY = 'request_types.catalog';

    /** Canonical seed set — also the fallback when the table is empty/missing. */
    public const DEFAULTS = [
        ['key' => 'CM', 'label' => 'CM · Corrective Maintenance'],
        ['key' => 'PM', 'label' => 'PM · Preventive Maintenance'],
        ['key' => 'PdM', 'label' => 'PdM · Predictive Maintenance'],
        ['key' => 'eForm', 'label' => 'eForm'],
        ['key' => 'project', 'label' => 'Project'],
    ];

    /** Full catalogue (all rows, ordered), cached forever until an admin edit. */
    protected static function catalog(): Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            if (Schema::hasTable('request_types')) {
                $rows = \App\Models\RequestType::orderBy('sort_order')->orderBy('id')->get();
                if ($rows->isNotEmpty()) {
                    return $rows;
                }
            }

            // fresh install / tests without the seeder → built-in defaults
            return collect(self::DEFAULTS)->map(fn ($d, $i) => (object) ($d + ['is_active' => true, 'sort_order' => $i]));
        });
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** Active types only. */
    protected static function active(): Collection
    {
        return static::catalog()->where('is_active', true);
    }

    /** Active type keys, in display order. */
    public static function all(): array
    {
        return static::active()->pluck('key')->all();
    }

    /** [key => label] for select inputs (active only). */
    public static function options(): array
    {
        return static::active()->pluck('label', 'key')->all();
    }

    /** Label for a stored key — falls back to any row (even inactive) so old data still reads. */
    public static function label(?string $s): string
    {
        return optional(static::catalog()->firstWhere('key', $s))->label ?? ($s ?? '—');
    }
}
