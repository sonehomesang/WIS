<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Marks legitimate (AD/HR-imported, admin-created) accounts as email-verified
 * so the `verified` middleware doesn't wall them. Self-registration is closed,
 * so any unverified account is a vetted, pre-created user.
 */
class VerifyPrecreatedUsers extends Command
{
    protected $signature = 'users:verify-precreated';

    protected $description = 'Set email_verified_at on pre-created/unverified accounts';

    public function handle(): int
    {
        $n = User::whereNull('email_verified_at')->update(['email_verified_at' => now()]);
        $this->info("Verified {$n} account(s).");

        return self::SUCCESS;
    }
}
