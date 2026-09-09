<?php

use App\Livewire\Settings\Ldap;
use App\Models\Setting;
use App\Models\User;
use App\Services\LdapDirectory;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use LdapRecord\Connection;
use Livewire\Livewire;

/** Build a normalized AD row like LdapDirectory::fetchUsers() returns. */
function adRow(array $o = []): array
{
    return array_merge([
        'guid' => (string) Str::uuid(),
        'username' => 'souksavanh',
        'email' => 'souksavanh@namtheun2.com',
        'display_name' => 'Souksavanh V.',
        'phone' => '+85620555000',
        'department' => 'Warehouse',
        'enabled' => true,
    ], $o);
}

test('syncRows pre-creates pending domain accounts from AD', function () {
    $sum = app(LdapDirectory::class)->syncRows([adRow(['username' => 'bounmy', 'email' => 'BounMy@Namtheun2.com', 'guid' => 'g-1'])]);

    expect($sum)->toMatchArray(['created' => 1, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0]);

    $u = User::where('username', 'bounmy')->first();
    expect($u)->not->toBeNull()
        ->and($u->auth_provider)->toBe('domain')
        ->and($u->is_pre_created)->toBeTrue()
        ->and($u->status)->toBe('pending')
        ->and($u->ad_guid)->toBe('g-1')
        ->and($u->email)->toBe('bounmy@namtheun2.com');   // lowercased
});

test('syncRows is idempotent — re-run leaves accounts unchanged', function () {
    $rows = [adRow(['guid' => 'g-2'])];
    app(LdapDirectory::class)->syncRows($rows);
    $sum = app(LdapDirectory::class)->syncRows($rows);

    expect($sum['created'])->toBe(0)
        ->and($sum['unchanged'])->toBe(1)
        ->and(User::where('ad_guid', 'g-2')->count())->toBe(1);
});

test('syncRows updates when AD display name changes', function () {
    app(LdapDirectory::class)->syncRows([adRow(['guid' => 'g-3', 'display_name' => 'Old Name'])]);
    $sum = app(LdapDirectory::class)->syncRows([adRow(['guid' => 'g-3', 'display_name' => 'New Name'])]);

    expect($sum['updated'])->toBe(1)
        ->and(User::where('ad_guid', 'g-3')->first()->display_name)->toBe('New Name');
});

test('keep-separate (default) leaves a matching REAL account completely untouched', function () {
    $local = User::factory()->create([
        'email' => 'khamsone@namtheun2.com',
        'username' => 'khamsone',
        'auth_provider' => 'password',
        'is_pre_created' => false,
        'ad_guid' => null,
    ]);

    // default linkExisting = false
    $sum = app(LdapDirectory::class)->syncRows([
        adRow(['guid' => 'g-4', 'username' => 'khamsone', 'email' => 'khamsone@namtheun2.com', 'display_name' => 'Khamsone P.']),
    ]);

    $local->refresh();
    expect($sum['created'])->toBe(0)
        ->and($sum['matched'])->toBe(1)                                   // reported for review
        ->and($sum['updated'])->toBe(0)
        ->and(User::where('email', 'khamsone@namtheun2.com')->count())->toBe(1)   // no duplicate
        ->and($local->ad_guid)->toBeNull()                               // untouched
        ->and($local->auth_provider)->toBe('password')
        ->and($local->is_pre_created)->toBeFalse();
});

test('linkExisting=true backfills a matching real account without downgrading it', function () {
    $local = User::factory()->create([
        'email' => 'khamsone@namtheun2.com',
        'username' => 'khamsone-old',
        'auth_provider' => 'password',
        'is_pre_created' => false,
        'ad_guid' => null,
    ]);

    $sum = app(LdapDirectory::class)->syncRows([
        adRow(['guid' => 'g-4', 'username' => 'khamsone', 'email' => 'khamsone@namtheun2.com', 'display_name' => 'Khamsone P.']),
    ], null, linkExisting: true);

    $local->refresh();
    expect($sum['created'])->toBe(0)
        ->and($sum['updated'])->toBe(1)
        ->and(User::where('email', 'khamsone@namtheun2.com')->count())->toBe(1)
        ->and($local->ad_guid)->toBe('g-4')
        ->and($local->auth_provider)->toBe('password');   // NOT downgraded
});

