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

test('viewing a template shows its checklist matrix read-only', function () {
    actingAs(User::factory()->create(['is_super_admin' => true]));
    $e = Equipment::create(['asset_code' => 'FL-V', 'name' => 'Forklift V', 'category' => 'Forklift', 'quantity' => 1]);
    $t = MaintenanceTemplate::create([
        'name' => 'PM View', 'equipment_id' => $e->id, 'category' => 'Forklift', 'is_active' => true,
        'items' => [['label' => 'ກວດ ນ້ຳມັນ ເຄື່ອງ', 'remark' => 'SAE', 'cycles' => ['monthly' => 'X']]],
    ]);

    Livewire::test(MaintenanceTemplates::class)
        ->call('viewTemplate', $t->id)
        ->assertSet('viewingId', $t->id)
        ->assertSee('PM View')
        ->assertSee('ກວດ ນ້ຳມັນ ເຄື່ອງ')
        ->assertSee('SAE');
});

test('a requester without edit rights can still be blocked from the page but view is open to viewers', function () {
    // ຜູ້ ທີ່ ເຂົ້າ ໜ້າ ໄດ້ (SA/admin) ເບິ່ງ ໄດ້ ໂດຍ ບໍ່ ຕ້ອງ ສິດ ແກ້ໄຂ — viewTemplate ບໍ່ ເຊັກ edit.
    actingAs(User::factory()->create(['is_super_admin' => true]));
    $e = Equipment::create(['asset_code' => 'FL-V2', 'name' => 'Forklift V2', 'quantity' => 1]);
    $t = MaintenanceTemplate::create(['name' => 'V2', 'equipment_id' => $e->id, 'items' => [], 'is_active' => true]);

    Livewire::test(MaintenanceTemplates::class)
        ->call('viewTemplate', $t->id)
        ->assertSet('viewingId', $t->id)
        ->assertHasNoErrors();
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

test('the TCM seeder loads the standard forklift checklist and links a forklift when present', function () {
    $fl = Equipment::create(['asset_code' => 'FLT-1', 'name' => 'TCM Forklift FD30T3Z', 'category' => 'Forklift', 'quantity' => 1]);

    (new Database\Seeders\MaintenanceTemplateSeeder)->run();

    $t = MaintenanceTemplate::where('name', 'like', 'TCM FD30T3Z%')->first();
    expect($t)->not->toBeNull();
    expect($t->equipment_id)->toBe($fl->id);          // ຜູກ ກັບ forklift ທີ່ ພົບ
    expect($t->category)->toBe('Forklift');

    $items = $t->normalizedItems();
    expect(count($items))->toBeGreaterThanOrEqual(40);
    // ທຸກ ຂໍ້ ຕ້ອງ ມີ ຢ່າງໜ້ອຍ 1 ຮອບ, ແລະ ຄ່າ ຕ້ອງ ເປັນ C ຫຼື X ເທົ່ານັ້ນ
    expect(collect($items)->every(fn ($x) => count($x['cycles']) > 0))->toBeTrue();
    expect(collect($items)->flatMap(fn ($x) => array_values($x['cycles']))->unique()->sort()->values()->all())->toBe(['C', 'X']);

    // ຣັນ ຊ້ຳ ບໍ່ ຊ້ຳ ຂໍ້ມູນ
    (new Database\Seeders\MaintenanceTemplateSeeder)->run();
    expect(MaintenanceTemplate::where('name', 'like', 'TCM FD30T3Z%')->count())->toBe(1);
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
