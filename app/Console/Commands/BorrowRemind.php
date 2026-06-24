<?php

namespace App\Console\Commands;

use App\Services\BorrowReminderService;
use Illuminate\Console\Command;

/**
 * Scheduled daily: notify borrowers of returns due tomorrow or already
 * overdue. Same logic as the manual ⏰ Daily Check button (shared service).
 */
class BorrowRemind extends Command
{
    protected $signature = 'borrow:remind';

    protected $description = 'Send due/overdue borrow-return reminders to borrowers';

    public function handle(BorrowReminderService $svc): int
    {
        $r = $svc->sweep();

        $this->info($r['enabled']
            ? "Borrow reminders — {$r['due']} due/overdue, {$r['sent']} notification(s) sent."
            : "Borrow reminders are disabled ({$r['due']} due/overdue, none sent).");

        return self::SUCCESS;
    }
}
