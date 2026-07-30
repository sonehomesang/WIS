<?php

use App\Livewire\Settings\RolesPermissions;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('super admin can grant a permission to a role', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
    $requester = Role::where('name', 'requester')->first();

    expect($requester->hasPermissionTo('inventory.create'))->toBeFalse();

    Livewire::test(RolesPermissions::class)
        ->call('selectRole', $requester->id)
        ->set('grants.inventory.create', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($requester->fresh()->hasPermissionTo('inventory.create'))->toBeTrue();
});

test('super admin can update scope rules', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
    $approver = Role::where('name', 'approver')->first();

    Livewire::test(RolesPermissions::class)
        ->call('selectRole', $approver->id)
        ->set('scope.transactionScope', 'own')
        ->call('save');

    expect($approver->fresh()->scope_rules['transactionScope'])->toBe('own');
});

test('saving the super_admin role is a no-op (read-only)', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
    $super = Role::where('name', 'super_admin')->first();
    $before = $super->permissions()->count();

    Livewire::test(RolesPermissions::class)
        ->call('selectRole', $super->id)
        ->set('grants.inventory.view', false)
        ->call('save');

    expect($super->fresh()->permissions()->count())->toBe($before);
});

test('non-permitted user cannot open roles editor', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => false]));
    Livewire::test(RolesPermissions::class)->assertForbidden();
});

test('the roles editor lists every menu the seeder creates (no silent perm loss)', function () {
    $ref = new ReflectionClass(RolePermissionSeeder::class);
    $prop = $ref->getProperty('menus');
    $prop->setAccessible(true);
    $seederMenus = $prop->getValue(new RolePermissionSeeder);

    // If the UI list drifts from the seeder, save()'s syncPermissions() would strip
    // the missing menus' permissions from the role — the exact bug this guards.
    expect((new RolesPermissions)->menus)->toEqualCanonicalizing($seederMenus);
});

test('saving a role via the editor keeps its equipment + disposal permissions', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
    $role = Role::where('name', 'warehouse_staff')->firstOrFail();

    expect($role->hasPermissionTo('equipment.view'))->toBeTrue()
        ->and($role->hasPermissionTo('disposal.view'))->toBeTrue();

    Livewire::test(RolesPermissions::class)
        ->call('selectRole', $role->id)
        ->call('save')
        ->assertHasNoErrors();

    $role->unsetRelation('permissions');
    expect($role->hasPermissionTo('equipment.view'))->toBeTrue()   // must survive the save
        ->and($role->hasPermissionTo('disposal.view'))->toBeTrue();
});
