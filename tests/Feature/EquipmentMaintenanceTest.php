<?php

use App\Livewire\Equipment\Maintenance;
use App\Models\Department;
use App\Models\Equipment;
use App\Models\EquipmentMaintenance;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->staff = User::factory()->create();
    $this->staff->syncRoles(['warehouse_staff']);
});

test('warehouse staff can record a maintenance', function () {
    $e = Equipment::create(['asset_code' => 'MT-01', 'name' => 'Generator', 'quantity' => 1]);

    actingAs($this->staff);
    Livewire::test(Maintenance::class)
        ->call('newMaintenance')
        ->call('pickEquipment', $e->id)
        ->set('mDate', '2026-07-06')
        ->set('mType', 'repair')
        ->set('mTitle', 'ປ່ຽນ ໝໍ້ ໄຟ')
        ->set('mCost', '450000')
        ->call('save')
        ->assertHasNoErrors();

    expect($e->maintenances()->count())->toBe(1);
    $m = $e->maintenances()->first();
    expect($m->type)->toBe('repair');
    expect($m->title)->toBe('ປ່ຽນ ໝໍ້ ໄຟ');
    expect((float) $m->cost)->toBe(450000.0);
    expect($m->created_by)->toBe($this->staff->id);
});

test('planning ahead creates a planned maintenance record', function () {
    $e = Equipment::create(['asset_code' => 'MT-07', 'name' => 'Chiller', 'quantity' => 1]);

    actingAs($this->staff);
    Livewire::test(Maintenance::class)
        ->call('newPlan')
        ->assertSet('planning', true)
        ->assertSet('mStatus', 'planned')
        ->call('pickEquipment', $e->id)
        ->set('mDate', '2026-09-01')
        ->set('mTitle', 'ນັດ Service ຮອບ ໜ້າ')
        ->call('save')
        ->assertHasNoErrors();

    $m = $e->maintenances()->first();
    expect($m->status)->toBe('planned');
    expect($m->title)->toBe('ນັດ Service ຮອບ ໜ້າ');
    expect($m->cost)->toBeNull();
});

test('cost is optional and stored as null when blank', function () {
    $e = Equipment::create(['asset_code' => 'MT-02', 'name' => 'Pump', 'quantity' => 1]);

    actingAs($this->staff);
    Livewire::test(Maintenance::class)
        ->call('newMaintenance')
        ->call('pickEquipment', $e->id)
        ->set('mDate', '2026-07-06')
        ->set('mTitle', 'ກວດ ທົ່ວໄປ')
        ->call('save')
        ->assertHasNoErrors();

    expect($e->maintenances()->first()->cost)->toBeNull();
});

test('choosing a service frequency auto-fills the next service date', function () {
    $e = Equipment::create(['asset_code' => 'MT-03', 'name' => 'Truck', 'quantity' => 1]);

    actingAs($this->staff);
    Livewire::test(Maintenance::class)
        ->call('newMaintenance')
        ->call('pickEquipment', $e->id)
        ->set('mDate', '2026-07-06')
        ->set('mTitle', 'Service ຮອບ')
        ->set('mType', 'service')
        ->set('mFrequency', 'quarterly')     // +3 months
        ->assertSet('mNextService', '2026-10-06')
        ->call('save')
        ->assertHasNoErrors();

    expect($e->maintenances()->first()->next_service_date->toDateString())->toBe('2026-10-06');
});

test('maintenance evidence photos are stored', function () {
    Storage::fake('public');
    $e = Equipment::create(['asset_code' => 'MT-04', 'name' => 'Compressor', 'quantity' => 1]);

    actingAs($this->staff);
    Livewire::test(Maintenance::class)
        ->call('newMaintenance')
        ->call('pickEquipment', $e->id)
        ->set('mDate', '2026-07-06')
        ->set('mTitle', 'ຊ່ອມ')
        ->set('mPhotos', [
            UploadedFile::fake()->image('fix1.jpg', 800, 600),
            UploadedFile::fake()->image('fix2.jpg', 800, 600),
        ])
        ->call('save')
        ->assertHasNoErrors();

    $m = $e->maintenances()->first();
    expect($m->photos)->toHaveCount(2);
    foreach ($m->photos as $p) {
        Storage::disk('public')->assertExists($p);
    }
});

test('an admin can delete a maintenance record', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(['admin']);
    $e = Equipment::create(['asset_code' => 'MT-05', 'name' => 'Saw', 'quantity' => 1]);
    $m = $e->maintenances()->create([
        'maintenance_date' => '2026-07-06', 'type' => 'other', 'title' => 'x', 'status' => 'done',
    ]);

    actingAs($admin);
    Livewire::test(Maintenance::class)
        ->call('delete', $m->id);

    expect(EquipmentMaintenance::find($m->id))->toBeNull();
    expect($e->maintenances()->count())->toBe(0);
});

test('warehouse staff without delete permission cannot delete a maintenance', function () {
    $e = Equipment::create(['asset_code' => 'MT-06', 'name' => 'Lathe', 'quantity' => 1]);
    $m = $e->maintenances()->create([
        'maintenance_date' => '2026-07-06', 'type' => 'other', 'title' => 'x', 'status' => 'done',
    ]);

    actingAs($this->staff);
    Livewire::test(Maintenance::class)
        ->call('delete', $m->id)
        ->assertForbidden();

    expect($e->maintenances()->count())->toBe(1);
});

test('department_admin sees and manages only its own department maintenance', function () {
    $unit = Unit::create(['slug' => 'wh', 'name' => 'WH Unit', 'is_active' => true]);
    $deptA = Department::create(['unit_id' => $unit->id, 'slug' => 'a', 'name' => 'Dept A', 'is_active' => true]);
    $deptB = Department::create(['unit_id' => $unit->id, 'slug' => 'b', 'name' => 'Dept B', 'is_active' => true]);
    $eqA = Equipment::create(['asset_code' => 'MT-A', 'name' => 'Aaa Machine', 'quantity' => 1, 'department_id' => $deptA->id]);
    $eqB = Equipment::create(['asset_code' => 'MT-B', 'name' => 'Bbb Machine', 'quantity' => 1, 'department_id' => $deptB->id]);
    $eqA->maintenances()->create(['maintenance_date' => '2026-07-06', 'type' => 'other', 'title' => 'Job A', 'status' => 'done']);
    $mB = $eqB->maintenances()->create(['maintenance_date' => '2026-07-06', 'type' => 'other', 'title' => 'Job B', 'status' => 'done']);

    $admin = User::factory()->create(['department_id' => $deptA->id]);
    $admin->syncRoles(['department_admin']);

    actingAs($admin);
    Livewire::test(Maintenance::class)
        ->assertSee('Job A')
        ->assertDontSee('Job B')
        ->call('editMaintenance', $mB->id)
        ->assertForbidden();
});
