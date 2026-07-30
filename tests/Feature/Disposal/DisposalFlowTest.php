<?php

use App\Livewire\Disposal\Create;
use App\Livewire\Disposal\Index;
use App\Models\DisposalRecord;
use App\Models\Equipment;
use App\Models\User;
use App\Services\DisposalService;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->staff = User::factory()->create();
    $this->staff->syncRoles(['warehouse_staff']);
});

test('warehouse staff can create a disposal draft via the form', function () {
    actingAs($this->staff);
    Livewire::test(Create::class)
        ->set('items.0.source_type', 'new')
        ->set('items.0.item_name', 'ໄຂຄວງ ໄຟຟ້າ')
        ->set('items.0.qty', 1)
        ->set('items.0.reason', 'ຊຳລຸດ / ເສຍຫາຍ')
        ->set('items.0.recommendation', 'ທຳລາຍ')
        ->call('save', false)
        ->assertHasNoErrors();

    $r = DisposalRecord::first();
    expect($r)->not->toBeNull();
    expect($r->status)->toBe('draft');
    expect($r->items->first()->reason)->toBe('ຊຳລຸດ / ເສຍຫາຍ');
});

test('picking an equipment item pulls its problem/repair history into the form', function () {
    $e = Equipment::create(['asset_code' => 'EQ-H', 'name' => 'Forklift', 'quantity' => 1]);
    $e->maintenances()->create([
        'maintenance_date' => '2026-07-20', 'type' => 'repair', 'title' => 'ມໍເຕີ ໄໝ້', 'status' => 'done', 'description' => 'ບໍ່ ຄຸ້ມ',
    ]);
    $e->maintenances()->create([
        'maintenance_date' => '2026-06-01', 'type' => 'preventive', 'title' => 'PM', 'status' => 'done',
        'checklist' => [['label' => 'ນ້ຳມັນ ເຄື່ອງ', 'status' => 'ng', 'action' => 'X', 'note' => 'ຮົ່ວ']],
    ]);

    actingAs($this->staff);
    $c = Livewire::test(Create::class)
        ->set('items.0.source_type', 'equipment')
        ->call('pickAsset', 0, 'equipment', $e->id)
        ->assertSet('items.0.item_name', 'Forklift');

    expect($c->get('items')[0]['history'])->toHaveCount(2);   // 1 repair + 1 NG maintenance item
});

test('full sign-off chain: draft → 5 approvals → dispose retires the equipment', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $e = Equipment::create(['asset_code' => 'DP-1', 'name' => 'Drill', 'quantity' => 3, 'status_counts' => ['active' => 3, 'repair' => 0, 'retired' => 0]]);
    $svc = app(DisposalService::class);

    $r = $svc->createDraft(['items' => [[
        'source_type' => 'equipment', 'source_id' => $e->id, 'item_name' => 'Drill', 'qty' => 2, 'reason' => 'ຊຳລຸດ',
    ]]], $admin);

    $svc->transition($r, 'submit', $admin);
    expect($r->refresh()->status)->toBe('committee_review');

    $svc->transition($r, 'sign', $admin, ['committee' => [['name' => 'A', 'title' => 'ຫົວໜ້າ'], ['name' => 'B', 'title' => '']]]);
    expect($r->refresh()->status)->toBe('technical_review');
    expect($r->signoffs()->where('role_key', 'committee')->count())->toBe(2);

    $svc->transition($r, 'sign', $admin);   // technical → manager_review
    $svc->transition($r, 'sign', $admin);   // manager → executive_review
    $svc->transition($r, 'sign', $admin);   // executive → approved
    expect($r->refresh()->status)->toBe('approved');

    $svc->transition($r, 'dispose', $admin, ['update_registers' => true]);
    expect($r->refresh()->status)->toBe('disposed');
    expect($r->registers_updated_at)->not->toBeNull();

    // 2 of 3 units moved to retired
    expect($e->fresh()->statusBreakdown())->toBe(['active' => 1, 'repair' => 0, 'retired' => 2]);
});

test('reject at a review stage loops back to draft with the reason', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $svc = app(DisposalService::class);
    $r = $svc->createDraft(['items' => [['source_type' => 'new', 'item_name' => 'X', 'qty' => 1]]], $admin);
    $svc->transition($r, 'submit', $admin);

    $svc->transition($r, 'reject', $admin, ['reason' => 'ຂໍ້ມູນ ຜິດ']);
    $r->refresh();
    expect($r->status)->toBe('draft');
    expect($r->reject_reason)->toBe('ຂໍ້ມູນ ຜິດ');
    expect($r->signoffs()->where('decision', 'rejected')->exists())->toBeTrue();
});

test('the → Disposal register button preloads the first item from an equipment id', function () {
    $e = Equipment::create(['asset_code' => 'PL-1', 'name' => 'Pump', 'quantity' => 1]);
    actingAs($this->staff);

    Livewire::withQueryParams(['add' => 'equipment:'.$e->id])
        ->test(Create::class)
        ->assertSet('items.0.source_type', 'equipment')
        ->assertSet('items.0.item_name', 'Pump')
        ->assertSet('items.0.asset_code', 'PL-1');
});

test('a non-permitted user cannot open the disposal list', function () {
    $u = User::factory()->create();
    $u->syncRoles(['requester']);
    actingAs($u);
    Livewire::test(Index::class)->assertForbidden();
});

test('the summary lists disposed items with running totals', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $r = app(DisposalService::class)->createDraft([
        'items' => [['source_type' => 'new', 'item_name' => 'ໝວກ ນິລະໄພ', 'qty' => 2, 'estimated_value' => 150000]],
    ], $admin);
    $r->update(['status' => 'disposed']);

    actingAs($admin);
    Livewire::test(App\Livewire\Disposal\Summary::class)
        ->assertViewHas('totalQty', 2)
        ->assertViewHas('totalValue', 150000.0)
        ->assertSee('ໝວກ ນິລະໄພ');
});
