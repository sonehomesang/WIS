<?php

namespace App\Notifications;

use App\Models\DisposalRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * ແຈ້ງ ຜູ້ ຖືກ ມອບໝາຍ ໃຫ້ ມາ ຮັບຮອງ ໃບ ຈຳໜ່າຍ (ຊ່ອງ ບົດບາດ ຂອງ ຕົນ). ພ້ອມ ລິ້ງ
 * ໄປ ໜ້າ ໃບ + ຄຳ ອະທິບາຍ ສ່ວນ ທີ່ ຕ້ອງ ເຮັດ. ສາມາດ ເຮັດ ອິດສະລະ ບໍ່ ຈຳກັດ ລຳດັບ.
 */
class DisposalEndorsementRequest extends Notification
{
    use Queueable;

    public function __construct(public DisposalRecord $record, public string $roleKey) {}

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $roleLabel = DisposalRecord::STAGES[$this->roleKey]['label'] ?? $this->roleKey;
        $itemCount = $this->record->items()->count();
        $firstItem = $this->record->items()->first();
        $summary = $firstItem
            ? $firstItem->item_name.($itemCount > 1 ? " (+{$itemCount} ລາຍການ)" : '')
            : '—';
        $url = route('disposal.show', $this->record);

        return (new MailMessage)
            ->subject("ຂໍ ຮັບຮອງ ໃບ ຈຳໜ່າຍ {$this->record->request_number} · Endorsement request")
            ->greeting('ສະບາຍດີ '.($notifiable->display_name ?: $notifiable->email))
            ->line("ທ່ານ ຖືກ ມອບໝາຍ ໃຫ້ ເປັນ **{$roleLabel}** ຮັບຮອງ ໃບ ຈຳໜ່າຍ ເຄື່ອງ ເລກທີ **{$this->record->request_number}**.")
            ->line("ລາຍການ: {$summary} · ຈຳນວນ {$itemCount} ລາຍການ")
            ->line('ກະລຸນາ ກົດ ລິ້ງ ລຸ່ມ ນີ້ ເພື່ອ ກວດ ລາຍລະອຽດ, ໃສ່ ຄຳ ເຫັນ / ຄຳ ແນະນຳ ຂອງ ທ່ານ, ແລ້ວ ກົດ ຢືນຢັນ ຮັບຮອງ (ຫຼື ຕີ ກັບ). ເຮັດ ໄດ້ ໂດຍ ບໍ່ ຕ້ອງ ລໍ ຄົນ ອື່ນ.')
            ->action('ເປີດ ໃບ ຈຳໜ່າຍ / Open record', $url)
            ->line('ຂອບໃຈ.');
    }
}
