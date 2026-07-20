<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Setting;
use App\Models\User;

/**
 * NotificationService — ສ້າງ in-app notification (Phase 6.10).
 * ໂມດູລ workflow ເອີ້ນ ຕอน transition ສຳຄັນ. Bell component ສະແດງ + mark read.
 *
 * Phase 6.11 — master kill-switch + editable message templates, ຄວບຄຸມ
 * ຈาก Settings › Notifications. ດ້ວຍ feature flag `notifications.enabled`.
 */
class NotificationService
{
    /** Default templates: key => [title, message] with {placeholders}. */
    public const TEMPLATES = [
        'request.submit' => ['title' => 'ໃບເບີກ {number} ລໍ approve', 'message' => 'ຈาก {requester}'],
        'request.approve' => ['title' => 'ໃບເບີກ {number} ຖูก approve ແລ້ວ', 'message' => ''],
        'request.reject' => ['title' => 'ໃບເບີກ {number} ຖูก reject', 'message' => '{reason}'],
        'request.close' => ['title' => 'ໃບເບີກ {number} ສຳເລັດ', 'message' => '{invoice}'],
        // borrow
        'borrow.submit' => ['title' => 'ການຢືມ {number} ລໍ ດำเนินการ', 'message' => 'ຈาก {borrower}'],
        'borrow.approve' => ['title' => 'ການຢືມ {number} ຖูก approve — ໄປຮັບເຄື່ອງ', 'message' => ''],
        'borrow.reminder' => ['title' => 'ການຢືມ {number} ໃກ້/ເກີນ ກຳນົດคืน', 'message' => 'ກຳນົดคืน {date}'],
        // deposit
        'deposit.submit' => ['title' => 'ການຝາກ {number} ລໍ ຮັບເຂົ້າ', 'message' => 'ຈาก {owner}'],
        'deposit.accept' => ['title' => 'ການຝາກ {number} ຖูกຮັບແລ້ວ', 'message' => ''],
        'deposit.stored' => ['title' => 'ການຝາກ {number} ເກັບเข้า stored', 'message' => ''],
        'deposit.claim' => ['title' => 'ການຝາກ {number} ຮັບคืนສຳເລັດ', 'message' => ''],
        // da
        'da.submit' => ['title' => 'DA {number} ລໍ review', 'message' => 'ຈาก {actor}'],
        'da.pending' => ['title' => 'DA {number} ລໍ approve (ຫົວໜ້າ)', 'message' => ''],
        'da.resolved' => ['title' => 'DA {number} resolved', 'message' => ''],
        // oga
        'oga.dispatch' => ['title' => 'OGA {number} ສົ່ງออกแล้ว', 'message' => ''],
        'oga.delivered' => ['title' => 'OGA {number} ส่งถึงแล้ว', 'message' => ''],
    ];

    /**
     * Default EMAIL templates — ສອງ ພາສາ (lo + en). admin ແກ້/ແປ ໄດ້ ຢູ່ Settings › Notifications.
     * placeholder: {app} {minutes} {name}.
     *
     * @var array<string,array{lo:array<string,string>,en:array<string,string>}>
     */
    public const EMAILS = [
        'email.set_password' => [
            'lo' => [
                'subject' => 'ຕັ້ງ / ຣີເຊັດ ລະຫັດຜ່ານ',
                'greeting' => 'ສະບາຍ ດີ!',
                'intro' => 'ທ່ານ ໄດ້ ຮັບ ອີເມວ ນີ້ ຍ້ອນ ມີ ຄຳ ຂໍ ຕັ້ງ / ຣີເຊັດ ລະຫັດຜ່ານ ສຳລັບ ບັນຊີ ຂອງ ທ່ານ.',
                'button' => 'ຕັ້ງ ລະຫັດຜ່ານ',
                'expiry' => 'ລິ້ງ ນີ້ ຈະ ໝົດ ອາຍຸ ພາຍ ໃນ {minutes} ນາທີ.',
                'outro' => 'ຖ້າ ທ່ານ ບໍ່ ໄດ້ ຮ້ອງ ຂໍ ການ ນີ້, ບໍ່ ຕ້ອງ ເຮັດ ຫຍັງ.',
                'salutation' => 'ດ້ວຍ ຄວາມ ນັບຖື,',
            ],
            'en' => [
                'subject' => 'Set / Reset your password',
                'greeting' => 'Hello!',
                'intro' => 'You are receiving this email because we received a password set/reset request for your account.',
                'button' => 'Set Password',
                'expiry' => 'This link will expire in {minutes} minutes.',
                'outro' => 'If you did not request this, no further action is required.',
                'salutation' => 'Regards,',
            ],
        ],
    ];

    /** Master switch — false short-circuits every notify call. */
    public static function enabled(): bool
    {
        return (bool) (Setting::get('notifications', ['enabled' => true])['enabled'] ?? true);
    }

    /** Active SEND language for outgoing messages (admin-set): 'lo' | 'en'. */
    public static function lang(): string
    {
        $l = Setting::get('notifications', [])['lang'] ?? 'lo';

        return in_array($l, ['lo', 'en'], true) ? $l : 'lo';
    }

