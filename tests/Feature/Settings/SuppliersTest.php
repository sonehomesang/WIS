<?php

use App\Livewire\Settings\Suppliers;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('super admin can create a supplier', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(Suppliers::class)
        ->call('newItem')
        ->set('name', 'ABC Trading')
        ->set('default_currency', 'THB')
        ->call('save')
        ->assertHasNoErrors();

    expect(Supplier::where('name', 'ABC Trading')->first()->default_currency)->toBe('THB');
});

test('duplicate supplier name is rejected', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
    Supplier::create(['slug' => 'abc', 'name' => 'ABC', 'default_currency' => 'LAK', 'is_active' => true]);

    Livewire::test(Suppliers::class)->call('newItem')->set('name', 'ABC')->call('save')->assertHasErrors(['name']);
});

test('non-permitted user cannot open suppliers', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => false]));
    Livewire::test(Suppliers::class)->assertForbidden();
});
