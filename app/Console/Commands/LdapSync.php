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
    protected $signature = 'ldap:sync {--all : include disabled AD accounts} {--bind-user= : AD bind (authorize) username}';

    protected $description = 'Sync users from Active Directory (namtheun2.com) into WH';

    public function handle(LdapDirectory $ldap): int
    {
        if (! $ldap->isEnabled()) {
            $this->warn('AD sync is disabled (Settings → Active Directory). Nothing to do.');

            return self::SUCCESS;
        }

        // The bind (authorize) account is entered at run time — WH never stores it.
        // --bind-user allows scripting the username; the password is always prompted
        // (secret, so it never lands in shell history or the process list).
        $bindUser = (string) ($this->option('bind-user') ?: $this->ask('AD bind (authorize) username'));
        $bindPass = (string) $this->secret('AD bind (authorize) password');
        if (trim($bindUser) === '' || trim($bindPass) === '') {
            $this->error('Bind username and password are both required.');

            return self::FAILURE;
        }

        $this->info('Connecting to AD…');
        $test = $ldap->testConnection($bindUser, $bindPass);
        if (! $test['ok']) {
            $this->error('Bind failed: '.$test['message']);

            return self::FAILURE;
        }
        $this->line($test['message']);

        $summary = $ldap->sync(enabledOnly: ! $this->option('all'), bindUsername: $bindUser, bindPassword: $bindPass);
        $this->table(
            ['Created', 'Updated', 'Unchanged', 'Skipped'],
            [[$summary['created'], $summary['updated'], $summary['unchanged'], $summary['skipped']]]
        );

        return self::SUCCESS;
    }
}
