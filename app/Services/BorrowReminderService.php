<?php

namespace App\Services;

use App\Models\BorrowRecord;
use App\Models\Notification;
use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * Sweeps active borrows due tomorrow-or-overdue and fires a reminder
 * notification to each borrower. Shared by the scheduled `borrow:remind`
 * command and the manual ⏰ Daily Check button on the Borrow list.
 *
 * Respects the master notifications switch + `borrow_reminder` flag, and
 * dedupes to one reminder per record per day.
 */
class BorrowReminderService
{
    /** @return array{enabled:bool,due:int,sent:int} */
    public function sweep(): array
    {
        $tomorrow = Carbon::today()->addDay();
        $due = BorrowRecord::where('status', 'active')
            ->whereDate('planned_return_date', '<=', $tomorrow)
            ->get(['id', 'request_number', 'borrower_user_id', 'planned_return_date']);

        $flags = Setting::get('notifications', ['enabled' => true, 'borrow_reminder' => true]);
        $enabled = NotificationService::enabled() && ($flags['borrow_reminder'] ?? true);
        if (! $enabled) {
            return ['enabled' => false, 'due' => $due->count(), 'sent' => 0];
        }

        $svc = app(NotificationService::class);
        $sent = 0;
        foreach ($due as $r) {
            if (! $r->borrower_user_id) {
                continue;
            }
            $link = route('borrow.show', $r);
            $already = Notification::where('user_id', $r->borrower_user_id)
                ->where('link', $link)->whereDate('created_at', Carbon::today())->exists();
            if ($already) {
                continue;
            }
            $svc->notifyTemplate($r->borrower_user_id, 'warning', 'borrow.reminder', [
                'number' => $r->request_number,
                'date' => Carbon::parse($r->planned_return_date)->format('d/m/Y'),
            ], $link);
            $sent++;
        }

        return ['enabled' => true, 'due' => $due->count(), 'sent' => $sent];
    }
}
