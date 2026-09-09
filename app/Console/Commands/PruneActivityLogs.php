<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\UserHistory;
use Illuminate\Console\Command;

/**
 * Retention: delete the user-account audit trail and bell notifications older
 * than N days (default 60). Scheduled daily so the window rolls forward.
 * Transaction module histories (borrow/deposit/…) are operational records and
 * are intentionally NOT pruned here.
 */
class PruneActivityLogs extends Command
{
    protected $signature = 'logs:prune {--days=60}';

    protected $description = 'Delete user-account history and bell notifications older than N days (default 60).';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $history = UserHistory::where('created_at', '<', $cutoff)->delete();
        $notifs = Notification::where('created_at', '<', $cutoff)->delete();

        $this->info("Pruned {$history} user_history + {$notifs} notifications older than {$days} days (before {$cutoff->toDateString()}).");

        return self::SUCCESS;
    }
}
