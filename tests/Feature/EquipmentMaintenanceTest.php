<?php

use App\Livewire\Equipment\Maintenance;
use App\Models\Department;
use App\Models\Equipment;
use App\Models\EquipmentMaintenance;
use App\Models\MaintenanceTemplate;
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

test('recording with a template + cycle loads the checklist filtered by cycle and saves a snapshot', function () {
    $e = Equipment::create(['asset_code' => 'MT-CK', 'name' => 'Forklift', 'quantity' => 1]);
    $t = MaintenanceTemplate::create([
        'name' => 'PM', 'equipment_id' => $e->id, 'is_active' => true,
        'items' => [
            ['label' => 'ນ້ຳມັນ ເຄື່ອງ', 'remark' => 'SAE', 'cycles' => ['monthly' => 'X', 'quarterly' => 'X']],
            ['label' => 'ໄສ້ກອງ ອາກາດ', 'remark' => '', 'cycles' => ['quarterly' => 'X']],   // ໄຕມາດ ເທົ່ານັ້ນ
            ['label' => 'ລົມ ຢາງ', 'remark' => '', 'cycles' => ['monthly' => 'C']],
        ],
    ]);

    actingAs($this->staff);
    Livewire::test(Maintenance::class)
        ->call('newMaintenance')
        ->call('pickEquipment', $e->id)
        ->set('mTitle', 'Service ຮອບ ເດືອນ')
        ->set('mTemplateId', $t->id)
        ->set('mFrequency', 'monthly')          // ຄັດ ລາຍການ ຮອບ ເດືອນ
        ->assertCount('mChecklist', 2)          // ນ້ຳມັນ(X) + ລົມຢາງ(C); ໄສ້ກອງ ບໍ່ ຂຶ້ນ (ໄຕມາດ)
        ->call('toggleMaintChecklist', 1, 'ng')
        ->call('save')
        ->assertHasNoErrors();

    $m = $e->maintenances()->first();
    expect($m->template_id)->toBe($t->id);
    expect($m->frequency)->toBe('monthly');
    expect($m->checklist)->toHaveCount(2);
    expect($m->checklist[0]['action'])->toBe('X');    // ນ້ຳມັນ = ປ່ຽນ
    expect($m->checklist[0]['status'])->toBe('na');   // ຄ່າ ຕັ້ງຕົ້ນ = ຍັງ ບໍ່ ໝາຍ
    expect($m->checklist[1]['status'])->toBe('ng');   // ລົມ ຢາງ = ມີ ບັນຫາ
});

test('the recording checklist is grouped by work-section and the filters narrow it', function () {
    $e = Equipment::create(['asset_code' => 'MT-GRP', 'name' => 'Forklift', 'quantity' => 1]);
    $t = MaintenanceTemplate::create([
        'name' => 'PM grouped', 'equipment_id' => $e->id, 'is_active' => true,
        'items' => [
            ['group' => 'engine', 'label' => 'ນ້ຳມັນ ເຄື່ອງ', 'remark' => '', 'cycles' => ['monthly' => 'X']],
            ['group' => 'engine', 'label' => 'ກວດ ສາຍພານ', 'remark' => '', 'cycles' => ['monthly' => 'C']],
            ['group' => 'hydraulic', 'label' => 'ນ້ຳມັນ ໄຮໂດຼລິກ', 'remark' => '', 'cycles' => ['monthly' => 'X']],
        ],
    ]);

    actingAs($this->staff);
    $c = Livewire::test(Maintenance::class)
        ->call('newMaintenance')
        ->call('pickEquipment', $e->id)
        ->set('mTemplateId', $t->id)
        ->set('mFrequency', 'monthly')
        ->assertCount('mChecklist', 3);

    // group is carried onto every checklist item
    expect($c->get('mChecklist')[0]['group'])->toBe('engine');

    // grouped for the view: engine 2 · hydraulic 1
    $c->assertViewHas('checklistGroups', fn ($g) => count($g['engine']) === 2 && count($g['hydraulic']) === 1);

    // action filter X → engine 1 (ນ້ຳມັນ) + hydraulic 1
    $c->set('ckAction', 'X')
        ->assertViewHas('checklistGroups', fn ($g) => count($g['engine'] ?? []) === 1 && count($g['hydraulic'] ?? []) === 1);

    // NG filter → only the item flagged ng
    $c->set('ckAction', '')
        ->call('toggleMaintChecklist', 0, 'ng')   // ນ້ຳມັນ ເຄື່ອງ = ng
        ->set('ckNg', true)
        ->assertViewHas('checklistNgCount', 1)
        ->assertViewHas('checklistGroups', fn ($g) => count($g) === 1 && count($g['engine']) === 1);

    // clearing the filter restores every item
    $c->call('resetChecklistFilter')
        ->assertViewHas('checklistGroups', fn ($g) => count($g['engine']) === 2 && count($g['hydraulic']) === 1);
});

