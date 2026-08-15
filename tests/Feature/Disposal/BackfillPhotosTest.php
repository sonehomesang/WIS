<?php

use App\Models\User;
use App\Services\DisposalService;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
});

function depositItemWithPhotos(array $paths): App\Models\DepositItem
{
    $rec = app(App\Services\DepositService::class)->createDraft([
        'request_type' => 'walk_in', 'item_category' => 'Tools', 'origin_source' => 'X',
        'deposit_reason' => 'r', 'deposit_date' => now()->toDateString(),
        'items' => [['item_name' => 'Broken drill', 'qty' => 1]],
    ], auth()->user());
    $di = $rec->items()->first();
    foreach ($paths as $i => $p) {
        $di->photos()->create(['kind' => 'deposit', 'path' => $p, 'sort_order' => $i]);
    }

    return $di;
}

function disposalWithSource(App\Models\DepositItem $di, ?array $photos): App\Models\DisposalItem
{
    $rec = app(DisposalService::class)->createDraft([
        'items' => [[
            'source_type' => 'deposit', 'source_id' => $di->id,
            'item_name' => $di->item_name, 'qty' => 1, 'photos' => $photos,
        ]],
    ], auth()->user());

    return $rec->items()->first();
}

test('backfill pulls source photos onto a disposal item that has none', function () {
    $di = depositItemWithPhotos(['deposit/9/9/x.jpg', 'deposit/9/9/y.jpg']);
    $item = disposalWithSource($di, null);
    expect($item->photos)->toBeNull();

    $this->artisan('disposal:backfill-photos')->assertSuccessful();

    expect($item->refresh()->photos)->toBe(['deposit/9/9/x.jpg', 'deposit/9/9/y.jpg']);
});

test('backfill skips an item that already has photos (no --force)', function () {
    $di = depositItemWithPhotos(['deposit/9/9/x.jpg']);
    $item = disposalWithSource($di, ['manual/keep.jpg']);

    $this->artisan('disposal:backfill-photos')->assertSuccessful();

    // untouched — its own photo stays
    expect($item->refresh()->photos)->toBe(['manual/keep.jpg']);
});

test('--force re-pulls source photos over existing ones', function () {
    $di = depositItemWithPhotos(['deposit/9/9/x.jpg']);
    $item = disposalWithSource($di, ['manual/keep.jpg']);

    $this->artisan('disposal:backfill-photos --force')->assertSuccessful();

    expect($item->refresh()->photos)->toBe(['deposit/9/9/x.jpg']);
});
