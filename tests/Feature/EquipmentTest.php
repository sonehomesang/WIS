<?php

use App\Livewire\Equipment\Index;
use App\Models\Equipment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        ->set('quantity', 1)
        ->call('save')
        ->assertHasNoErrors();

    $e = Equipment::first();
    expect($e->name)->toBe('Generator');
    expect($e->asset_code)->toBe('EQ-'.str_pad((string) $e->id, 4, '0', STR_PAD_LEFT));
    // new equipment defaults to all active
    expect($e->statusBreakdown())->toBe(['active' => 1, 'repair' => 0, 'retired' => 0]);
});

test('status is split by quantity (active = total − repair − retired)', function () {
    $u = User::factory()->create();
    $u->syncRoles(['warehouse_staff']);

    actingAs($u);
    Livewire::test(Index::class)
        ->call('newItem')
        ->set('name', 'Drills')
        ->set('quantity', 10)
        ->set('qtyRepair', 2)
        ->set('qtyRetired', 1)
        ->call('save')
        ->assertHasNoErrors();

    expect(Equipment::first()->statusBreakdown())->toBe(['active' => 7, 'repair' => 2, 'retired' => 1]);
});

test('repair + retired cannot exceed the total quantity', function () {
    $u = User::factory()->create();
    $u->syncRoles(['warehouse_staff']);

    actingAs($u);
    Livewire::test(Index::class)
        ->call('newItem')
        ->set('name', 'Pumps')
        ->set('quantity', 3)
        ->set('qtyRepair', 2)
        ->set('qtyRetired', 2)
        ->call('save')
        ->assertHasErrors('qtyRepair');

    expect(Equipment::count())->toBe(0);
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
    $e = Equipment::create(['asset_code' => 'EQ-0001', 'name' => 'Drill', 'quantity' => 5]);

    actingAs($staff);
    Livewire::test(Index::class)
        ->call('editItem', $e->id)
        ->set('qtyRepair', 2)
        ->call('save')
        ->assertHasNoErrors();
    expect($e->fresh()->statusBreakdown())->toBe(['active' => 3, 'repair' => 2, 'retired' => 0]);

    // adminPerm excludes delete → warehouse_staff is blocked
    Livewire::test(Index::class)->call('delete', $e->id)->assertForbidden();
    expect(Equipment::find($e->id))->not->toBeNull();
});

test('a super_admin can delete equipment', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $e = Equipment::create(['asset_code' => 'EQ-0002', 'name' => 'Pump', 'quantity' => 1]);

    actingAs($admin);
    Livewire::test(Index::class)->call('delete', $e->id);
    expect(Equipment::find($e->id))->toBeNull();   // soft-deleted
});

test('photos can be attached (up to 3) from an upload', function () {
    Storage::fake('public');
    $u = User::factory()->create();
    $u->syncRoles(['warehouse_staff']);

    actingAs($u);
    Livewire::test(Index::class)
        ->call('newItem')
        ->set('name', 'Welder')
        ->set('quantity', 1)
        ->set('newPhotos', [UploadedFile::fake()->image('p1.jpg'), UploadedFile::fake()->image('p2.jpg')])
        ->call('save')
        ->assertHasNoErrors();

    $e = Equipment::first();
    expect($e->photos)->toHaveCount(2);
    Storage::disk('public')->assertExists($e->photos->first()->path);
});

test('more than 3 photos is rejected', function () {
    Storage::fake('public');
    $u = User::factory()->create(['is_super_admin' => true]);

    actingAs($u);
    Livewire::test(Index::class)
        ->call('newItem')
        ->set('name', 'X')
        ->set('quantity', 1)
        ->set('newPhotos', [
            UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg'),
            UploadedFile::fake()->image('c.jpg'), UploadedFile::fake()->image('d.jpg'),
        ])
        ->call('save')
        ->assertHasErrors('newPhotos');

    expect(Equipment::count())->toBe(0);
});