test('syncRows skips rows with no identifier', function () {
    $sum = app(LdapDirectory::class)->syncRows([
        ['guid' => null, 'username' => null, 'email' => null, 'display_name' => null, 'phone' => null, 'department' => null, 'enabled' => true],
    ]);

    expect($sum['skipped'])->toBe(1)->and(User::count())->toBe(0);
});

test('syncRows fabricates a domain email when AD lacks mail', function () {
    app(LdapDirectory::class)->syncRows([adRow(['guid' => 'g-5', 'username' => 'noemail', 'email' => null])]);

    expect(User::where('username', 'noemail')->first()->email)->toBe('noemail@namtheun2.com');
});

test('syncRows onlyGuids restricts import to the selected rows', function () {
    $sum = app(LdapDirectory::class)->syncRows([
        adRow(['guid' => 'sel-A', 'username' => 'aaa', 'email' => 'aaa@namtheun2.com']),
        adRow(['guid' => 'sel-B', 'username' => 'bbb', 'email' => 'bbb@namtheun2.com']),
    ], ['sel-A']);

    expect($sum['created'])->toBe(1)
        ->and(User::where('username', 'aaa')->exists())->toBeTrue()
        ->and(User::where('username', 'bbb')->exists())->toBeFalse();
});

test('rollback disables only imported accounts, never real users', function () {
    $real = User::factory()->create(['auth_provider' => 'password', 'is_pre_created' => false, 'status' => 'active']);
    app(LdapDirectory::class)->syncRows([
        adRow(['guid' => 'imp-1', 'username' => 'imp1', 'email' => 'imp1@namtheun2.com']),
        adRow(['guid' => 'imp-2', 'username' => 'imp2', 'email' => 'imp2@namtheun2.com']),
    ]);

    $svc = app(LdapDirectory::class);
    expect($svc->importedCount())->toBe(2);

    $n = $svc->rollbackImported(delete: false);   // disable

    $real->refresh();
    expect($n)->toBe(2)
        ->and(User::where('username', 'imp1')->first()->status)->toBe('locked')
        ->and($real->status)->toBe('active');     // real user untouched
});

test('kill switch never touches pre-created domain staff it did not import', function () {
    // The app pre-creates real staff exactly like this (domain + pre_created).
    // Scoping the rollback on those flags once permanently deleted genuine staff.
    $staff = User::factory()->create([
        'auth_provider' => 'domain',
        'is_pre_created' => true,
        'ldap_imported_at' => null,
        'status' => 'active',
    ]);

    app(LdapDirectory::class)->syncRows([adRow(['guid' => 'imp-x', 'username' => 'impx', 'email' => 'impx@namtheun2.com'])]);

    $svc = app(LdapDirectory::class);
    expect($svc->importedCount())->toBe(1);          // only what the importer created

    $svc->rollbackImported(delete: true);

    expect(User::whereKey($staff->id)->exists())->toBeTrue()          // staff survives
        ->and(User::where('username', 'impx')->exists())->toBeFalse(); // import removed
});

test('rollback remove soft-deletes only imported accounts', function () {
    $real = User::factory()->create(['auth_provider' => 'password', 'is_pre_created' => false]);
    app(LdapDirectory::class)->syncRows([adRow(['guid' => 'imp-9', 'username' => 'imp9', 'email' => 'imp9@namtheun2.com'])]);

    $n = app(LdapDirectory::class)->rollbackImported(delete: true);

    expect($n)->toBe(1)
        ->and(User::where('username', 'imp9')->exists())->toBeFalse()          // gone from default scope
        ->and(User::withTrashed()->where('username', 'imp9')->exists())->toBeTrue()   // soft-deleted
        ->and(User::whereKey($real->id)->exists())->toBeTrue();               // real user kept
});

