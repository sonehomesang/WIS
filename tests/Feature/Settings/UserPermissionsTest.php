<?php

use App\Livewire\Settings\Users;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create(['is_super_admin' => true, 'display_name' => 'Admin']);
});

test('a requester does not see DA/OGA/Expo by default', function () {
    $u = User::factory()->create(['display_name' => 'Staff', 'status' => 'active']);
    $u->syncRoles(['requester']);

    expect($u->can('inventory.view'))->toBeTrue();
    expect($u->can('borrow.view'))->toBeTrue();
    expect($u->can('da.view'))->toBeFalse();
    expect($u->can('oga.view'))->toBeFalse();
    expect($u->can('expo.view'))->toBeFalse();
});

test('granting only view gives view without create or edit', function () {
    $u = User::factory()->create(['display_name' => 'Staff', 'email' => 'staff@namtheun2.com', 'status' => 'active']);
    $u->syncRoles(['requester']);

    actingAs($this->admin);
    Livewire::test(Users::class)
        ->call('editUser', $u->id)
        ->set('extraPerms.da.view', true)
        ->call('save')
        ->assertHasNoErrors();

    $u->refresh();
    expect($u->can('da.view'))->toBeTrue();
    expect($u->can('da.create'))->toBeFalse();
    expect($u->can('da.edit'))->toBeFalse();
});

test('granting create or edit implies view', function () {
    $u = User::factory()->create(['display_name' => 'Staff', 'email' => 'staff@namtheun2.com', 'status' => 'active']);
    $u->syncRoles(['requester']);

    actingAs($this->admin);
    Livewire::test(Users::class)
        ->call('editUser', $u->id)
        ->set('extraPerms.da.edit', true)   // edit only, view left unticked
        ->call('save')
        ->assertHasNoErrors();

    $u->refresh();
    expect($u->can('da.view'))->toBeTrue();   // implied
    expect($u->can('da.edit'))->toBeTrue();
    expect($u->can('da.create'))->toBeFalse();
});

test('revoking clears the direct permission', function () {
    $u = User::factory()->create(['display_name' => 'Staff', 'email' => 'staff@namtheun2.com', 'status' => 'active']);
    $u->syncRoles(['requester']);
    $u->givePermissionTo('da.view', 'da.create', 'da.edit');

    actingAs($this->admin);
    Livewire::test(Users::class)
        ->call('editUser', $u->id)
        ->set('extraPerms.da.view', false)
        ->set('extraPerms.da.create', false)
        ->set('extraPerms.da.edit', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($u->refresh()->can('da.view'))->toBeFalse();
});

test('escalation guard: admin menus cannot be granted per person', function () {
    $u = User::factory()->create(['display_name' => 'Staff', 'email' => 'staff@namtheun2.com', 'status' => 'active']);
    $u->syncRoles(['requester']);

    actingAs($this->admin);
    Livewire::test(Users::class)
        ->call('editUser', $u->id)
        ->set('extraPerms.da.view', true)
        ->set('extraPerms.users.view', true)   // not grantable → ignored
        ->call('save')
        ->assertHasNoErrors();

    $u->refresh();
    expect($u->can('da.view'))->toBeTrue();
    expect($u->can('users.view'))->toBeFalse();
});
