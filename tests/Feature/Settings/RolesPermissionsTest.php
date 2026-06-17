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
