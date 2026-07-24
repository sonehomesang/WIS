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

test('a maintenance template is deleted with a required reason and can be restored', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    actingAs($admin);
    $t = MaintenanceTemplate::create(['name' => 'Doomed', 'items' => [], 'is_active' => true]);

    // reason required
    Livewire::test(MaintenanceTemplates::class)
        ->call('openDelete', $t->id)
        ->call('deleteRecord')
        ->assertHasErrors(['deleteReason' => 'required']);
    expect(MaintenanceTemplate::find($t->id))->not->toBeNull();

    // delete with reason → soft-deleted + metadata, restorable
    Livewire::test(MaintenanceTemplates::class)
        ->call('openDelete', $t->id)
        ->set('deleteReason', 'ບໍ່ ໃຊ້ ແລ້ວ')
        ->call('deleteRecord')
        ->assertHasNoErrors();
    expect(MaintenanceTemplate::find($t->id))->toBeNull();
    $trashed = MaintenanceTemplate::withTrashed()->find($t->id);
    expect($trashed->deleted_reason)->toBe('ບໍ່ ໃຊ້ ແລ້ວ');
    expect($trashed->deleted_by)->toBe($admin->id);

    Livewire::test(MaintenanceTemplates::class)->call('restore', $t->id);
    expect(MaintenanceTemplate::find($t->id))->not->toBeNull();
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

test('a maintenance template can be scoped to a category or general (no equipment needed)', function () {
    actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(MaintenanceTemplates::class)
        ->call('newTemplate')
        ->set('tName', 'Cat PM')->set('tScope', 'category')->set('tCategory', 'Vehicles')
        ->set('tItems', [['label' => 'Grease', 'remark' => '', 'cycles' => ['monthly' => 'C']]])
        ->call('save')->assertHasNoErrors();
    $cat = MaintenanceTemplate::where('name', 'Cat PM')->first();
    expect($cat->equipment_id)->toBeNull();
    expect($cat->category)->toBe('Vehicles');

    Livewire::test(MaintenanceTemplates::class)
        ->call('newTemplate')
        ->set('tName', 'Gen PM')->set('tScope', 'general')
        ->set('tItems', [['label' => 'Walk-around', 'remark' => '', 'cycles' => ['daily' => 'C']]])
        ->call('save')->assertHasNoErrors();
    $gen = MaintenanceTemplate::where('name', 'Gen PM')->first();
    expect($gen->equipment_id)->toBeNull();
    expect($gen->category)->toBeNull();
});

test('category scope requires a category', function () {
    actingAs(User::factory()->create(['is_super_admin' => true]));
    Livewire::test(MaintenanceTemplates::class)
        ->call('newTemplate')
        ->set('tName', 'No cat')->set('tScope', 'category')->set('tCategory', '')
        ->call('save')->assertHasErrors('tCategory');
});

test('maintenance templates list is narrowed by search + category filter', function () {
    actingAs(User::factory()->create(['is_super_admin' => true]));
    MaintenanceTemplate::create(['name' => 'PM Forklift', 'category' => 'Forklift', 'items' => [], 'is_active' => true]);
    MaintenanceTemplate::create(['name' => 'PM Sling', 'category' => 'Sling', 'items' => [], 'is_active' => true]);

    Livewire::test(MaintenanceTemplates::class)->set('search', 'Sling')
        ->assertViewHas('templates', fn ($t) => $t->count() === 1 && $t->first()->name === 'PM Sling');
    Livewire::test(MaintenanceTemplates::class)->set('categoryFilter', 'Forklift')
        ->assertViewHas('templates', fn ($t) => $t->count() === 1 && $t->first()->category === 'Forklift');
});
