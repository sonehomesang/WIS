<?php

namespace App\Console\Commands;

use App\Services\LdapDirectory;
use Illuminate\Console\Command;

/**
 * One-off / anytime cleanup: remove any AD bind (authorize) account + password
 * left in stored settings. WH does not keep the service-account secret at rest —
 * it is entered per sync. Non-secret connection config is untouched, and user
 * login is unaffected (it binds as the person, not the service account).
 */
class LdapForgetBind extends Command
{
    protected $signature = 'ldap:forget-bind';

    protected $description = 'Remove any stored AD bind account + password (entered per-sync, never kept at rest)';

    public function handle(LdapDirectory $ldap): int
    {
        $had = $ldap->forgetBindCredentials();

        $this->info('Stored AD bind credentials cleared.');
        $this->table(
            ['bind_username was stored', 'bind password was stored'],
            [[$had['bind_username'] ? 'yes → removed' : 'no', $had['password'] ? 'yes → removed' : 'no']]
        );

        return self::SUCCESS;
    }
}
