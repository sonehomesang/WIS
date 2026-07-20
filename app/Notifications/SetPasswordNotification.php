<?php

namespace App\Notifications;

use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * email ຕັ້ງ / ຣີເຊັດ ລະຫັດຜ່ານ — ຂໍ້ຄວາມ ດຶງ ຈາກ template (ລາວ/ອັງກິດ),
 * admin ແກ້/ແປ ໄດ້ ຢູ່ Settings › Notifications. ໃຊ້ ຮ່ວມ ທັງ forgot-password
 * ແລະ ຕອນ admin ສ້າງ ບັນຊີ ໃໝ່ (invite).
 */
class SetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token) {}

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords', 'users').'.expire', 60);

        $t = NotificationService::resolve('email.set_password', [
            'app' => config('app.name'),
            'minutes' => $minutes,
            'name' => $notifiable->display_name ?: $notifiable->email,
        ]);

        $url = route('password.reset', ['token' => $this->token])
            .'?email='.urlencode($notifiable->getEmailForPasswordReset());

        $mail = (new MailMessage)
            ->subject($t['subject'] ?: 'Set / Reset your password')
            ->greeting($t['greeting'] ?: 'Hello!')
            ->line($t['intro'] ?? '')
            ->action($t['button'] ?: 'Set Password', $url);

        if (! empty($t['expiry'])) {
            $mail->line($t['expiry']);
        }
        if (! empty($t['outro'])) {
            $mail->line($t['outro']);
        }
        if (! empty($t['salutation'])) {
            $mail->salutation($t['salutation']);
        }

        return $mail;
    }
}
