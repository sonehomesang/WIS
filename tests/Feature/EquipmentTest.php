<?php

use App\Livewire\Equipment\Index;
use App\Livewire\Equipment\Maintenance;
use App\Models\Equipment;
use App\Models\EquipmentInspection;
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

test('clearItemPhoto requires equipment.edit — a view-only user cannot delete files (H1)', function () {
    $viewer = User::factory()->create();
    $viewer->givePermissionTo('equipment.view');   // ເຫັນ ໄດ້ ແຕ່ ບໍ່ ມີ edit
    actingAs($viewer);

    Livewire::test(Maintenance::class)
        ->call('clearItemPhoto', 0, 'problem')
        ->assertForbidden();
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

test('an inspection is deleted with a required reason and can be restored', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $e = Equipment::create(['asset_code' => 'EL-9', 'name' => 'Tester', 'quantity' => 1]);
    $ins = EquipmentInspection::create([
        'equipment_id' => $e->id, 'inspected_at' => now(), 'inspector_name' => 'SA', 'result' => 'pass',
    ]);
    actingAs($admin);

    // reason required
    Livewire::test(Index::class)
        ->call('openDeleteInspection', $ins->id)
        ->assertSet('deletingInsId', $ins->id)
        ->call('deleteInspectionRecord')
        ->assertHasErrors(['insDeleteReason' => 'required']);
    expect(EquipmentInspection::find($ins->id))->not->toBeNull();

    // delete with reason → soft-deleted + metadata stored
    Livewire::test(Index::class)
        ->call('openDeleteInspection', $ins->id)
        ->set('insDeleteReason', 'ບັນທຶກ ຊ້ຳ')
        ->call('deleteInspectionRecord')
        ->assertHasNoErrors()
        ->assertSet('deletingInsId', null);
    expect(EquipmentInspection::find($ins->id))->toBeNull();
    $trashed = EquipmentInspection::withTrashed()->find($ins->id);
    expect($trashed->trashed())->toBeTrue();
    expect($trashed->deleted_reason)->toBe('ບັນທຶກ ຊ້ຳ');
    expect($trashed->deleted_by)->toBe($admin->id);

    // restore clears metadata
    Livewire::test(Index::class)->call('restoreInspection', $ins->id);
    expect(EquipmentInspection::find($ins->id)?->deleted_reason)->toBeNull();
});

test('inspection list search + result + category filters narrow the list', function () {
    actingAs(User::factory()->create(['is_super_admin' => true]));
    $e1 = Equipment::create(['asset_code' => 'A-1', 'name' => 'Alpha', 'category' => 'Power tool', 'quantity' => 1]);
    $e2 = Equipment::create(['asset_code' => 'B-1', 'name' => 'Beta', 'category' => 'Vehicle', 'quantity' => 1]);
    EquipmentInspection::create(['equipment_id' => $e1->id, 'inspected_at' => now(), 'result' => 'pass', 'inspector_name' => 'Sam']);
    EquipmentInspection::create(['equipment_id' => $e2->id, 'inspected_at' => now(), 'result' => 'fail', 'inspector_name' => 'Lee']);

    Livewire::test(Index::class)->set('inspSearch', 'Alpha')
        ->assertViewHas('inspections', fn ($i) => $i->count() === 1 && $i->first()->equipment_id === $e1->id);
    Livewire::test(Index::class)->set('inspResultFilter', 'fail')
        ->assertViewHas('inspections', fn ($i) => $i->count() === 1 && $i->first()->result === 'fail');
    Livewire::test(Index::class)->set('inspCategoryFilter', 'Vehicle')
        ->assertViewHas('inspections', fn ($i) => $i->count() === 1 && $i->first()->equipment_id === $e2->id);
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

    Livewire::test(Index::class)->call('openDelete', $e->id)->assertForbidden();
    expect(Equipment::find($e->id))->not->toBeNull();
});

test('a super_admin deletes with a required reason (soft-delete to log)', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $e = Equipment::create(['asset_code' => 'EQ-0002', 'name' => 'Pump', 'quantity' => 1]);

    actingAs($admin);

    // reason is required — empty is rejected, item stays
    Livewire::test(Index::class)
        ->call('openDelete', $e->id)
        ->assertSet('deletingId', $e->id)
        ->call('deleteRecord')
        ->assertHasErrors(['deleteReason' => 'required']);
    expect(Equipment::find($e->id))->not->toBeNull();

    // with a reason → soft-deleted, reason + who stored, and restorable
    Livewire::test(Index::class)
        ->call('openDelete', $e->id)
        ->set('deleteReason', 'ຊຳລຸດ ໃຊ້ ບໍ່ ໄດ້')
        ->call('deleteRecord')
        ->assertHasNoErrors()
        ->assertSet('deletingId', null);

    expect(Equipment::find($e->id))->toBeNull();                 // hidden from normal list
    $trashed = Equipment::withTrashed()->find($e->id);
    expect($trashed->trashed())->toBeTrue();
    expect($trashed->deleted_reason)->toBe('ຊຳລຸດ ໃຊ້ ບໍ່ ໄດ້');
    expect($trashed->deleted_by)->toBe($admin->id);

    // restore clears the delete metadata
    Livewire::test(Index::class)->call('restore', $e->id);
    $restored = Equipment::find($e->id);
    expect($restored)->not->toBeNull();
    expect($restored->deleted_reason)->toBeNull();
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

test('the equipment page opens on the tab given by ?tab= (invalid falls back)', function () {
    actingAs(User::factory()->create(['is_super_admin' => true]));

    $this->get(route('equipment', ['tab' => 'maintenance']))
        ->assertOk()->assertSee("tab: 'maintenance'", false);

    $this->get(route('equipment', ['tab' => 'inspection']))
        ->assertSee("tab: 'inspection'", false);

    $this->get(route('equipment', ['tab' => 'bogus']))
        ->assertSee("tab: 'register'", false);
});
