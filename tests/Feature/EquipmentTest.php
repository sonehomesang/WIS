<?php

use App\Livewire\Equipment\Index;
use App\Models\Equipment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('warehouse staff can create equipment and it gets an auto asset code', function () {
    $u = User::factory()->create();
    $u->syncRoles(['warehouse_staff']);

    actingAs($u);
    Livewire::test(Index::class)
        ->call('newItem')
        ->set('name', 'Generator')
        ->set('category', 'Generator')
        ->set('brand_model', 'Cummins C220')
        ->set('status', 'active')
        ->call('save')
        ->assertHasNoErrors();

    $e = Equipment::first();
    expect($e->name)->toBe('Generator');
    expect($e->asset_code)->toBe('EQ-'.str_pad((string) $e->id, 4, '0', STR_PAD_LEFT));
});

test('a requester cannot access the equipment page (hidden menu)', function () {
    $u = User::factory()->create();
    $u->syncRoles(['requester']);

    expect($u->can('equipment.view'))->toBeFalse();

    actingAs($u);
    Livewire::test(Index::class)->assertForbidden();
});

test('warehouse staff can edit but cannot delete (delete is admin only)', function () {
    $staff = User::factory()->create();
    $staff->syncRoles(['warehouse_staff']);
    $e = Equipment::create(['asset_code' => 'EQ-0001', 'name' => 'Drill', 'status' => 'active']);

    actingAs($staff);
    Livewire::test(Index::class)
        ->call('editItem', $e->id)
        ->set('status', 'repair')
        ->call('save')
        ->assertHasNoErrors();
    expect($e->fresh()->status)->toBe('repair');

    // adminPerm excludes delete → warehouse_staff is blocked
    Livewire::test(Index::class)->call('delete', $e->id)->assertForbidden();
    expect(Equipment::find($e->id))->not->toBeNull();
});

test('a super_admin can delete equipment', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $e = Equipment::create(['asset_code' => 'EQ-0002', 'name' => 'Pump', 'status' => 'active']);

    actingAs($admin);
    Livewire::test(Index::class)->call('delete', $e->id);
    expect(Equipment::find($e->id))->toBeNull();   // soft-deleted
});