test('evidence photo appears only on NG; a per-item note is stored', function () {
    Storage::fake('public');
    $e = Equipment::create(['asset_code' => 'MT-PH', 'name' => 'Forklift', 'quantity' => 1]);
    $t = MaintenanceTemplate::create([
        'name' => 'PM ph', 'equipment_id' => $e->id, 'is_active' => true,
        'items' => [
            ['group' => 'engine', 'label' => 'ປ່ຽນ ນ້ຳມັນ', 'remark' => '', 'cycles' => ['monthly' => 'X']],
            ['group' => 'other', 'label' => 'ກວດ ຢາງ', 'remark' => '', 'cycles' => ['monthly' => 'C']],
        ],
    ]);

    actingAs($this->staff);
    Livewire::test(Maintenance::class)
        ->call('newMaintenance')
        ->call('pickEquipment', $e->id)
        ->set('mTitle', 'Service')
        ->set('mTemplateId', $t->id)
        ->set('mFrequency', 'monthly')
        ->assertCount('mChecklist', 2)                              // ຂໍ້ 0 = X (ປ່ຽນ), ຂໍ້ 1 = C
        ->call('toggleMaintChecklist', 0, 'ng')                    // X ມີ ບັນຫາ → ຊ່ອງ ຫຼັກຖານ
        ->set('mChecklist.0.note', 'ໃຊ້ ອາໄຫຼ່ OEM')               // ໝາຍເຫດ/ອ້າງອີງ ຕໍ່ ຂໍ້
        ->set('itemPhotoProblem.0', UploadedFile::fake()->image('problem.jpg', 400, 300))
        ->call('toggleMaintChecklist', 1, 'ok')                    // C ຜ່ານ (✓) → ບໍ່ມີ ຊ່ອງ ຮູບ
        ->call('save')
        ->assertHasNoErrors();

    $m = $e->maintenances()->first();
    expect($m->checklist[0]['note'] ?? null)->toBe('ໃຊ້ ອາໄຫຼ່ OEM');   // ໝາຍເຫດ ຖືກ ເກັບ
    expect($m->checklist[0]['photo_problem'] ?? null)->not->toBeNull();  // NG → ຮູບ ຫຼັກຖານ
    Storage::disk('public')->assertExists($m->checklist[0]['photo_problem']);
    // the ✓ item keeps no evidence photos
    expect($m->checklist[1]['photo_problem'] ?? null)->toBeNull();
});

