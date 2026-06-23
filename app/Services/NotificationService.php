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
    ];

    /** Master switch — false short-circuits every notify call. */
    public static function enabled(): bool
    {
        return (bool) (Setting::get('notifications', ['enabled' => true])['enabled'] ?? true);
    }

    /** Resolve a template (custom over default) with {placeholder} replacement. */
    public static function template(string $key, array $vars = []): array
    {
        $custom = Setting::get('notification_templates', [])[$key] ?? [];
        $tpl = array_merge(self::TEMPLATES[$key] ?? ['title' => '', 'message' => ''], array_filter($custom, fn ($v) => $v !== null && $v !== ''));

        $repl = [];
        foreach ($vars as $k => $v) {
            $repl['{'.$k.'}'] = (string) $v;
        }

        return [
            'title' => strtr($tpl['title'] ?? '', $repl),
            'message' => trim(strtr($tpl['message'] ?? '', $repl)) ?: null,
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
}
