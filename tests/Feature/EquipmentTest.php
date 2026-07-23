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

test('warehouse staff can create equipment with the code they enter', function () {
    $u = User::factory()->create();
    $u->syncRoles(['warehouse_staff']);

    actingAs($u);
    Livewire::test(Index::class)
        ->call('newItem')
        ->set('asset_code', 'GEN-01')
        ->set('name', 'Generator')
        ->set('quantity', 1)
        ->call('save')
        ->assertHasNoErrors();

    $e = Equipment::first();
    expect($e->asset_code)->toBe('GEN-01');
    expect($e->name)->toBe('Generator');
    expect($e->statusBreakdown())->toBe(['active' => 1, 'repair' => 0, 'retired' => 0]);
});

test('viewItem opens a read-only detail modal (register manage column)', function () {
    $e = Equipment::create([
        'asset_code' => 'EL-T001-1', 'name' => 'Megger Insulation Tester',
        'category' => 'Power tool', 'brand_model' => 'Megger MIT510/2', 'quantity' => 1,
    ]);
    actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(Index::class)
        ->assertSee('ຈັດການ')                     // actions column now has a header
        ->call('viewItem', $e->id)
        ->assertSet('viewingItemId', $e->id)
        ->assertSee('Megger Insulation Tester')    // detail modal shows the item
        ->assertSee('Megger MIT510/2');            // brand/model detail

    // opening edit closes the detail modal (no overlap)
    Livewire::test(Index::class)
        ->call('viewItem', $e->id)
        ->call('editItem', $e->id)
        ->assertSet('viewingItemId', null)
        ->assertSet('showModal', true);
});

test('asset code must be unique', function () {
    Equipment::create(['asset_code' => 'DUP-1', 'name' => 'A', 'quantity' => 1]);
    $u = User::factory()->create(['is_super_admin' => true]);

    actingAs($u);
    Livewire::test(Index::class)
        ->call('newItem')
        ->set('asset_code', 'DUP-1')
        ->set('name', 'B')
        ->call('save')
        ->assertHasErrors('asset_code');

    expect(Equipment::where('name', 'B')->count())->toBe(0);
});

test('status is split by quantity (active = total − repair − retired)', function () {
    $u = User::factory()->create();
    $u->syncRoles(['warehouse_staff']);

    actingAs($u);
    Livewire::test(Index::class)
        ->call('newItem')
        ->set('asset_code', 'DRL-01')
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
        ->set('asset_code', 'PMP-01')
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

    Livewire::test(Index::class)->call('delete', $e->id)->assertForbidden();
    expect(Equipment::find($e->id))->not->toBeNull();
});

test('a super_admin can delete equipment', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $e = Equipment::create(['asset_code' => 'EQ-0002', 'name' => 'Pump', 'quantity' => 1]);

    actingAs($admin);
    Livewire::test(Index::class)->call('delete', $e->id);
    expect(Equipment::find($e->id))->toBeNull();
});

test('photos can be attached (up to 3) from an upload', function () {
    Storage::fake('public');
    $u = User::factory()->create();
    $u->syncRoles(['warehouse_staff']);

    actingAs($u);
    Livewire::test(Index::class)
        ->call('newItem')
        ->set('asset_code', 'WLD-01')
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
        ->set('asset_code', 'X-01')
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
