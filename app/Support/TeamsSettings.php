<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Microsoft Teams outgoing-notification config (Settings › Notifications › Teams).
 * A default Incoming-Webhook URL plus optional per-module overrides so each
 * module can post to its own Teams channel. WhatsApp is a later channel.
 */
class TeamsSettings
{
    public const CACHE_KEY = 'settings.teams';

    /** Modules that fire template notifications (key prefix) → Lao label. */
    public const MODULES = [
        'borrow' => 'ການ ຢືມ',
        'deposit' => 'ການ ຝາກ',
        'request' => 'ການ ຂໍ ເບີກ',
        'da' => 'DA',
        'oga' => 'OGA',
    ];

    /** @return array{enabled:bool,default_webhook:string,modules:array<string,array{enabled:bool,webhook:string}>} */
    public static function get(): array
    {
        try {
            $s = Cache::rememberForever(
                self::CACHE_KEY,
                fn () => Schema::hasTable('settings') ? Setting::get('teams', []) : []
            );
        } catch (\Throwable) {
            $s = [];
        }

        $modules = [];
        foreach (array_keys(self::MODULES) as $m) {
            $modules[$m] = [
                'enabled' => (bool) ($s['modules'][$m]['enabled'] ?? false),
                'webhook' => (string) ($s['modules'][$m]['webhook'] ?? ''),
            ];
        }

        return [
            'enabled' => (bool) ($s['enabled'] ?? false),
            'default_webhook' => (string) ($s['default_webhook'] ?? ''),
            'modules' => $modules,
        ];
    }

    public static function enabled(): bool
    {
        return self::get()['enabled'];
    }

    /**
     * Resolve the webhook URL to post to for a module, or null if Teams is off,
     * the module is off, or no URL is set (module override → default).
     */
    public static function webhookFor(string $module): ?string
    {
        $s = self::get();
        if (! $s['enabled']) {
            return null;
        }
        $m = $s['modules'][$module] ?? null;
        if (! $m || ! $m['enabled']) {
            return null;
        }
        $url = $m['webhook'] !== '' ? $m['webhook'] : $s['default_webhook'];

        return $url !== '' ? $url : null;
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
