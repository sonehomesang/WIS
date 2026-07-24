<?php

use App\Livewire\Equipment\Index;
use App\Livewire\Equipment\InspectionTemplates;
use App\Models\Equipment;
use App\Models\InspectionTemplate;
use App\Models\User;
use Database\Seeders\InspectionTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('the seeder installs one merged forklift template with type + frequency tags', function () {
    $this->seed(InspectionTemplateSeeder::class);

    $forklifts = InspectionTemplate::where('category', 'Forklift')->where('is_active', true)->get();
    expect($forklifts)->toHaveCount(1);                       // 2 legacy → 1 merged

    $t = $forklifts->first();
    expect(count($t->normalizedItems()))->toBe(36);
    expect($t->hasFuelTypes())->toBeTrue();                  // EV/engine split present
    expect($t->hasFrequencies())->toBeTrue();                // round filter present
});

test('admin can create an inspection template (blank checklist items dropped)', function () {
    actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(InspectionTemplates::class)
        ->call('newTemplate')
        ->set('tName', 'Power tools')
        ->set('tCategory', 'Power tool')
        ->set('tItems', [
            ['label' => 'Casing ok', 'applies' => 'both'],
            ['label' => 'Cable ok', 'applies' => 'ev'],
            ['label' => '', 'applies' => 'both'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $t = InspectionTemplate::first();
    expect($t->name)->toBe('Power tools');
    // ຂໍ້ ວ່າງ ຖືກ ຕັດ ອອກ · ເກັບ ເປັນ {label, applies, freqs}
    expect($t->normalizedItems())->toBe([
        ['label' => 'Casing ok', 'applies' => 'both', 'freqs' => []],
        ['label' => 'Cable ok', 'applies' => 'ev', 'freqs' => []],
    ]);
});

test('a requester cannot access the template manager', function () {
    $u = User::factory()->create();
    $u->syncRoles(['requester']);
    actingAs($u);

    Livewire::test(InspectionTemplates::class)->assertForbidden();
});

test('selecting a template loads its checklist and it is saved on the inspection', function () {
    $u = User::factory()->create();
    $u->syncRoles(['warehouse_staff']);
    $e = Equipment::create(['asset_code' => 'EQ-1', 'name' => 'Drill', 'category' => 'Power tool', 'quantity' => 1]);
    $t = InspectionTemplate::create(['name' => 'PT', 'category' => 'Power tool', 'items' => ['A', 'B'], 'is_active' => true]);

    actingAs($u);
    Livewire::test(Index::class)
        ->call('newInspection')
        ->call('pickInspectionEquipment', $e->id)
        ->set('insTemplateId', $t->id)
        ->assertCount('insChecklist', 2)
        ->set('insChecklist.0.status', 'fail')
        ->set('insChecklist.0.note', 'cracked casing')
        ->call('saveInspection')
        ->assertHasNoErrors();

    $ins = $e->inspections()->first();
    expect($ins->template_id)->toBe($t->id);
    expect($ins->checklist[0]['status'])->toBe('fail');
    expect($ins->checklist[0]['note'])->toBe('cracked casing');

    // ຄະແນນ ຄິດ ອັດຕະໂນມັດ: 1 ຜ່ານ / 2 ຂໍ້ = 50% → ບໍ່ຜ່ານ (<70%)
    expect($ins->score)->toBe(50);
    expect($ins->result)->toBe('fail');
    // ຜູ້ ກວດ ບັງຄັບ ຈາກ ຜູ້ ລ໋ອກອິນ · ເວລາ ສະແຕັມ ຕອນ submit
    expect($ins->inspector_name)->toBe($u->display_name);
    expect($ins->inspected_at->isToday())->toBeTrue();
});

test('a fuel-typed template shows only the items for the chosen type', function () {
    $u = User::factory()->create();
    $u->syncRoles(['warehouse_staff']);
    $e = Equipment::create(['asset_code' => 'FL-1', 'name' => 'Forklift', 'quantity' => 1]);
    $t = InspectionTemplate::create([
        'name' => 'FLT',
        'category' => 'Forklift',
        'items' => [
            ['label' => 'Common A', 'applies' => 'both'],
            ['label' => 'Battery', 'applies' => 'ev'],
            ['label' => 'Engine oil', 'applies' => 'engine'],
        ],
        'is_active' => true,
    ]);

    actingAs($u);
    $c = Livewire::test(Index::class)
        ->call('newInspection')
        ->call('pickInspectionEquipment', $e->id)
        ->set('insTemplateId', $t->id)
        ->assertCount('insChecklist', 0)        // ຍັງ ບໍ່ ເລືອກ ປະເພດ → ວ່າງ
        ->set('insFuelType', 'engine')
        ->assertCount('insChecklist', 2);       // Common + Engine

    expect(collect($c->get('insChecklist'))->pluck('label')->all())->toBe(['Common A', 'Engine oil']);

    $c->set('insFuelType', 'ev')
        ->assertCount('insChecklist', 2);        // Common + Battery
    expect(collect($c->get('insChecklist'))->pluck('label')->all())->toBe(['Common A', 'Battery']);

    $c->call('saveInspection')->assertHasNoErrors();
    expect($e->inspections()->first()->fuel_type)->toBe('ev');
});

test('the OK/NG toggle sets and clears a checklist status', function () {
    $u = User::factory()->create();
    $u->syncRoles(['warehouse_staff']);
    $e = Equipment::create(['asset_code' => 'FL-3', 'name' => 'Forklift', 'quantity' => 1]);
    $t = InspectionTemplate::create(['name' => 'FLT3', 'items' => [['label' => 'A', 'applies' => 'both']], 'is_active' => true]);

    actingAs($u);
    Livewire::test(Index::class)
        ->call('newInspection')
        ->call('pickInspectionEquipment', $e->id)
        ->set('insTemplateId', $t->id)
        ->assertSet('insChecklist.0.status', 'pass')       // default OK
        ->call('toggleChecklist', 0, 'fail')
        ->assertSet('insChecklist.0.status', 'fail')        // NG
        ->call('toggleChecklist', 0, 'fail')
        ->assertSet('insChecklist.0.status', 'na')          // ກົດ ຊ້ຳ → N/A
        ->call('toggleChecklist', 0, 'pass')
        ->assertSet('insChecklist.0.status', 'pass');
});

test('a frequency-typed template filters the checklist by the chosen frequency and auto-sets next due', function () {
    $u = User::factory()->create();
    $u->syncRoles(['warehouse_staff']);
    $e = Equipment::create(['asset_code' => 'FQ-1', 'name' => 'Forklift', 'quantity' => 1]);
    $t = InspectionTemplate::create([
        'name' => 'FQ',
        'items' => [
            ['label' => 'Daily A', 'applies' => 'both', 'freqs' => ['pre_use']],
            ['label' => 'Monthly B', 'applies' => 'both', 'freqs' => ['monthly']],
            ['label' => 'Always C', 'applies' => 'both', 'freqs' => []],   // ບໍ່ ຕິດ ຮອບ = ຂຶ້ນ ທຸກ ຮອບ
        ],
        'is_active' => true,
    ]);

    actingAs($u);
    $c = Livewire::test(Index::class)
        ->call('newInspection')
        ->call('pickInspectionEquipment', $e->id)
        ->set('insTemplateId', $t->id)
        ->assertCount('insChecklist', 0)          // ຍັງ ບໍ່ ເລືອກ ຮອບ → ວ່າງ
        ->set('insFrequency', 'pre_use')
        ->assertCount('insChecklist', 2);         // Daily A + Always C
    expect(collect($c->get('insChecklist'))->pluck('label')->all())->toBe(['Daily A', 'Always C']);

    $c->set('insFrequency', 'monthly')->assertCount('insChecklist', 2);   // Monthly B + Always C
    expect($c->get('insNextDue'))->toBe(now()->addMonth()->toDateString());  // ຄິດ +1 ເດືອນ ໃຫ້ ເອງ

    $c->call('saveInspection')->assertHasNoErrors();
    $ins = $e->inspections()->first();
    expect($ins->frequency)->toBe('monthly');
    expect($ins->next_due_date->toDateString())->toBe(now()->addMonth()->toDateString());
});

test('a frequency-typed template requires a frequency before saving', function () {
    $u = User::factory()->create();
    $u->syncRoles(['warehouse_staff']);
    $e = Equipment::create(['asset_code' => 'FQ-2', 'name' => 'Forklift', 'quantity' => 1]);
    $t = InspectionTemplate::create([
        'name' => 'FQ2',
        'items' => [['label' => 'M', 'applies' => 'both', 'freqs' => ['monthly']]],
        'is_active' => true,
    ]);

    actingAs($u);
    Livewire::test(Index::class)
        ->call('newInspection')
        ->call('pickInspectionEquipment', $e->id)
        ->set('insTemplateId', $t->id)
        ->call('saveInspection')
        ->assertHasErrors('insFrequency');
});

test('a fuel-typed template requires a fuel type before saving', function () {
    $u = User::factory()->create();
    $u->syncRoles(['warehouse_staff']);
    $e = Equipment::create(['asset_code' => 'FL-2', 'name' => 'Forklift', 'quantity' => 1]);
    $t = InspectionTemplate::create([
        'name' => 'FLT2',
        'items' => [['label' => 'Battery', 'applies' => 'ev']],
        'is_active' => true,
    ]);

    actingAs($u);
    Livewire::test(Index::class)
        ->call('newInspection')
        ->call('pickInspectionEquipment', $e->id)
        ->set('insTemplateId', $t->id)
        ->call('saveInspection')
        ->assertHasErrors('insFuelType');
});

test('checklist score derives follow_up between 70 and 99 percent', function () {
    $u = User::factory()->create();
    $u->syncRoles(['warehouse_staff']);
    $e = Equipment::create(['asset_code' => 'EQ-2', 'name' => 'Saw', 'category' => 'Power tool', 'quantity' => 1]);
    $t = InspectionTemplate::create(['name' => 'PT4', 'category' => 'Power tool', 'items' => ['A', 'B', 'C', 'D'], 'is_active' => true]);

    actingAs($u);
    Livewire::test(Index::class)
        ->call('newInspection')
        ->call('pickInspectionEquipment', $e->id)
        ->set('insTemplateId', $t->id)
        ->set('insChecklist.3.status', 'fail')   // 3 ຜ່ານ / 4 = 75%
        ->call('saveInspection')
        ->assertHasNoErrors();

    $ins = $e->inspections()->first();
    expect($ins->score)->toBe(75);
    expect($ins->result)->toBe('follow_up');
});

test('the eye icon opens a per-equipment inspection history and can start a new one', function () {
    $u = User::factory()->create();
    $u->syncRoles(['warehouse_staff']);
    $e = Equipment::create(['asset_code' => 'EQ-H', 'name' => 'Pump', 'quantity' => 1]);
    $e->inspections()->create(['inspected_at' => now(), 'inspector_name' => 'X', 'result' => 'pass', 'score' => 100]);

    actingAs($u);
    Livewire::test(Index::class)
        ->call('viewInspectionHistory', $e->id)
        ->assertSet('historyEquipmentId', $e->id)
        ->assertSee('Pump')
        // ＋ ກວດ ໃໝ່ → ເປີດ ຟອມ ກວດ ພ້ອມ ໃສ່ ເຄື່ອງ ນີ້ ໃຫ້ ອັດຕະໂນມັດ
        ->call('inspectEquipment', $e->id)
        ->assertSet('historyEquipmentId', null)
        ->assertSet('showInspectionModal', true)
        ->assertSet('insEquipmentId', $e->id);
});

test('an existing inspection can be edited', function () {
    $u = User::factory()->create();
    $u->syncRoles(['warehouse_staff']);
    $e = Equipment::create(['asset_code' => 'EQ-3', 'name' => 'Grinder', 'quantity' => 1]);

    actingAs($u);
    Livewire::test(Index::class)
        ->call('newInspection')
        ->call('pickInspectionEquipment', $e->id)
        ->set('insResult', 'pass')
        ->set('insNotes', 'first')
        ->call('saveInspection')
        ->assertHasNoErrors();

    $ins = $e->inspections()->first();

    Livewire::test(Index::class)
        ->call('editInspection', $ins->id)
        ->assertSet('insNotes', 'first')
        ->set('insResult', 'fail')
        ->set('insNotes', 'corrected')
        ->call('saveInspection')
        ->assertHasNoErrors();

    expect($e->inspections()->count())->toBe(1);   // ແກ້ໄຂ ບໍ່ ສ້າງ ໃໝ່
    expect($ins->fresh()->result)->toBe('fail');
    expect($ins->fresh()->notes)->toBe('corrected');
});

test('an inspection template can be previewed read-only (view button)', function () {
    $staff = User::factory()->create();
    $staff->syncRoles(['warehouse_staff']);
    actingAs($staff);
    $t = InspectionTemplate::create([
        'name' => 'Preview me', 'category' => 'Forklift', 'is_active' => true,
        'items' => [['label' => 'Check brakes', 'applies' => 'both', 'freqs' => ['pre_use']]],
    ]);

    Livewire::test(InspectionTemplates::class)
        ->call('viewTemplate', $t->id)
        ->assertSet('viewingId', $t->id)
        ->assertSee('Preview me')
        ->assertSee('Check brakes');
});

test('inspection templates list is narrowed by search + category filter', function () {
    actingAs(User::factory()->create(['is_super_admin' => true]));
    InspectionTemplate::create(['name' => 'Forklift Check', 'category' => 'Forklift', 'items' => [], 'is_active' => true]);
    InspectionTemplate::create(['name' => 'Sling Check', 'category' => 'Sling', 'items' => [], 'is_active' => true]);

    Livewire::test(InspectionTemplates::class)->set('search', 'Sling')
        ->assertViewHas('templates', fn ($t) => $t->count() === 1 && $t->first()->name === 'Sling Check');
    Livewire::test(InspectionTemplates::class)->set('categoryFilter', 'Forklift')
        ->assertViewHas('templates', fn ($t) => $t->count() === 1 && $t->first()->category === 'Forklift');
});
