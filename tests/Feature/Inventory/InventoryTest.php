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

test('deleting an inventory item requires a reason and moves it to the deleted log', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $item = InventoryItem::create(['slug' => 'drill-1', 'name' => 'Drill', 'quantity' => 3, 'min_quantity' => 0, 'status' => 'available', 'is_active' => true]);

    // reason is required
    Livewire::test(Index::class)
        ->call('openDelete', $item->id)
        ->call('deleteRecord')
        ->assertHasErrors('deleteReason');

    expect(InventoryItem::whereKey($item->id)->exists())->toBeTrue();

    // with a reason → soft-deleted + audit stored
    Livewire::test(Index::class)
        ->call('openDelete', $item->id)
        ->set('deleteReason', 'ນັບ ຊ້ຳ')
        ->call('deleteRecord')
        ->assertHasNoErrors();

    $item->refresh();
    expect($item->trashed())->toBeTrue();
    expect($item->deleted_reason)->toBe('ນັບ ຊ້ຳ');
    expect($item->deleted_by)->toBe($admin->id);
});

test('the deleted log lists trashed items and restore brings them back', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $item = InventoryItem::create(['slug' => 'drill-2', 'name' => 'Drill 2', 'quantity' => 1, 'min_quantity' => 0, 'status' => 'available', 'is_active' => true]);
    $item->forceFill(['deleted_reason' => 'x', 'deleted_by' => $admin->id])->save();
    $item->delete();

    Livewire::test(Index::class)
        ->assertViewHas('items', fn ($p) => ! collect($p->items())->contains('id', $item->id))
        ->call('toggleDeleted')
        ->assertViewHas('items', fn ($p) => collect($p->items())->contains('id', $item->id))
        ->call('restore', $item->id);

    expect(InventoryItem::whereKey($item->id)->first()->trashed())->toBeFalse();
});
