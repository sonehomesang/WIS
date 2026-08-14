<?php

namespace App\Support;

/**
 * Shared lifecycle condition-status ("ສະຖານະພາບ") for warehouse items —
 * used by Inventory, Equipment and Deposit items, and by the auto-pull to
 * Disposal. Separate from each module's operational status.
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

    /** All statuses, in display order. */
    public const ALL = [
        self::IN_SERVICE, self::UNDER_REPAIR, self::AWAITING_PARTS,
        self::DETERIORATED, self::BEYOND_REPAIR, self::END_OF_LIFE,
        self::OBSOLETE, self::DECOMMISSIONED,
    ];

    /** Statuses that make an item eligible to be pulled into Disposal. */
    public const DISPOSABLE = [
        self::DETERIORATED, self::BEYOND_REPAIR, self::END_OF_LIFE,
        self::OBSOLETE, self::DECOMMISSIONED,
    ];

    /** Bilingual labels (Lao · English). */
    public const LABELS = [
        self::IN_SERVICE => 'ໃຊ້ ງານ ດີ · In service',
        self::UNDER_REPAIR => 'ກຳລັງ ສ້ອມແປງ · Under repair',
        self::AWAITING_PARTS => 'ລໍ ອາໄຫຼ່ · Awaiting parts',
        self::DETERIORATED => 'ເສື່ອມ ສະພາບ · Deteriorated',
        self::BEYOND_REPAIR => 'ສ້ອມ ບໍ່ ໄດ້ · Beyond repair',
        self::END_OF_LIFE => 'ໝົດ ອາຍຸ ໃຊ້ ງານ · End of life',
        self::OBSOLETE => 'ຕົກ ລຸ້ນ · Obsolete',
        self::DECOMMISSIONED => 'ຍົກເລີກ ໃຊ້ ງານ · Decommissioned',
    ];

    /** Tailwind badge classes per status. */
    public const BADGE = [
        self::IN_SERVICE => 'bg-emerald-50 text-emerald-700',
        self::UNDER_REPAIR => 'bg-amber-50 text-amber-700',
        self::AWAITING_PARTS => 'bg-orange-50 text-orange-700',
        self::DETERIORATED => 'bg-yellow-50 text-yellow-800',
        self::BEYOND_REPAIR => 'bg-red-50 text-red-700',
        self::END_OF_LIFE => 'bg-rose-50 text-rose-700',
        self::OBSOLETE => 'bg-purple-50 text-purple-700',
        self::DECOMMISSIONED => 'bg-slate-100 text-slate-600',
    ];

    public static function isDisposable(?string $s): bool
    {
        return in_array($s, self::DISPOSABLE, true);
    }

    public static function label(?string $s): string
    {
        return self::LABELS[$s] ?? ($s ?? '—');
    }

    /** Short Lao-only label (first part before the ·). */
    public static function shortLabel(?string $s): string
    {
        return trim(explode('·', self::label($s))[0]);
    }

    public static function badge(?string $s): string
    {
        return self::BADGE[$s] ?? 'bg-gray-100 text-gray-600';
    }

    /** [value => label] for select inputs. */
    public static function options(): array
    {
        return self::LABELS;
    }

    /** Validation rule fragment: in:in_service,under_repair,... */
    public static function rule(): string
    {
        return 'in:'.implode(',', self::ALL);
    }
}
