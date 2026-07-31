<?php

use App\Livewire\Settings\Facilities;
use App\Models\Building;
use App\Models\BuildingType;
use App\Models\Location;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('super admin can build location -> building -> room', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    $c = Livewire::test(Facilities::class)
        ->call('newLocation')->set('name', 'NT2 Site')->call('save')->assertHasNoErrors();

    $loc = Location::where('name', 'NT2 Site')->first();
    expect($loc)->not->toBeNull();

    $warehouseTypeId = BuildingType::where('slug', 'warehouse')->value('id');
    $c->call('newBuilding')->set('name', 'Warehouse A')->set('buildingTypeId', $warehouseTypeId)->call('save')->assertHasNoErrors();
    $building = Building::where('name', 'Warehouse A')->first();
    expect($building->location_id)->toBe($loc->id);
    expect($building->building_type_id)->toBe($warehouseTypeId);

    $c->call('newRoom')->set('name', 'Storage 1')->set('function', 'storage')->call('save')->assertHasNoErrors();
    $room = Room::where('name', 'Storage 1')->first();
    expect($room->building_id)->toBe($building->id);
});

test('non-permitted user cannot open facilities', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => false]));
    Livewire::test(Facilities::class)->assertForbidden();
});

test('super admin can add a building type', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(Facilities::class)
        ->call('openTypesManager')
        ->set('typeName', 'Substation')
        ->call('saveType')
        ->assertHasNoErrors();

    expect(BuildingType::where('name', 'Substation')->exists())->toBeTrue();
});

test('a room can be soft-deleted with a reason and restored', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
    $loc = Location::create(['slug' => 'l', 'name' => 'Site', 'is_active' => true]);
    $bt = BuildingType::where('is_active', true)->first();
    $bld = Building::create(['location_id' => $loc->id, 'building_type_id' => $bt->id, 'slug' => 'b', 'name' => 'WH', 'is_active' => true]);
    $room = Room::create(['building_id' => $bld->id, 'slug' => 'r', 'name' => 'Store 1', 'is_active' => true]);

    Livewire::test(Facilities::class)
        ->call('selectLocation', $loc->id)
        ->call('selectBuilding', $bld->id)
        ->call('openDelete', 'room', $room->id)
        ->set('deleteReason', 'ຮື້ ຫ້ອງ ຖິ້ມ')
        ->call('deleteRecord')
        ->assertHasNoErrors();

    expect(Room::find($room->id))->toBeNull();
    expect(Room::withTrashed()->find($room->id)->deleted_reason)->toBe('ຮື້ ຫ້ອງ ຖິ້ມ');

    Livewire::test(Facilities::class)
        ->call('selectLocation', $loc->id)
        ->call('selectBuilding', $bld->id)
        ->call('toggleDeletedLog', 'room')
        ->assertViewHas('showDelRoom', true)
        ->assertSee('Store 1')
        ->call('restoreRecord', 'room', $room->id);
    expect(Room::find($room->id))->not->toBeNull();
});

test('a location with buildings cannot be deleted', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
    $loc = Location::create(['slug' => 'l', 'name' => 'Site', 'is_active' => true]);
    $bt = BuildingType::where('is_active', true)->first();
    Building::create(['location_id' => $loc->id, 'building_type_id' => $bt->id, 'slug' => 'b', 'name' => 'WH', 'is_active' => true]);

    Livewire::test(Facilities::class)->call('openDelete', 'location', $loc->id)->assertHasErrors('row');
    expect(Location::find($loc->id))->not->toBeNull();
});
