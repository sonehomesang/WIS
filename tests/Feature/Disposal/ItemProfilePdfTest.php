<?php

use App\Models\Equipment;
use App\Models\User;
use App\Services\DisposalService;
use Database\Seeders\RolePermissionSeeder;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

test('an item disposal profile renders as a PDF', function () {
    $u = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($u);

    $e = Equipment::create(['asset_code' => 'EQ-P1', 'name' => 'Old pump', 'quantity' => 1,
        'status_counts' => ['active' => 0, 'repair' => 0, 'retired' => 1],
        'condition_status' => 'beyond_repair', 'purchase_date' => '2011-03-18']);

    $record = app(DisposalService::class)->createDraft([
        'title' => 'Q3 disposal',
        'items' => [[
            'source_type' => 'equipment', 'source_id' => $e->id, 'item_name' => 'Old pump',
            'asset_code' => 'EQ-P1', 'qty' => 1, 'reason' => 'ຊຳລຸດ / ເສຍຫາຍ', 'recommendation' => 'ທຳລາຍ',
        ]],
    ], $u);
    $item = $record->items()->first();

    $res = $this->get(route('disposal.item.pdf', [$record, $item]));
    $res->assertOk();
    expect(strtolower($res->headers->get('content-type')))->toContain('pdf');
});

test('the profile 404s for an item that belongs to a different record', function () {
    $u = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($u);

    $r1 = app(DisposalService::class)->createDraft(['title' => 'A', 'items' => [['source_type' => 'new', 'item_name' => 'X', 'qty' => 1]]], $u);
    $r2 = app(DisposalService::class)->createDraft(['title' => 'B', 'items' => [['source_type' => 'new', 'item_name' => 'Y', 'qty' => 1]]], $u);
    $foreignItem = $r2->items()->first();

    $this->get(route('disposal.item.pdf', [$r1, $foreignItem]))->assertNotFound();
});
