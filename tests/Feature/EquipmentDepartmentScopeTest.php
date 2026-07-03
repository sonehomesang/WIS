<?php

use App\Livewire\Equipment\Index;
use App\Livewire\Equipment\InspectionTemplates;
use App\Models\Department;
use App\Models\Equipment;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $unit = Unit::create(['slug' => 'wh', 'name' => 'Warehouse Unit', 'is_active' => true]);
    $this->deptA = Department::create(['unit_id' => $unit->id, 'slug' => 'dept-a', 'name' => 'Dept A', 'is_active' => true]);
    $this->deptB = Department::create(['unit_id' => $unit->id, 'slug' => 'dept-b', 'name' => 'Dept B', 'is_active' => true]);
    $this->eqA = Equipment::create(['asset_code' => 'EQ-A', 'name' => 'Drill A', 'quantity' => 1, 'department_id' => $this->deptA->id]);
    $this->eqB = Equipment::create(['asset_code' => 'EQ-B', 'name' => 'Drill B', 'quantity' => 1, 'department_id' => $this->deptB->id]);

    $this->deptAdmin = User::factory()->create(['department_id' => $this->deptA->id]);
    $this->deptAdmin->syncRoles(['department_admin']);
});

test('department_admin sees only its own department equipment', function () {
    actingAs($this->deptAdmin);
    Livewire::test(Index::class)
        ->assertSee('EQ-A')
        ->assertDontSee('EQ-B');
});

test('a full-scope admin sees equipment from every department', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(['admin']);
    actingAs($admin);
    Livewire::test(Index::class)
        ->assertSee('EQ-A')
        ->assertSee('EQ-B');
});

test('department_admin cannot edit equipment from another department', function () {
    actingAs($this->deptAdmin);
    Livewire::test(Index::class)
        ->call('editItem', $this->eqB->id)
        ->assertForbidden();
});

test('department_admin new equipment is forced to its own department', function () {
    actingAs($this->deptAdmin);
    Livewire::test(Index::class)
        ->call('newItem')
        ->assertSet('department_id', $this->deptA->id)   // preset + locked
        ->set('asset_code', 'EQ-NEW')
        ->set('name', 'New Saw')
        ->set('quantity', 1)
        ->set('department_id', $this->deptB->id)          // attempt to sneak into Dept B
        ->set('loc_bin', 'A-01-03')
        ->call('save')
        ->assertHasNoErrors();

    $eq = Equipment::where('asset_code', 'EQ-NEW')->first();
    expect($eq->department_id)->toBe($this->deptA->id);   // forced back to own dept
    expect($eq->loc_bin)->toBe('A-01-03');
});

test('department_admin cannot open the shared template manager', function () {
    actingAs($this->deptAdmin);
    Livewire::test(InspectionTemplates::class)->assertForbidden();
});

test('owner and loc-bin are saved by a full-scope staff member', function () {
    $staff = User::factory()->create();
    $staff->syncRoles(['warehouse_staff']);
    actingAs($staff);
    Livewire::test(Index::class)
        ->call('newItem')
        ->set('asset_code', 'EQ-OWN')
        ->set('name', 'Grinder')
        ->set('quantity', 1)
        ->set('department_id', $this->deptB->id)
        ->set('loc_bin', 'WH-Rack-B2')
        ->call('save')
        ->assertHasNoErrors();

    $eq = Equipment::where('asset_code', 'EQ-OWN')->first();
    expect($eq->department_id)->toBe($this->deptB->id);
    expect($eq->loc_bin)->toBe('WH-Rack-B2');
});