test('a checklist item marked NG stores a problem evidence photo or video clip', function () {
    Storage::fake('public');
    $e = Equipment::create(['asset_code' => 'MT-NG', 'name' => 'Forklift', 'quantity' => 1]);
    $t = MaintenanceTemplate::create([
        'name' => 'PM ng', 'equipment_id' => $e->id, 'is_active' => true,
        'items' => [
            ['group' => 'other', 'label' => 'ກວດ ໄຟ', 'remark' => '', 'cycles' => ['monthly' => 'C']],
            ['group' => 'engine', 'label' => 'ກວດ ສາຍພານ', 'remark' => '', 'cycles' => ['monthly' => 'C']],
        ],
    ]);

    actingAs($this->staff);
    Livewire::test(Maintenance::class)
        ->call('newMaintenance')
        ->call('pickEquipment', $e->id)
        ->set('mTitle', 'Inspect')
        ->set('mTemplateId', $t->id)
        ->set('mFrequency', 'monthly')
        ->call('toggleMaintChecklist', 0, 'ng')                   // ຂໍ້ 0 ມີ ບັນຫາ → ຮູບ
        ->set('itemPhotoProblem.0', UploadedFile::fake()->image('problem.jpg', 400, 300))
        ->call('toggleMaintChecklist', 1, 'ng')                   // ຂໍ້ 1 ມີ ບັນຫາ → ຄລິບ
        ->set('itemPhotoProblem.1', UploadedFile::fake()->create('clip.mp4', 500, 'video/mp4'))
        ->call('save')
        ->assertHasNoErrors();

    $m = $e->maintenances()->first();
    expect($m->checklist[0]['photo_problem'] ?? null)->not->toBeNull();
    expect($m->checklist[1]['photo_problem'] ?? null)->not->toBeNull();
    expect(str_ends_with($m->checklist[1]['photo_problem'], '.mp4'))->toBeTrue();   // ຄລິບ ວິດີໂອ ຮັບ ໄດ້
    Storage::disk('public')->assertExists($m->checklist[0]['photo_problem']);
    Storage::disk('public')->assertExists($m->checklist[1]['photo_problem']);
});

test('a fuel-typed template requires choosing EV/Engine and filters the checklist by it', function () {
    $e = Equipment::create(['asset_code' => 'MT-FUEL', 'name' => 'Forklift', 'quantity' => 1]);
    $t = MaintenanceTemplate::create([
        'name' => 'PM fuel', 'equipment_id' => $e->id, 'is_active' => true,
        'items' => [
            ['group' => 'engine', 'label' => 'ນ້ຳມັນ ເຄື່ອງ', 'remark' => '', 'cycles' => ['monthly' => 'X'], 'applies' => 'engine'],
            ['group' => 'other', 'label' => 'ຢາງ', 'remark' => '', 'cycles' => ['monthly' => 'C'], 'applies' => 'both'],
        ],
    ]);

    actingAs($this->staff);
    $c = Livewire::test(Maintenance::class)
        ->call('newMaintenance')
        ->call('pickEquipment', $e->id)
        ->set('mTitle', 'Service')
        ->set('mTemplateId', $t->id)
        ->set('mFrequency', 'monthly')
        ->assertViewHas('mTemplateHasFuelTypes', true)
        ->assertCount('mChecklist', 0);       // ຍັງ ບໍ່ ເລືອກ ປະເພດ ລົດ → ວ່າງ

    // saving without a fuel type is blocked
    $c->call('save')->assertHasErrors('mFuelType');

    // engine → both + engine (2), ev → only both (1)
    $c->set('mFuelType', 'engine')->assertCount('mChecklist', 2);
    $c->set('mFuelType', 'ev')->assertCount('mChecklist', 1);

    $c->call('save')->assertHasNoErrors();
    $m = $e->maintenances()->first();
    expect($m->checklist)->toHaveCount(1);    // ບັນທຶກ ສະເພາະ ຂໍ້ ຂອງ EV (ຢາງ)
    expect($m->checklist[0]['label'])->toBe('ຢາງ');
});

test('changing the cycle re-filters the checklist and clearing the template empties it', function () {
    $e = Equipment::create(['asset_code' => 'MT-CK3', 'name' => 'Loader', 'quantity' => 1]);
    $t = MaintenanceTemplate::create([
        'name' => 'PM', 'equipment_id' => $e->id, 'is_active' => true,
        'items' => [
            ['label' => 'A', 'remark' => '', 'cycles' => ['monthly' => 'C']],
            ['label' => 'B', 'remark' => '', 'cycles' => ['quarterly' => 'X']],
        ],
    ]);

    actingAs($this->staff);
    Livewire::test(Maintenance::class)
        ->call('newMaintenance')
        ->call('pickEquipment', $e->id)
        ->set('mTemplateId', $t->id)
        ->set('mFrequency', 'monthly')->assertCount('mChecklist', 1)
        ->set('mFrequency', 'quarterly')->assertCount('mChecklist', 1)
        ->set('mFrequency', 'annual')->assertCount('mChecklist', 0)     // ບໍ່ ມີ ຂໍ້ ຮອບ ປີ
        ->set('mTemplateId', '')->set('mFrequency', 'monthly')->assertCount('mChecklist', 0);   // ບໍ່ ໃຊ້ ແມ່ແບບ → ວ່າງ
});

