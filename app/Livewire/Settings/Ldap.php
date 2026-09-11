<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use App\Models\User;
use App\Services\LdapDirectory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Settings → Active Directory (LDAP). Admin enters the DC connection + bind
 * (authorize) account, tests it, then pulls the directory to pre-create local
 * accounts. SYNC ONLY — see App\Services\LdapDirectory.
 */
#[Layout('layouts.app')]
class Ldap extends Component
{
    public bool $enabled = false;

    public string $host = '';

    public ?int $port = 636;

    public string $encryption = 'ssl';     // ssl (636) · tls (389 StartTLS) · none (389)

    public string $base_dn = '';

    public string $user_ou = '';

    public string $bind_username = '';

    public string $password = '';          // transient — typed per sync, never stored

    public bool $enabledOnly = true;       // pull only enabled AD accounts

    public bool $tlsSkipVerify = false;    // internal-CA / weak cert → skip TLS chain verification

    public bool $linkExisting = false;     // "keep separate": false = never touch existing accounts

    public bool $loginWithAd = false;      // Phase 2: verify domain passwords against AD at login

    public int $importedCount = 0;         // # of accounts created by import (kill-switch scope)

    /** @var array<int,array> fetched preview rows */
    public array $rows = [];

    public array $selected = [];           // selected AD guids for import

    public string $result = '';

    public string $resultType = '';        // ok · error

    public ?array $summary = null;         // last import summary

    public ?array $lastSync = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('settings.view'), 403);
        $s = Setting::get('ldap', []);
        $this->enabled = (bool) ($s['enabled'] ?? false);
        $this->host = $s['host'] ?? '';
        $this->port = (int) ($s['port'] ?? 636);
        $this->encryption = $s['encryption'] ?? 'ssl';
        $this->base_dn = $s['base_dn'] ?? '';
        $this->user_ou = $s['user_ou'] ?? '';
        // Bind account + password are NEVER loaded from storage — they are not kept
        // at rest. The admin re-types them each time they sync (test/preview/import).
        $this->bind_username = '';
        $this->linkExisting = (bool) ($s['link_existing'] ?? false);
        $this->tlsSkipVerify = (bool) ($s['tls_skip_verify'] ?? false);
        $this->loginWithAd = (bool) ($s['login_with_ad'] ?? false);
        $this->lastSync = Setting::get('ldap_last_sync', []) ?: null;
        $this->importedCount = app(LdapDirectory::class)->importedCount();
    }

    /**
     * Validate + store ONLY the non-secret connection config.
     *
     * The bind (authorize) account + password are never written here — WH does not
     * keep the AD service-account secret at rest. Any legacy stored value is stripped.
     */
    protected function persistConfig(): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        $req = $this->enabled ? 'required' : 'nullable';
        $this->validate([
            'host' => [$req, 'string', 'max:200'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'encryption' => ['required', 'in:ssl,tls,none'],
            'base_dn' => [$req, 'string', 'max:300'],
            'user_ou' => ['nullable', 'string', 'max:300'],
        ], [], ['host' => 'Domain Controller', 'base_dn' => 'Base DN']);

        $existing = Setting::get('ldap', []);
        unset($existing['bind_username'], $existing['password']);   // never keep creds at rest

        Setting::put('ldap', array_merge($existing, [
            'enabled' => $this->enabled,
            'host' => $this->host,
            'port' => (int) $this->port,
            'encryption' => $this->encryption,
            'base_dn' => $this->base_dn,
            'user_ou' => $this->user_ou,
            'link_existing' => $this->linkExisting,
            'tls_skip_verify' => $this->tlsSkipVerify,
            'login_with_ad' => $this->loginWithAd,
        ]), auth()->id());

        Cache::forget('settings.ldap');
    }

    /**
     * The bind account for a directory operation (test/preview/import). It must be
     * typed in now — there is no stored fallback, so a blank field is a hard error.
     *
     * @return array{0:string,1:string} [username, password]
     */
    protected function bindCreds(): array
    {
        $this->validate([
            'bind_username' => ['required', 'string', 'max:200'],
            'password' => ['required', 'string', 'max:200'],
        ], [], ['bind_username' => 'bind username', 'password' => 'bind password']);

        return [trim($this->bind_username), $this->password];
    }

    public function save(): void
    {
        $this->persistConfig();
        $this->dispatch('saved');
    }

    /** Save config first, then bind to the DC with the typed account and report. */
    public function test(): void
    {
        $this->persistConfig();
        [$u, $p] = $this->bindCreds();
        $this->result = '';
        $r = app(LdapDirectory::class)->testConnection($u, $p);
        $this->result = ($r['ok'] ? '✔ ' : '✖ ').$r['message'];
        $this->resultType = $r['ok'] ? 'ok' : 'error';
    }

    /** Pull rows from AD into the preview table (marks new vs existing). */
    public function preview(): void
    {
        $this->persistConfig();
        [$u, $p] = $this->bindCreds();
        $this->result = '';
        $this->rows = [];
        $this->selected = [];
        try {
            $rows = app(LdapDirectory::class)->fetchUsers($this->enabledOnly, $u, $p);
            foreach ($rows as $r) {
                $r['exists'] = $this->matches($r);
                $this->rows[] = $r;
                if ($r['guid']) {
                    $this->selected[] = $r['guid'];
                }
            }
            $this->result = 'ດຶງ '.count($this->rows).' users ຈາກ AD';
            $this->resultType = 'ok';
        } catch (\Throwable $e) {
            $this->result = '✖ ດຶງ ບໍ່ ສຳເລັດ: '.$e->getMessage();
            $this->resultType = 'error';
        }
    }

    /** Import the selected preview rows (or all if nothing ticked). */
    public function importSelected(): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        if (empty($this->rows)) {
            return;
        }
        $only = ! empty($this->selected) ? $this->selected : null;
        $this->summary = app(LdapDirectory::class)->syncRows($this->rows, $only, $this->linkExisting);
        $this->lastSync = Setting::get('ldap_last_sync', []) ?: null;
        $this->importedCount = app(LdapDirectory::class)->importedCount();
        $this->result = 'Import ສຳເລັດ';
        $this->resultType = 'ok';
        $this->dispatch('saved');
    }

    /** Kill switch — disable (lock) every imported account. */
    public function disableImported(): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        $n = app(LdapDirectory::class)->rollbackImported(delete: false);
        $this->importedCount = app(LdapDirectory::class)->importedCount();
        $this->result = "ປິດ (lock) {$n} ບັນຊີ imported ແລ້ວ";
        $this->resultType = 'ok';
        $this->dispatch('saved');
    }

    /** Kill switch — remove (soft-delete) every imported account. */
    public function removeImported(): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        $n = app(LdapDirectory::class)->rollbackImported(delete: true);
        $this->importedCount = app(LdapDirectory::class)->importedCount();
        $this->result = "ລຶບ {$n} ບັນຊີ imported ແລ້ວ";
        $this->resultType = 'ok';
        $this->dispatch('saved');
    }

    /** Does an active/soft-deleted local user already match this AD row? */
    private function matches(array $r): bool
    {
        $email = Str::lower($r['email'] ?? '');

        return User::withTrashed()->where(function ($q) use ($r, $email) {
            if (! empty($r['guid'])) {
                $q->orWhere('ad_guid', $r['guid']);
            }
            if (! empty($r['username'])) {
                $q->orWhere('username', $r['username']);
            }
            if ($email) {
                $q->orWhere('email', $email);
            }
        })->exists();
    }

    public function render(): View
    {
        return view('livewire.settings.ldap');
    }
}
