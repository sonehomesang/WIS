<?php

use App\Livewire\Inventory\Index;
use App\Models\InventoryItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('super admin can create an inventory item', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(Index::class)
        ->call('newItem')
        ->set('name', 'Drill Bosch GSB')
        ->set('quantity', 5)
        ->set('status', 'available')
        ->call('save')
        ->assertHasNoErrors();

    expect(InventoryItem::where('name', 'Drill Bosch GSB')->first()->quantity)->toBe(5);
});

test('non-permitted user cannot open inventory', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => false]));
    Livewire::test(Index::class)->assertForbidden();
});
