<?php

use App\Livewire\Disposal\Create;
use App\Models\Equipment;
use App\Models\InventoryItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    // disposable
    Equipment::create(['asset_code' => 'EQ-D1', 'name' => 'Broken pump', 'quantity' => 1,
        'status_counts' => ['active' => 0, 'repair' => 0, 'retired' => 1], 'condition_status' => 'beyond_repair']);
    InventoryItem::create(['slug' => 'IN-D1', 'name' => 'Rusted valve', 'quantity' => 1,
        'status' => 'available', 'is_active' => true, 'condition_status' => 'obsolete']);
    // NOT disposable
    Equipment::create(['asset_code' => 'EQ-OK', 'name' => 'Good pump', 'quantity' => 1,
        'status_counts' => ['active' => 1, 'repair' => 0, 'retired' => 0], 'condition_status' => 'in_service']);
    InventoryItem::create(['slug' => 'IN-OK', 'name' => 'Good valve', 'quantity' => 1,
        'status' => 'available', 'is_active' => true, 'condition_status' => 'under_repair']);
});

test('auto-pull grabs only items in a disposable condition-status', function () {
    $c = Livewire::test(Create::class)->call('autoPull')->assertHasNoErrors();

    $items = $c->get('items');
    expect($items)->toHaveCount(2);

    $keys = collect($items)->map(fn ($it) => $it['source_type'].':'.$it['source_id'])->sort()->values()->all();
    expect($keys)->toContain('equipment:'.Equipment::where('asset_code', 'EQ-D1')->value('id'));
    expect($keys)->toContain('inventory:'.InventoryItem::where('slug', 'IN-D1')->value('id'));
    // the in_service / under_repair ones are excluded
    expect(collect($items)->pluck('item_name'))->not->toContain('Good pump');
});

test('auto-pull is idempotent — pulling twice does not duplicate rows', function () {
    $c = Livewire::test(Create::class)->call('autoPull')->call('autoPull');
    expect($c->get('items'))->toHaveCount(2);
});

test('with no disposable statuses selected it errors and pulls nothing', function () {
    $c = Livewire::test(Create::class)
        ->set('pullStatuses', ['beyond_repair' => false, 'end_of_life' => false, 'obsolete' => false, 'deteriorated' => false, 'decommissioned' => false])
        ->call('autoPull')
        ->assertHasErrors('pull');

    // still just the initial blank row
    expect($c->get('items'))->toHaveCount(1);
});

test('auto-pull also grabs a disposable deposit item by status', function () {
    $rec = app(App\Services\DepositService::class)->createDraft([
        'request_type' => 'walk_in', 'item_category' => 'Tools', 'origin_source' => 'X',
        'deposit_reason' => 'r', 'deposit_date' => now()->toDateString(),
        'items' => [['item_name' => 'Broken drill', 'qty' => 1, 'condition_status' => 'beyond_repair']],
    ], auth()->user());
    $depItem = $rec->items()->first();
    expect($depItem->condition_status)->toBe('beyond_repair');

    $c = Livewire::test(Create::class)
        ->set('pullSources', ['inventory' => false, 'equipment' => false, 'deposit' => true])
        ->call('autoPull')->assertHasNoErrors();

    $items = $c->get('items');
    expect($items)->toHaveCount(1);
    expect($items[0]['source_type'])->toBe('deposit');
    expect((int) $items[0]['source_id'])->toBe($depItem->id);
});