test('long AD values are trimmed to fit their columns', function () {
    app(LdapDirectory::class)->syncRows([adRow([
        'guid' => 'g-fit',
        'username' => 'fituser',
        'email' => 'fituser@namtheun2.com',
        'display_name' => str_repeat('ກ', 400),
        'phone' => '020-55-613-855, 020-2223 5601 Ext 110',   // AD can hold several
    ])]);

    $u = User::where('username', 'fituser')->first();
    expect($u)->not->toBeNull()
        ->and(mb_strlen($u->display_name))->toBeLessThanOrEqual(256)
        ->and($u->phone_number)->toBe('020-55-613-855')        // first number only
        ->and(mb_strlen($u->phone_number))->toBeLessThanOrEqual(32);
});

test('binary objectGUID containing a dash byte is converted, not stored raw', function () {
    // 16 raw bytes including 0x2d ('-') — the case that used to store binary garbage
    $binary = hex2bin('c91adc67a69d2d4ab1e2f0a1b2c3d4e5');
    $m = new ReflectionMethod(LdapDirectory::class, 'guid');
    $m->setAccessible(true);

    expect($m->invoke(app(LdapDirectory::class), ['objectguid' => [$binary]]))
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});

test('searchBase falls back to the base DN when the users OU is blank', function () {
    $base = 'DC=namtheun2,DC=com';

    // blank string must fall back — searching a non-existent OU silently returns 0 users
    Setting::put('ldap', ['base_dn' => $base, 'user_ou' => ''], null);
    expect(app(LdapDirectory::class)->searchBase())->toBe($base);

    Setting::put('ldap', ['base_dn' => $base, 'user_ou' => '   '], null);
    expect(app(LdapDirectory::class)->searchBase())->toBe($base);

    // a real OU is still honoured
    Setting::put('ldap', ['base_dn' => $base, 'user_ou' => 'OU=Staff,'.$base], null);
    expect(app(LdapDirectory::class)->searchBase())->toBe('OU=Staff,'.$base);
});

test('config builds a valid LdapRecord connection (LDAPS, no unknown options)', function () {
    Setting::put('ldap', [
        'enabled' => true, 'host' => 'dc01.namtheun2.com', 'port' => 636,
        'encryption' => 'ssl', 'base_dn' => 'DC=namtheun2,DC=com',
        'bind_username' => 'svc-ldap@example.com',
        'password' => Crypt::encryptString('secret'),
    ], null);

    $cfg = app(LdapDirectory::class)->config();
    expect($cfg['use_tls'])->toBeTrue()           // ssl → LDAPS (ldaps://)
        ->and($cfg['use_starttls'])->toBeFalse()
        ->and($cfg)->not->toHaveKey('use_ssl');    // v4 removed use_ssl

    // constructing the Connection validates option keys — throws on an unknown one
    $conn = new Connection($cfg);
    expect($conn)->toBeInstanceOf(Connection::class);
});

test('ldap settings page renders for admin', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(Ldap::class)
        ->assertOk()
        ->assertSee('Active Directory')
        ->assertSee('Bind username');
});

test('saving stores the bind password encrypted (blank keeps existing)', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(Ldap::class)
        ->set('enabled', true)
        ->set('host', 'dc01.namtheun2.com')
        ->set('base_dn', 'DC=namtheun2,DC=com')
        ->set('bind_username', 'svc-wh@namtheun2.com')
        ->set('password', 's3cr3t!')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('password', '')          // cleared after save
        ->assertSet('hasPassword', true);

    $stored = Setting::get('ldap');
    expect($stored['password'])->not->toBe('s3cr3t!')                    // not plain-text
        ->and(Crypt::decryptString($stored['password']))->toBe('s3cr3t!');
});
