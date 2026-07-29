<?php

use App\Livewire\Equipment\Index;
use App\Models\Equipment;
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

test('warehouse staff can record an inspection', function () {
    $e = Equipment::create(['asset_code' => 'EQ-01', 'name' => 'Generator', 'quantity' => 5]);

    actingAs($this->staff);
    Livewire::test(Index::class)
        ->call('newInspection')
        ->call('pickInspectionEquipment', $e->id)
        ->set('insInspector', 'ທ. ສົມສະໜຸກ')
        ->set('insResult', 'pass')
        ->call('saveInspection')
        ->assertHasNoErrors();

    expect($e->inspections()->count())->toBe(1);
    expect($e->inspections()->first()->result)->toBe('pass');
});

test('an inspection can update the equipment status breakdown', function () {
    $e = Equipment::create(['asset_code' => 'EQ-02', 'name' => 'Drills', 'quantity' => 10]);

    actingAs($this->staff);
    Livewire::test(Index::class)
        ->call('newInspection')
        ->call('pickInspectionEquipment', $e->id)
        ->set('insResult', 'follow_up')
        ->set('insUpdateStatus', true)
        ->set('insRepair', 3)
        ->set('insRetired', 1)
        ->call('saveInspection')
        ->assertHasNoErrors();

    expect($e->fresh()->statusBreakdown())->toBe(['active' => 6, 'repair' => 3, 'retired' => 1]);
});

test('multiple inspection evidence photos are stored', function () {
    Storage::fake('public');
    $e = Equipment::create(['asset_code' => 'EQ-03', 'name' => 'Pump', 'quantity' => 1]);

    actingAs($this->staff);
    Livewire::test(Index::class)
        ->call('newInspection')
        ->call('pickInspectionEquipment', $e->id)
        ->set('insPhotos', [
            UploadedFile::fake()->image('check1.jpg', 800, 600),
            UploadedFile::fake()->image('check2.jpg', 800, 600),
        ])
        ->call('saveInspection')
        ->assertHasNoErrors();

    $ins = $e->inspections()->first();
    expect($ins->photos)->toHaveCount(2);            // ເກັບ ຫຼາຍ ໃບ
    expect($ins->photo_path)->toBe($ins->photos[0]); // ຮູບ ທຳອິດ = list
    expect($ins->allPhotos())->toHaveCount(2);
    foreach ($ins->photos as $p) {
        Storage::disk('public')->assertExists($p);
    }
});

test('the inspection record PDF renders via DomPDF (SCU-WID header, no overlap)', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $e = Equipment::create(['asset_code' => 'INSP-PDF', 'name' => 'Forklift', 'category' => 'Vehicles', 'quantity' => 1]);
    $ins = $e->inspections()->create([
        'inspected_at' => '2026-07-28 07:15', 'inspector_name' => 'SA', 'fuel_type' => 'ev',
        'frequency' => 'pre_use', 'result' => 'pass', 'score' => 100, 'created_by' => $admin->id,
        'checklist' => [
            ['label' => 'Forks, Mast & Load Backrest', 'status' => 'pass', 'note' => ''],
            ['label' => 'Brake & Parking Brake', 'status' => 'fail', 'note' => 'ຮົ່ວ'],
        ],
    ]);

    $res = actingAs($admin)->get(route('equipment.inspection.pdf', $ins->id));
    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('application/pdf');
});

test('a manager can acknowledge an inspection and it fills the PDF signature box', function () {
    $mgr = User::factory()->create(['display_name' => 'ຫົວໜ້າ ກວດ']);
    $mgr->syncRoles(['warehouse_staff']);   // has equipment.activate
    $e = Equipment::create(['asset_code' => 'IACK-1', 'name' => 'Forklift', 'quantity' => 1]);
    $ins = $e->inspections()->create([
        'inspected_at' => '2026-07-28 08:00', 'inspector_name' => 'ຊ່າງ', 'result' => 'pass',
    ]);

    actingAs($mgr);
    Livewire::test(Index::class)
        ->call('viewInspection', $ins->id)
        ->assertViewHas('canAcknowledge', true)
        ->call('acknowledgeInspection', $ins->id)
        ->assertHasNoErrors();

    $ins->refresh();
    expect($ins->acknowledged_by)->toBe($mgr->id);
    expect($ins->acknowledged_by_name)->toBe('ຫົວໜ້າ ກວດ');
    expect($ins->acknowledged_at)->not->toBeNull();

    $html = view('equipment.inspection-pdf', ['record' => $ins, 'totalPages' => 1])->render();
    expect($html)->toContain('ຫົວໜ້າ ກວດ');
});

test('a user with equipment view but no activate cannot acknowledge an inspection', function () {
    $u = User::factory()->create();
    $u->givePermissionTo('equipment.view');
    $e = Equipment::create(['asset_code' => 'IACK-2', 'name' => 'Forklift', 'quantity' => 1]);
    $ins = $e->inspections()->create(['inspected_at' => '2026-07-28 08:00', 'inspector_name' => 'ຊ່າງ', 'result' => 'pass']);

    actingAs($u);
    Livewire::test(Index::class)
        ->assertViewHas('canAcknowledge', false)
        ->call('acknowledgeInspection', $ins->id)
        ->assertForbidden();

    expect($ins->fresh()->acknowledged_at)->toBeNull();
});

test('an inspection acknowledgement can be revoked', function () {
    $mgr = User::factory()->create();
    $mgr->syncRoles(['warehouse_staff']);
    $e = Equipment::create(['asset_code' => 'IACK-3', 'name' => 'Forklift', 'quantity' => 1]);
    $ins = $e->inspections()->create([
        'inspected_at' => '2026-07-28 08:00', 'inspector_name' => 'ຊ່າງ', 'result' => 'pass',
        'acknowledged_by' => $mgr->id, 'acknowledged_by_name' => 'X', 'acknowledged_at' => now(),
    ]);

    actingAs($mgr);
    Livewire::test(Index::class)->call('unacknowledgeInspection', $ins->id)->assertHasNoErrors();

    $ins->refresh();
    expect($ins->acknowledged_at)->toBeNull();
    expect($ins->acknowledged_by)->toBeNull();
});
