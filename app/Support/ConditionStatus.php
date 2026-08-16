<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Read facade over the admin-managed `condition_statuses` catalogue (single source
 * of truth). Used by Inventory, Equipment and Deposit items, and the auto-pull to
 * Disposal. The constants below are the canonical seed keys — item rows store these
 * string keys, so they must stay stable even as admins add/edit/disable statuses.
 */
class ConditionStatus
{
    public const IN_SERVICE = 'in_service';

    public const UNDER_REPAIR = 'under_repair';

    public const AWAITING_PARTS = 'awaiting_parts';

    public const DETERIORATED = 'deteriorated';

    public const BEYOND_REPAIR = 'beyond_repair';

    public const END_OF_LIFE = 'end_of_life';

    public const OBSOLETE = 'obsolete';

    public const DECOMMISSIONED = 'decommissioned';

    private const CACHE_KEY = 'condition_statuses.catalog';

    /** Canonical seed set — also the fallback when the table is empty/missing. */
    public const DEFAULTS = [
        ['key' => self::IN_SERVICE, 'label' => 'ໃຊ້ ງານ ດີ · In service', 'color' => 'emerald', 'is_disposable' => false],
        ['key' => self::UNDER_REPAIR, 'label' => 'ກຳລັງ ສ້ອມແປງ · Under repair', 'color' => 'amber', 'is_disposable' => false],
        ['key' => self::AWAITING_PARTS, 'label' => 'ລໍ ອາໄຫຼ່ · Awaiting parts', 'color' => 'orange', 'is_disposable' => false],
        ['key' => self::DETERIORATED, 'label' => 'ເສື່ອມ ສະພາບ · Deteriorated', 'color' => 'yellow', 'is_disposable' => true],
        ['key' => self::BEYOND_REPAIR, 'label' => 'ສ້ອມ ບໍ່ ໄດ້ · Beyond repair', 'color' => 'red', 'is_disposable' => true],
        ['key' => self::END_OF_LIFE, 'label' => 'ໝົດ ອາຍຸ ໃຊ້ ງານ · End of life', 'color' => 'rose', 'is_disposable' => true],
        ['key' => self::OBSOLETE, 'label' => 'ຕົກ ລຸ້ນ · Obsolete', 'color' => 'purple', 'is_disposable' => true],
        ['key' => self::DECOMMISSIONED, 'label' => 'ຍົກເລີກ ໃຊ້ ງານ · Decommissioned', 'color' => 'slate', 'is_disposable' => true],
    ];

    /** Preset colour name → Tailwind badge classes (safelisted in tailwind.config.js). */
    public const COLORS = [
        'emerald' => 'bg-emerald-50 text-emerald-700',
        'lime' => 'bg-lime-50 text-lime-700',
        'teal' => 'bg-teal-50 text-teal-700',
        'sky' => 'bg-sky-50 text-sky-700',
        'amber' => 'bg-amber-50 text-amber-700',
        'orange' => 'bg-orange-50 text-orange-700',
        'yellow' => 'bg-yellow-50 text-yellow-800',
        'red' => 'bg-red-50 text-red-700',
        'rose' => 'bg-rose-50 text-rose-700',
        'purple' => 'bg-purple-50 text-purple-700',
        'indigo' => 'bg-indigo-50 text-indigo-700',
        'pink' => 'bg-pink-50 text-pink-700',
        'slate' => 'bg-slate-100 text-slate-600',
        'gray' => 'bg-gray-100 text-gray-600',
    ];

    /** Full catalogue (all rows, ordered), cached forever until an admin edit. */
    protected static function catalog(): Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            if (Schema::hasTable('condition_statuses')) {
                $rows = \App\Models\ConditionStatus::orderBy('sort_order')->orderBy('id')->get();
                if ($rows->isNotEmpty()) {
                    return $rows;
                }
            }

            // fresh install / tests without the seeder → built-in defaults
            return collect(self::DEFAULTS)->map(fn ($d) => (object) ($d + ['is_active' => true]));
        });
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** Active statuses only. */
    protected static function active(): Collection
    {
        return static::catalog()->where('is_active', true);
    }

    /** Active status keys, in display order. */
    public static function all(): array
    {
        return static::active()->pluck('key')->all();
    }

    /** Active keys that make an item eligible for Disposal. */
    public static function disposable(): array
    {
        return static::active()->where('is_disposable', true)->pluck('key')->all();
    }

    public static function isDisposable(?string $s): bool
    {
        return in_array($s, static::disposable(), true);
    }

    /** [key => label] for select inputs (active only). */
    public static function options(): array
    {
        return static::active()->pluck('label', 'key')->all();
    }

    /** Bilingual label — falls back to any row (even inactive) so old data still reads. */
    public static function label(?string $s): string
    {
        return optional(static::catalog()->firstWhere('key', $s))->label ?? ($s ?? '—');
    }

    /** Short Lao-only label (first part before the ·). */
    public static function shortLabel(?string $s): string
    {
        return trim(explode('·', static::label($s))[0]);
    }

    /** Tailwind badge classes for the status' colour. */
    public static function badge(?string $s): string
    {
        $color = optional(static::catalog()->firstWhere('key', $s))->color ?? 'gray';

        return self::COLORS[$color] ?? self::COLORS['gray'];
    }

    /** Validation rule fragment: in:in_service,... (falls back to plain string if empty). */
    public static function rule(): string
    {
        $keys = static::all();

        return $keys ? 'in:'.implode(',', $keys) : 'string';
    }

    /** Available colour names for the admin picker. */
    public static function colorNames(): array
    {
        return array_keys(self::COLORS);
    }
}
