<?php

namespace App\Notifications;

use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * ຢືນຢັນ email — ສົ່ງ ຫຼັງ admin ເປີດ ໃຊ້ ບັນຊີ (activate) ເມື່ອ ເປີດ ນະໂຍບາຍ
 * "ບັງຄັບ ຢືນຢັນ email". ຂໍ້ຄວາມ ດຶງ ຈາກ template (ລາວ/ອັງກິດ, admin ແກ້ ໄດ້) —
 * ຖ້າ ຍັງ ບໍ່ ຕັ້ງ template → ໃຊ້ ຄ່າ fallback ລາວ ດ້ານ ລຸ່ມ.
 */
class VerifyEmailNotification extends Notification
{
    use Queueable;

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        $t = NotificationService::resolve('email.verify_email', [
            'app' => config('app.name'),
            'name' => $notifiable->display_name ?: $notifiable->email,
        ]);

        $mail = (new MailMessage)
            ->subject($t['subject'] ?: 'ຢືນຢັນ email ຂອງ ທ່ານ · Verify your email')
            ->greeting($t['greeting'] ?: ('ສະບາຍດີ '.($notifiable->display_name ?: '').'!'))
            ->line($t['intro'] ?: 'ບັນຊີ ຂອງ ທ່ານ ຖືກ ເປີດ ໃຊ້ ແລ້ວ. ກະລຸນາ ກົດ ປຸ່ມ ລຸ່ມ ນີ້ ເພື່ອ ຢືນຢັນ ອີເມລ ກ່ອນ ເຂົ້າ ໃຊ້ ລະບົບ.')
            ->action($t['button'] ?: 'ຢືນຢັນ Email', $url)
            ->line($t['outro'] ?: 'ຖ້າ ທ່ານ ບໍ່ ໄດ້ ຮ້ອງຂໍ ບັນຊີ ນີ້ ບໍ່ ຕ້ອງ ດຳເນີນ ການ ໃດໆ.');

        if (! empty($t['salutation'])) {
            $mail->salutation($t['salutation']);
        }

        return $mail;
    }

    /** Signed, expiring URL to verification.verify — same shape Laravel uses. */
    protected function verificationUrl(object $notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
