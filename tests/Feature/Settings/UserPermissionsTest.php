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

test('admin can grant an extra menu to one individual (view+create+edit)', function () {
    $u = User::factory()->create(['display_name' => 'Staff', 'email' => 'staff@namtheun2.com', 'status' => 'active']);
    $u->syncRoles(['requester']);

    actingAs($this->admin);
    Livewire::test(Users::class)
        ->call('editUser', $u->id)
        ->set('extraMenus', ['da'])
        ->call('save')
        ->assertHasNoErrors();

    $u->refresh();
    expect($u->can('da.view'))->toBeTrue();
    expect($u->can('da.create'))->toBeTrue();
    expect($u->can('da.edit'))->toBeTrue();
    // not granted: delete stays off
    expect($u->can('da.delete'))->toBeFalse();
});

test('revoking an extra menu removes the direct permission', function () {
    $u = User::factory()->create(['display_name' => 'Staff', 'email' => 'staff@namtheun2.com', 'status' => 'active']);
    $u->syncRoles(['requester']);
    $u->givePermissionTo('da.view', 'da.create', 'da.edit');
    expect($u->can('da.view'))->toBeTrue();

    actingAs($this->admin);
    Livewire::test(Users::class)
        ->call('editUser', $u->id)   // editUser pre-loads extraMenus = ['da']
        ->set('extraMenus', [])      // untick it
        ->call('save')
        ->assertHasNoErrors();

    expect($u->refresh()->can('da.view'))->toBeFalse();
});

test('escalation guard: non-grantable menus (users/roles/settings) are ignored', function () {
    $u = User::factory()->create(['display_name' => 'Staff', 'email' => 'staff@namtheun2.com', 'status' => 'active']);
    $u->syncRoles(['requester']);

    actingAs($this->admin);
    Livewire::test(Users::class)
        ->call('editUser', $u->id)
        ->set('extraMenus', ['da', 'users', 'roles', 'settings'])
        ->call('save')
        ->assertHasNoErrors();

    $u->refresh();
    expect($u->can('da.view'))->toBeTrue();        // grantable → applied
    expect($u->can('users.view'))->toBeFalse();    // not grantable → ignored
    expect($u->can('roles.view'))->toBeFalse();
    expect($u->can('settings.view'))->toBeFalse();
});
