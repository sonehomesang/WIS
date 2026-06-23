<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

/**
 * NotificationService — ສ້າງ in-app notification (Phase 6.10).
 * ໂມດູລ workflow ເອີ້ນ ຕอน transition ສຳຄັນ. Bell component ສະແດງ + mark read.
 */
class NotificationService
{
    public function notify(?int $userId, string $type, string $title, ?string $message = null, ?string $link = null): void
    {
        if (! $userId) {
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
