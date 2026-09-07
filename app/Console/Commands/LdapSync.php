<?php

namespace App\Console\Commands;

use App\Services\LdapDirectory;
use Illuminate\Console\Command;

/**
 * Pull AD users → pre-create local accounts. Run on the namtheun2 server
 * (which can reach the DC). Schedulable for periodic directory sync.
 */
class LdapSync extends Command
{
    protected $signature = 'ldap:sync {--all : include disabled AD accounts}';

    protected $description = 'Sync users from Active Directory (namtheun2.com) into WH';

    public function handle(LdapDirectory $ldap): int
    {
        if (! $ldap->isEnabled()) {
            $this->warn('AD sync is disabled (Settings → Active Directory). Nothing to do.');

            return self::SUCCESS;
        }

        $this->info('Connecting to AD…');
        $test = $ldap->testConnection();
        if (! $test['ok']) {
            $this->error('Bind failed: '.$test['message']);

            return self::FAILURE;
        }
        $this->line($test['message']);

        $summary = $ldap->sync(enabledOnly: ! $this->option('all'));
        $this->table(
            ['Created', 'Updated', 'Unchanged', 'Skipped'],
            [[$summary['created'], $summary['updated'], $summary['unchanged'], $summary['skipped']]]
        );

        return self::SUCCESS;
    }
}
