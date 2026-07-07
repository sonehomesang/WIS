<?php

use App\Livewire\Equipment\MaintenanceTemplates;
use App\Models\Equipment;
use App\Models\MaintenanceTemplate;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('admin can create a maintenance template with per-cycle check/replace actions', function () {
    actingAs(User::factory()->create(['is_super_admin' => true]));
    $e = Equipment::create(['asset_code' => 'FL-9', 'name' => 'Forklift 9', 'category' => 'Forklift', 'quantity' => 1]);

    Livewire::test(MaintenanceTemplates::class)
        ->call('newTemplate')
        ->set('tName', 'Forklift PM')
        ->set('tEquipmentId', $e->id)
        ->set('tItems', [
            ['label' => 'ປ່ຽນ ນ້ຳມັນ ເຄື່ອງ', 'remark' => 'SAE15W-40',
                'cycles' => ['annual' => 'X', 'daily' => 'C', 'monthly' => 'X', 'quarterly' => 'X', 'semi_annual' => 'X']],
            ['label' => 'ກວດ ລົມ ຢາງ', 'remark' => '', 'cycles' => ['daily' => 'C']],
            ['label' => '', 'remark' => 'ຂໍ້ ວ່າງ', 'cycles' => ['monthly' => 'C']],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $t = MaintenanceTemplate::first();
    expect($t->equipment_id)->toBe($e->id);
    expect($t->category)->toBe('Forklift');           // ດຶງ ຈາກ ເຄື່ອງ
    // ຂໍ້ ວ່າງ ຖືກ ຕັດ · cycles ຮຽງ ຕາມ ລຳດັບ ຮອບ (daily→annual) · action C/X ຮັກສາ ໄວ້
    expect($t->normalizedItems())->toBe([
        ['label' => 'ປ່ຽນ ນ້ຳມັນ ເຄື່ອງ', 'remark' => 'SAE15W-40',
            'cycles' => ['daily' => 'C', 'monthly' => 'X', 'quarterly' => 'X', 'semi_annual' => 'X', 'annual' => 'X']],
        ['label' => 'ກວດ ລົມ ຢາງ', 'remark' => '', 'cycles' => ['daily' => 'C']],
    ]);
});

test('itemsForCycle returns only items due that cycle with their action', function () {
    $e = Equipment::create(['asset_code' => 'FL-8', 'name' => 'Forklift 8', 'quantity' => 1]);
    $t = MaintenanceTemplate::create([
        'name' => 'PM', 'equipment_id' => $e->id, 'is_active' => true,
        'items' => [
            ['label' => 'ນ້ຳມັນ ເຄື່ອງ', 'remark' => '', 'cycles' => ['daily' => 'C', 'annual' => 'X']],
            ['label' => 'return filter', 'remark' => '271A7', 'cycles' => ['annual' => 'X']],
        ],
    ]);

    expect($t->itemsForCycle('daily'))->toBe([
        ['label' => 'ນ້ຳມັນ ເຄື່ອງ', 'remark' => '', 'action' => 'C'],
    ]);
    expect($t->itemsForCycle('annual'))->toBe([
        ['label' => 'ນ້ຳມັນ ເຄື່ອງ', 'remark' => '', 'action' => 'X'],
        ['label' => 'return filter', 'remark' => '271A7', 'action' => 'X'],
    ]);
    expect($t->itemsForCycle('quarterly'))->toBe([]);
});

test('bumpCycle rotates a cell through none, check, replace', function () {
    actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(MaintenanceTemplates::class)
        ->call('newTemplate')                                    // ສ້າງ ຂໍ້ ເປົ່າ 1 ຂໍ້
        ->call('bumpCycle', 0, 'semi_annual')
        ->assertSet('tItems.0.cycles.semi_annual', 'C')
        ->call('bumpCycle', 0, 'semi_annual')
        ->assertSet('tItems.0.cycles.semi_annual', 'X')
        ->call('bumpCycle', 0, 'semi_annual')
        ->assertSet('tItems.0.cycles.semi_annual', null);        // ກັບ ໄປ ວ່າງ
});

test('creating a template requires selecting an equipment', function () {
    actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(MaintenanceTemplates::class)
        ->call('newTemplate')
        ->set('tName', 'No equipment')
        ->call('save')
        ->assertHasErrors('tEquipmentId');

    expect(MaintenanceTemplate::count())->toBe(0);
});

test('editing a template updates it without creating a new one', function () {
    actingAs(User::factory()->create(['is_super_admin' => true]));
    $e = Equipment::create(['asset_code' => 'PMP-1', 'name' => 'Pump', 'category' => 'Pump', 'quantity' => 1]);
    $t = MaintenanceTemplate::create([
        'name' => 'Old', 'equipment_id' => $e->id, 'category' => 'Pump',
        'items' => [['label' => 'A', 'remark' => '', 'cycles' => ['monthly' => 'C']]], 'is_active' => true,
    ]);

    Livewire::test(MaintenanceTemplates::class)
        ->call('editTemplate', $t->id)
        ->assertSet('tName', 'Old')
        ->assertSet('tEquipmentId', $e->id)
        ->assertSet('tItems.0.cycles.monthly', 'C')
        ->set('tName', 'New name')
        ->set('tActive', false)
        ->call('save')
        ->assertHasNoErrors();

    expect(MaintenanceTemplate::count())->toBe(1);
    expect($t->fresh()->name)->toBe('New name');
    expect($t->fresh()->is_active)->toBeFalse();
});

test('legacy freqs items are read as check actions', function () {
    $e = Equipment::create(['asset_code' => 'LG-1', 'name' => 'Legacy', 'quantity' => 1]);
    $t = MaintenanceTemplate::create([
        'name' => 'Legacy', 'equipment_id' => $e->id, 'is_active' => true,
        'items' => [['label' => 'old item', 'freqs' => ['monthly', 'annual']]],
    ]);

    expect($t->normalizedItems())->toBe([
        ['label' => 'old item', 'remark' => '', 'cycles' => ['monthly' => 'C', 'annual' => 'C']],
    ]);
});

test('an admin can delete a maintenance template', function () {
    actingAs(User::factory()->create(['is_super_admin' => true]));
    $t = MaintenanceTemplate::create(['name' => 'Doomed', 'items' => [], 'is_active' => true]);

    Livewire::test(MaintenanceTemplates::class)
        ->call('delete', $t->id);

    expect(MaintenanceTemplate::find($t->id))->toBeNull();
});

test('a requester cannot access the maintenance template manager', function () {
    $u = User::factory()->create();
    $u->syncRoles(['requester']);
    actingAs($u);

    Livewire::test(MaintenanceTemplates::class)->assertForbidden();
});

test('a department-scoped admin cannot access the central template manager', function () {
    $u = User::factory()->create();
    $u->syncRoles(['department_admin']);
    actingAs($u);

    Livewire::test(MaintenanceTemplates::class)->assertForbidden();
});
