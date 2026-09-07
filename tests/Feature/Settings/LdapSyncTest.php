<?php

use App\Livewire\Settings\Ldap;
use App\Models\Setting;
use App\Models\User;
use App\Services\LdapDirectory;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
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

test('syncRows links an existing local user by email without duplicating', function () {
    $local = User::factory()->create([
        'email' => 'khamsone@namtheun2.com',
        'auth_provider' => 'password',
        'ad_guid' => null,
    ]);

    $sum = app(LdapDirectory::class)->syncRows([
        adRow(['guid' => 'g-4', 'username' => 'khamsone', 'email' => 'khamsone@namtheun2.com', 'display_name' => 'Khamsone P.']),
    ]);

    $local->refresh();
    expect($sum['created'])->toBe(0)
        ->and($sum['updated'])->toBe(1)
        ->and(User::where('email', 'khamsone@namtheun2.com')->count())->toBe(1)   // no duplicate
        ->and($local->ad_guid)->toBe('g-4')
        ->and($local->username)->toBe('khamsone')
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
