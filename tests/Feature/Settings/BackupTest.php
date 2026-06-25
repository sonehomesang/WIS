<?php

use App\Livewire\Settings\Backup;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('backup page renders for super_admin', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
    Livewire::test(Backup::class)->assertOk()->assertSee('Backup');
});

test('a user without settings permission is forbidden', function () {
    $u = User::factory()->create(['is_super_admin' => false]);
    $u->assignRole('requester');
    $this->actingAs($u);
    Livewire::test(Backup::class)->assertForbidden();
});

test('restore is super_admin only — an admin (not super) is blocked', function () {
    $admin = User::factory()->create(['is_super_admin' => false]);
    $admin->assignRole('admin');   // has settings.* but not the super bypass
    $this->actingAs($admin);

    Livewire::test(Backup::class)
        ->set('restoreTarget', 'whatever.sql.gz')
        ->set('confirmRestore', 'RESTORE')
        ->call('restore')
        ->assertForbidden();
});

test('restore requires the typed confirmation', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    // wrong/empty confirmation → guarded with an error, never touches the DB
    Livewire::test(Backup::class)
        ->set('restoreTarget', 'x.sql.gz')
        ->set('confirmRestore', 'nope')
        ->call('restore')
        ->assertSet('error', fn ($e) => $e !== '');
});