test('editing a maintenance record loads its saved checklist', function () {
    $e = Equipment::create(['asset_code' => 'MT-CK2', 'name' => 'Truck', 'quantity' => 1]);
    $t = MaintenanceTemplate::create(['name' => 'T', 'equipment_id' => $e->id, 'is_active' => true,
        'items' => [['label' => 'X', 'remark' => '', 'cycles' => ['monthly' => 'C']]]]);
    $m = $e->maintenances()->create([
        'maintenance_date' => '2026-07-06', 'type' => 'service', 'title' => 's', 'status' => 'done',
        'template_id' => $t->id, 'frequency' => 'monthly',
        'checklist' => [['label' => 'X', 'remark' => '', 'action' => 'C', 'status' => 'ng']],
    ]);

    actingAs($this->staff);
    Livewire::test(Maintenance::class)
        ->call('editMaintenance', $m->id)
        ->assertSet('mTemplateId', $t->id)
        ->assertCount('mChecklist', 1)
        ->assertSet('mChecklist.0.status', 'ng');
});

test('an admin can delete a maintenance record', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(['admin']);
    $e = Equipment::create(['asset_code' => 'MT-05', 'name' => 'Saw', 'quantity' => 1]);
    $m = $e->maintenances()->create([
        'maintenance_date' => '2026-07-06', 'type' => 'other', 'title' => 'x', 'status' => 'done',
    ]);

    actingAs($admin);
    // reason required
    Livewire::test(Maintenance::class)
        ->call('openDelete', $m->id)
        ->call('deleteRecord')
        ->assertHasErrors(['deleteReason' => 'required']);
    expect(EquipmentMaintenance::find($m->id))->not->toBeNull();

    // delete with reason → soft-deleted + metadata, then restorable
    Livewire::test(Maintenance::class)
        ->call('openDelete', $m->id)
        ->set('deleteReason', 'ບັນທຶກ ຊ້ຳ')
        ->call('deleteRecord')
        ->assertHasNoErrors();

    expect(EquipmentMaintenance::find($m->id))->toBeNull();
    $trashed = EquipmentMaintenance::withTrashed()->find($m->id);
    expect($trashed->trashed())->toBeTrue();
    expect($trashed->deleted_reason)->toBe('ບັນທຶກ ຊ້ຳ');
    expect($trashed->deleted_by)->toBe($admin->id);

    Livewire::test(Maintenance::class)->call('restore', $m->id);
    expect(EquipmentMaintenance::find($m->id))->not->toBeNull();
});

