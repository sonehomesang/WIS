<?php

use App\Livewire\Settings\Facilities;
use App\Models\Building;
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

    $warehouseTypeId = \App\Models\BuildingType::where('slug', 'warehouse')->value('id');
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

    expect(\App\Models\BuildingType::where('name', 'Substation')->exists())->toBeTrue();
});
