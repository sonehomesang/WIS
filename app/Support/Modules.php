<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Feature-flag helper: which optional modules are turned on.
 *
 * Admin toggles these in Settings › System (stored under the `modules` setting).
 * Core areas (dashboard, inventory, settings + admin sub-pages) are NOT toggleable
 * and are always enabled. A module absent from the stored map defaults to ON.
 */
class Modules
{
    /** Optional modules an admin may switch off. Keys match the RBAC menu / route prefix. */
    public const TOGGLEABLE = [
        'borrow', 'deposit', 'request', 'catalog', 'equipment',
        'area_inspection', 'disposal', 'da', 'oga', 'expo',
    ];

    /** Bilingual labels for the settings UI. */
    public const LABELS = [
        'borrow' => 'ຢືມ ເຄື່ອງ · Borrow',
        'deposit' => 'ຝາກ ເຄື່ອງ · Deposit',
        'request' => 'ເບີກ ວັດສະດຸ · Request',
        'catalog' => 'ບັນຊີ ສິນຄ້າ · Catalog',
        'equipment' => 'ເຄື່ອງມື · Equipment',
        'area_inspection' => 'ກວດ ສະຖານທີ່ · Area Inspection',
        'disposal' => 'ຈຳໜ່າຍ · Disposal',
        'da' => 'DA Claims',
        'oga' => 'OGA',
        'expo' => 'Expo Info',
    ];

    /** The stored on/off map ([key => bool]). */
    public static function all(): array
    {
        return Setting::get('modules', []);
    }

    /** Is this menu/module enabled? Core (non-toggleable) keys are always true. */
    public static function enabled(string $key): bool
    {
        if (! in_array($key, self::TOGGLEABLE, true)) {
            return true;
        }

        return (bool) (self::all()[$key] ?? true);
    }
}
