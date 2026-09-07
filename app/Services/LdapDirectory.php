<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use LdapRecord\Connection;

/**
 * Active Directory (LDAP) directory sync — SYNC ONLY.
 *
 * Reads connection settings from Setting::get('ldap') and pulls user accounts
 * from AD to pre-create local accounts (auth_provider=domain, is_pre_created=true,
 * status=pending). It never stores the synced users' passwords; domain-password
 * login (SSO) is a separate later phase.
 *
 * fetchUsers()/testConnection() bind to the DC and only work where the server can
 * reach it (the namtheun2 server). syncRows() is pure DB logic and is unit-tested
 * locally with fabricated rows (no LDAP needed).
 */
class LdapDirectory
{
    /** Raw stored settings (bind password still encrypted). */
    public function settings(): array
    {
        return Setting::get('ldap', []);
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->settings()['enabled'] ?? false);
    }

    /** Build the LdapRecord connection config from stored settings. */
    public function config(): array
    {
        $s = $this->settings();
        $enc = $s['encryption'] ?? 'ssl';          // ssl (636) · tls (389 StartTLS) · none (389)
        $password = ! empty($s['password']) ? Crypt::decryptString($s['password']) : '';

        return [
            'hosts' => array_filter([$s['host'] ?? '']),
            'port' => (int) ($s['port'] ?? ($enc === 'ssl' ? 636 : 389)),
            'base_dn' => $s['base_dn'] ?? '',
            'username' => $s['bind_username'] ?? '',
            'password' => $password,
            'use_ssl' => $enc === 'ssl',
            'use_tls' => $enc === 'tls',
            'timeout' => 8,
            // AD servers issue referrals that break searches unless disabled.
            'options' => [LDAP_OPT_REFERRALS => 0],
        ];
    }

    /** Search base for user queries (Users OU if set, else base DN). */
    public function searchBase(): string
    {
        $s = $this->settings();

        return $s['user_ou'] ?? $s['base_dn'] ?? '';
    }

    /**
     * Bind to the DC and count users under the search base.
     *
     * @return array{ok:bool,message:string,count:int}
     */
    public function testConnection(): array
    {
        try {
            $conn = new Connection($this->config());
            $conn->connect();                       // binds with the service account

            $count = $conn->query()->in($this->searchBase())
                ->where('objectclass', '=', 'user')
                ->where('objectcategory', '=', 'person')
                ->limit(1000)
                ->get()
                ->count();

            return ['ok' => true, 'message' => "bind OK · ພົບ {$count} users", 'count' => $count];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'count' => 0];
        }
    }

    /**
     * Pull normalized user rows from AD.
     *
     * @return array<int,array{guid:?string,username:?string,email:?string,display_name:?string,phone:?string,department:?string,enabled:bool}>
     */
    public function fetchUsers(bool $enabledOnly = true): array
    {
        $conn = new Connection($this->config());
        $conn->connect();

        $records = $conn->query()->in($this->searchBase())
            ->where('objectclass', '=', 'user')
            ->where('objectcategory', '=', 'person')
            ->select(['samaccountname', 'mail', 'userprincipalname', 'displayname', 'cn', 'telephonenumber', 'department', 'useraccountcontrol', 'objectguid'])
            ->paginate(1000);

        $rows = [];
        foreach ($records as $r) {
            $uac = (int) ($this->first($r, 'useraccountcontrol') ?? 0);
            $enabled = ($uac & 2) === 0;            // 0x2 = ACCOUNTDISABLE
            if ($enabledOnly && ! $enabled) {
                continue;
            }
            $rows[] = [
                'guid' => $this->guid($r),
                'username' => $this->first($r, 'samaccountname'),
                'email' => $this->first($r, 'mail') ?: $this->first($r, 'userprincipalname'),
                'display_name' => $this->first($r, 'displayname') ?: $this->first($r, 'cn'),
                'phone' => $this->first($r, 'telephonenumber'),
                'department' => $this->first($r, 'department'),
                'enabled' => $enabled,
            ];
        }

        return $rows;
    }

    /** Fetch + sync in one call (used by the artisan command / cron). */
    public function sync(bool $enabledOnly = true): array
    {
        return $this->syncRows($this->fetchUsers($enabledOnly));
    }

    /**
     * Upsert normalized rows into the users table. PURE — no LDAP.
     * Match order: ad_guid → username → email.
     *
     * @param  array<int,array>  $rows
     * @param  array<int,string>|null  $onlyGuids  restrict to these AD guids (selection); null = all
     * @return array{created:int,updated:int,unchanged:int,skipped:int}
     */
    public function syncRows(array $rows, ?array $onlyGuids = null): array
    {
        $sum = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0];

        foreach ($rows as $row) {
            $guid = $row['guid'] ?? null;
            if ($onlyGuids !== null && (! $guid || ! in_array($guid, $onlyGuids, true))) {
                continue;
            }

            $username = $this->clean($row['username'] ?? null);
            $email = Str::lower($this->clean($row['email'] ?? null) ?? '');
            $name = $this->clean($row['display_name'] ?? null) ?: ($username ?: $email);

            // No stable identifier at all → cannot import safely.
            if (! $username && ! $email && ! $guid) {
                $sum['skipped']++;

                continue;
            }
            // email is required + unique in schema — fabricate a domain one if AD lacks it.
            if (! $email) {
                $email = Str::lower(($username ?: 'user').'@namtheun2.com');
            }

            $user = ($guid ? User::withTrashed()->where('ad_guid', $guid)->first() : null)
                ?? ($username ? User::withTrashed()->where('username', $username)->first() : null)
                ?? User::withTrashed()->where('email', $email)->first();

            if (! $user) {
                User::create([
                    'ad_guid' => $guid,
                    'username' => $username,
                    'email' => $email,
                    'display_name' => $name,
                    'phone_number' => $this->clean($row['phone'] ?? null),
                    'password' => Str::random(40),        // unusable — domain login is a later phase
                    'auth_provider' => 'domain',
                    'is_pre_created' => true,
                    'status' => 'pending',
                ]);
                $sum['created']++;

                continue;
            }

            // Existing account — backfill link fields without downgrading the person.
            $dirty = [];
            if ($guid && $user->ad_guid !== $guid) {
                $dirty['ad_guid'] = $guid;
            }
            if ($username && $user->username !== $username && ! $this->usernameTaken($username, $user->id)) {
                $dirty['username'] = $username;
            }
            if ($name && $user->display_name !== $name) {
                $dirty['display_name'] = $name;
            }
            if (($p = $this->clean($row['phone'] ?? null)) && $user->phone_number !== $p) {
                $dirty['phone_number'] = $p;
            }

            if ($dirty) {
                $user->forceFill($dirty)->save();
                $sum['updated']++;
            } else {
                $sum['unchanged']++;
            }
        }

        Setting::put('ldap_last_sync', [
            'at' => now()->toDateTimeString(),
            'by' => optional(auth()->user())->only(['id', 'display_name']),
            'summary' => $sum,
        ], auth()->id());

        Log::info('LDAP sync', $sum);

        return $sum;
    }

    // ── helpers ──────────────────────────────────────────────────────────

    private function usernameTaken(string $username, int $exceptId): bool
    {
        return User::withTrashed()->where('username', $username)->where('id', '!=', $exceptId)->exists();
    }

    private function clean(?string $v): ?string
    {
        $v = is_string($v) ? trim($v) : null;

        return $v === '' ? null : $v;
    }

    /** First value of an LdapRecord attribute (arrays) or a plain string. */
    private function first(array $entry, string $key): ?string
    {
        $v = $entry[$key] ?? null;
        if (is_array($v)) {
            $v = $v[0] ?? null;
        }

        return is_string($v) ? $v : null;
    }

    /** Convert AD binary objectGUID to a GUID string. */
    private function guid(array $entry): ?string
    {
        $raw = $entry['objectguid'][0] ?? ($entry['objectguid'] ?? null);
        if (! is_string($raw) || $raw === '') {
            return null;
        }
        // Already a readable GUID string?
        if (Str::contains($raw, '-') && strlen($raw) <= 40) {
            return Str::lower($raw);
        }
        if (strlen($raw) !== 16) {
            return null;
        }
        $h = bin2hex($raw);
        $g = substr($h, 6, 2).substr($h, 4, 2).substr($h, 2, 2).substr($h, 0, 2).'-'
            .substr($h, 10, 2).substr($h, 8, 2).'-'
            .substr($h, 14, 2).substr($h, 12, 2).'-'
            .substr($h, 16, 4).'-'.substr($h, 20, 12);

        return Str::lower($g);
    }
}
