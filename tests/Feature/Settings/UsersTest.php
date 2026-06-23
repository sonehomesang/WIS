<?php

use App\Livewire\Settings\Users;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function adminUser(): User
{
    return User::factory()->create(['is_super_admin' => true]);
}

test('super admin can create a user with a role', function () {
    $this->actingAs(adminUser());

    Livewire::test(Users::class)
        ->call('newUser')
        ->set('display_name', 'Somchai V.')
        ->set('email', 'somchai@nt2.la')
        ->set('password', 'password123')
        ->set('role', 'warehouse_staff')
        ->set('status', 'active')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'somchai@nt2.la')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('warehouse_staff'))->toBeTrue();
    expect($user->status)->toBe('active');
});

test('email must be unique', function () {
    $this->actingAs(adminUser());
    User::factory()->create(['email' => 'dup@nt2.la']);

    Livewire::test(Users::class)
        ->call('newUser')
        ->set('display_name', 'X')
        ->set('email', 'dup@nt2.la')
        ->set('password', 'password123')
        ->set('role', 'requester')
        ->call('save')
        ->assertHasErrors(['email']);
});

test('can approve a pending user', function () {
    $this->actingAs(adminUser());
    $pending = User::factory()->create(['status' => 'pending']);

    Livewire::test(Users::class)->call('approve', $pending->id);

    expect($pending->refresh()->status)->toBe('active');
});

test('non-permitted user cannot open users', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => false]));

    Livewire::test(Users::class)->assertForbidden();
});

test('creating a supplier-role user requires and stores supplier_id', function () {
    $this->actingAs(adminUser());
    $sup = Supplier::create(['slug' => 'sp-'.uniqid(), 'name' => 'PortalCo', 'is_active' => true]);

    // missing supplier_id → error
    Livewire::test(Users::class)
        ->call('newUser')
        ->set('display_name', 'Sup User')->set('email', 'sup@nt2.la')->set('password', 'password123')
        ->set('role', 'supplier')->set('status', 'active')
        ->call('save')
        ->assertHasErrors(['supplier_id']);

    // with supplier_id → ok + persisted
    Livewire::test(Users::class)
        ->call('newUser')
        ->set('display_name', 'Sup User')->set('email', 'sup@nt2.la')->set('password', 'password123')
        ->set('role', 'supplier')->set('supplier_id', $sup->id)->set('status', 'active')
        ->call('save')
        ->assertHasNoErrors();

    $u = User::where('email', 'sup@nt2.la')->first();
    expect($u->supplier_id)->toBe($sup->id);
    expect($u->hasRole('supplier'))->toBeTrue();
});

test('non-supplier role clears supplier_id', function () {
    $this->actingAs(adminUser());
    $sup = Supplier::create(['slug' => 'sp-'.uniqid(), 'name' => 'X', 'is_active' => true]);
    $u = User::factory()->create(['supplier_id' => $sup->id]);

    Livewire::test(Users::class)
        ->call('editUser', $u->id)
        ->set('role', 'warehouse_staff')
        ->call('save')
        ->assertHasNoErrors();

    expect($u->refresh()->supplier_id)->toBeNull();
});