test('warehouse staff without delete permission cannot delete a maintenance', function () {
    $e = Equipment::create(['asset_code' => 'MT-06', 'name' => 'Lathe', 'quantity' => 1]);
    $m = $e->maintenances()->create([
        'maintenance_date' => '2026-07-06', 'type' => 'other', 'title' => 'x', 'status' => 'done',
    ]);

    actingAs($this->staff);
    Livewire::test(Maintenance::class)
        ->call('openDelete', $m->id)
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

test('C2: creating CM from NG checklist items makes linked repair records, idempotently', function () {
    actingAs($this->staff);
    $e = Equipment::create(['asset_code' => 'FL-CM', 'name' => 'Forklift', 'quantity' => 1]);
    $pm = $e->maintenances()->create([
        'maintenance_date' => now()->toDateString(), 'type' => 'preventive', 'title' => 'PM monthly', 'status' => 'done',
        'checklist' => [
            ['label' => 'ກວດ ເບກ', 'status' => 'ok'],
            ['label' => 'ກວດ ຢາງ', 'status' => 'ng'],
            ['label' => 'ກວດ ໄຟ ສ່ອງ', 'status' => 'ng'],
        ],
    ]);

    Livewire::test(Maintenance::class)->call('createRepairsFromNg', $pm->id);

    $repairs = EquipmentMaintenance::where('type', 'repair')->where('source_maintenance_id', $pm->id)->get();
    expect($repairs)->toHaveCount(2);                                  // only the 2 NG items
    expect($repairs->pluck('title')->all())->toContain('ກວດ ຢາງ', 'ກວດ ໄຟ ສ່ອງ');
    expect($repairs->every(fn ($r) => $r->status === 'planned'))->toBeTrue();

    Livewire::test(Maintenance::class)->call('createRepairsFromNg', $pm->id);   // again
    expect(EquipmentMaintenance::where('source_maintenance_id', $pm->id)->count())->toBe(2);  // no duplicates
});

test('a maintenance record can be viewed read-only with its checklist', function () {
    actingAs($this->staff);
    $e = Equipment::create(['asset_code' => 'FL-VW', 'name' => 'Forklift', 'quantity' => 1]);
    $m = $e->maintenances()->create([
        'maintenance_date' => now()->toDateString(), 'type' => 'preventive', 'title' => 'PM viewable', 'status' => 'done',
        'checklist' => [['label' => 'ກວດ ເບກ', 'status' => 'ng']],
    ]);

    Livewire::test(Maintenance::class)
        ->call('viewRecord', $m->id)
        ->assertSet('viewingId', $m->id)
        ->assertSee('PM viewable')
        ->assertSee('ກວດ ເບກ');
});

test('the PM template picker matches by equipment, category, or general (+ show all)', function () {
    actingAs($this->staff);
    $eq = Equipment::create(['asset_code' => 'FL-M', 'name' => 'Forklift', 'quantity' => 1, 'category' => 'Vehicles']);

    MaintenanceTemplate::create(['name' => 'Specific PM', 'equipment_id' => $eq->id, 'category' => 'Vehicles', 'is_active' => true, 'items' => []]);
    MaintenanceTemplate::create(['name' => 'Category PM', 'equipment_id' => null, 'category' => 'Vehicles', 'is_active' => true, 'items' => []]);
    MaintenanceTemplate::create(['name' => 'General PM', 'equipment_id' => null, 'category' => null, 'is_active' => true, 'items' => []]);
    MaintenanceTemplate::create(['name' => 'Sling PM', 'equipment_id' => null, 'category' => 'Sling', 'is_active' => true, 'items' => []]);

    Livewire::test(Maintenance::class)
        ->call('newMaintenance')
        ->call('pickEquipment', $eq->id)
        ->assertViewHas('templateOptions', function ($o) {
            $n = $o->pluck('name')->all();

            return in_array('Specific PM', $n) && in_array('Category PM', $n)
                && in_array('General PM', $n) && ! in_array('Sling PM', $n);   // Sling excluded
        })
        ->set('mShowAllTemplates', true)
        ->assertViewHas('templateOptions', fn ($o) => $o->pluck('name')->contains('Sling PM'));   // show all
});

test('maintenance list search + type + status filters narrow the records', function () {
    actingAs(User::factory()->create(['is_super_admin' => true]));
    $e1 = Equipment::create(['asset_code' => 'FORK-1', 'name' => 'Forklift', 'quantity' => 1]);
    $e2 = Equipment::create(['asset_code' => 'DRILL-1', 'name' => 'Drill', 'quantity' => 1]);
    EquipmentMaintenance::create(['equipment_id' => $e1->id, 'maintenance_date' => now(), 'type' => 'preventive', 'status' => 'done', 'title' => 'Oil change']);
    EquipmentMaintenance::create(['equipment_id' => $e2->id, 'maintenance_date' => now(), 'type' => 'repair', 'status' => 'planned', 'title' => 'Fix motor']);

    Livewire::test(Maintenance::class)->set('search', 'Forklift')
        ->assertViewHas('records', fn ($r) => $r->count() === 1 && $r->first()->equipment_id === $e1->id);
    Livewire::test(Maintenance::class)->set('typeFilter', 'repair')
        ->assertViewHas('records', fn ($r) => $r->count() === 1 && $r->first()->type === 'repair');
    Livewire::test(Maintenance::class)->set('statusFilter', 'planned')
        ->assertViewHas('records', fn ($r) => $r->count() === 1 && $r->first()->status === 'planned');
});