    /** Whether a template key is enabled. Email keys are always on (ບໍ່ ໃຫ້ ປິດ). */
    public static function templateEnabled(string $key): bool
    {
        if (isset(self::EMAILS[$key])) {
            return true;
        }

        return (bool) ((Setting::get('notification_templates', [])[$key] ?? [])['enabled'] ?? true);
    }

    /** Default field set for a key, as ['lo' => [...], 'en' => [...]]. */
    public static function defaults(string $key): array
    {
        if (isset(self::EMAILS[$key])) {
            return self::EMAILS[$key];
        }
        if (isset(self::TEMPLATES[$key])) {
            return ['lo' => self::TEMPLATES[$key], 'en' => []];
        }

        return ['lo' => [], 'en' => []];
    }

    /**
     * Resolve every field of a template for the active (or given) language.
     * Fallback chain per field: stored[lang] → stored[lo] → default[lang] → default[lo],
     * then {placeholder} replacement. Handles the legacy flat shape (title/message) too.
     *
     * @return array<string,string>
     */
    public static function resolve(string $key, array $vars = [], ?string $lang = null): array
    {
        $lang = $lang ?: self::lang();
        $stored = Setting::get('notification_templates', [])[$key] ?? [];

        // legacy flat ['title'=>, 'message'=>] → treat as lo
        if (! isset($stored['lo']) && ! isset($stored['en']) && (isset($stored['title']) || isset($stored['message']))) {
            $stored = ['lo' => $stored];
        }

        $def = self::defaults($key);
        $repl = [];
        foreach ($vars as $k => $v) {
            $repl['{'.$k.'}'] = (string) $v;
        }

        $out = [];
        foreach (array_keys($def['lo'] ?? []) as $f) {
            $val = (string) ($stored[$lang][$f] ?? '');
            if ($val === '') {
                $val = (string) ($stored['lo'][$f] ?? '');
            }
            if ($val === '' && $lang === 'en') {
                $val = (string) ($def['en'][$f] ?? '');
            }
            if ($val === '') {
                $val = (string) ($def['lo'][$f] ?? '');
            }
            $out[$f] = strtr($val, $repl);
        }

        return $out;
    }

    /** Resolve an in-app template (title + message) for the active language. */
    public static function template(string $key, array $vars = [], ?string $lang = null): array
    {
        $r = self::resolve($key, $vars, $lang);

        return [
            'title' => $r['title'] ?? '',
            'message' => (isset($r['message']) && trim($r['message']) !== '') ? trim($r['message']) : null,
        ];
    }

    public function notify(?int $userId, string $type, string $title, ?string $message = null, ?string $link = null): void
    {
        if (! $userId || ! self::enabled()) {
            return;
        }
        Notification::create([
            'user_id' => $userId,
            'type' => in_array($type, ['info', 'success', 'warning', 'error'], true) ? $type : 'info',
            'title' => mb_substr($title, 0, 256),
            'message' => $message,
            'link' => $link,
            'created_at' => now(),
        ]);
    }

    /** Notify using a stored template; resolves title/message from the key. */
    public function notifyTemplate(?int $userId, string $type, string $key, array $vars = [], ?string $link = null): void
    {
        if (! self::templateEnabled($key)) {
            return;
        }
        $t = self::template($key, $vars);
        if ($t['title'] === '') {
            return;
        }
        $this->notify($userId, $type, $t['title'], $t['message'], $link);
    }

    /** @param  array<int>  $userIds */
    public function notifyMany(array $userIds, string $type, string $title, ?string $message = null, ?string $link = null): void
    {
        foreach (array_unique(array_filter($userIds)) as $uid) {
            $this->notify((int) $uid, $type, $title, $message, $link);
        }
    }

    /** ສົ່ງຫາ ທຸກ user ໃນ role (ເຊັ່ນ warehouse_staff). */
    public function notifyRole(string $role, string $type, string $title, ?string $message = null, ?string $link = null): void
    {
        $ids = User::role($role)->where('status', 'active')->pluck('id')->all();
        $this->notifyMany($ids, $type, $title, $message, $link);
    }

    /** Notify every active user in a role, using a stored template. */
    public function notifyRoleTemplate(string $role, string $type, string $key, array $vars = [], ?string $link = null): void
    {
        if (! self::templateEnabled($key)) {
            return;
        }
        $t = self::template($key, $vars);
        if ($t['title'] === '') {
            return;
        }
        $this->notifyRole($role, $type, $t['title'], $t['message'], $link);
    }

    /** Notify a set of users, using a stored template. */
    public function notifyUsersTemplate(array $userIds, string $type, string $key, array $vars = [], ?string $link = null): void
    {
        if (! self::templateEnabled($key)) {
            return;
        }
        $t = self::template($key, $vars);
        if ($t['title'] === '') {
            return;
        }
        $this->notifyMany($userIds, $type, $t['title'], $t['message'], $link);
    }
}
