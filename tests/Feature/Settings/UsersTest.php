<?php

use App\Livewire\Settings\Users;
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
