<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Read-through accessor for the Settings › System › Security options, cached so
 * the idle-timeout partial and the verification middleware do not hit the DB on
 * every request. Cache is cleared from System::saveSecurity().
 */
class SecuritySettings
{
    public const CACHE_KEY = 'settings.security';

    public const DEFAULTS = [
        'idle_timeout_minutes' => 3,   // 0 = off
    ];

    /** @return array{idle_timeout_minutes:int} */
    public static function get(): array
    {
        try {
            $s = Cache::rememberForever(
                self::CACHE_KEY,
                fn () => Schema::hasTable('settings') ? Setting::get('security', []) : []
            );
        } catch (\Throwable) {
            $s = [];
        }

        return [
            'idle_timeout_minutes' => (int) ($s['idle_timeout_minutes'] ?? self::DEFAULTS['idle_timeout_minutes']),
        ];
    }

    /** Minutes of inactivity before auto-logout; 0 = disabled. */
    public static function idleTimeoutMinutes(): int
    {
        return max(0, self::get()['idle_timeout_minutes']);
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
