<?php

use App\Livewire\Disposal\Create;
use App\Models\Department;
use App\Models\Equipment;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $unit = Unit::create(['slug' => 'u1', 'name' => 'Ops', 'is_active' => true]);
    $this->d1 = Department::create(['slug' => 'd1', 'name' => 'Dept 1', 'unit_id' => $unit->id, 'is_active' => true]);
    $this->d2 = Department::create(['slug' => 'd2', 'name' => 'Dept 2', 'unit_id' => $unit->id, 'is_active' => true]);

    $mk = fn ($code, $dept) => Equipment::create(['asset_code' => $code, 'name' => $code, 'quantity' => 1,
        'status_counts' => ['active' => 0, 'repair' => 0, 'retired' => 1],
        'condition_status' => 'beyond_repair', 'department_id' => $dept]);
    $mk('EQ-A', $this->d1->id);
    $mk('EQ-B', $this->d2->id);
});

test('a department-scoped preparer (line_manager) auto-pulls only their own department', function () {
    $lm = User::factory()->create(['department_id' => $this->d1->id]);
    $lm->assignRole('line_manager');
    $this->actingAs($lm);

    $c = Livewire::test(Create::class)->call('autoPull')->assertHasNoErrors();
    $items = $c->get('items');
    expect($items)->toHaveCount(1);
    expect($items[0]['asset_code'])->toBe('EQ-A');
    expect($c->call('pullCount')->get('items'))->not->toBeNull(); // component alive
});

test('a broad role (warehouse_staff) auto-pulls across departments', function () {
    $ws = User::factory()->create(['department_id' => $this->d1->id]);
    $ws->assignRole('warehouse_staff');
    $this->actingAs($ws);

    $c = Livewire::test(Create::class)->call('autoPull')->assertHasNoErrors();
    expect($c->get('items'))->toHaveCount(2);
});
