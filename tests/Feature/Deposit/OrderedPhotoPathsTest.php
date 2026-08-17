<?php
use App\Models\DepositItem;
use App\Models\User;
use App\Services\DepositService;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
});

function depItem(): DepositItem {
    $rec = app(DepositService::class)->createDraft([
        'request_type' => 'legacy', 'deposit_date' => now()->toDateString(),
        'items' => [['item_name' => 'X', 'qty' => 1]],
    ], auth()->user());
    return $rec->items()->first();
}

test('orderedPhotoPaths leads with one photo per slot in overall/id/damage order', function () {
    $di = depItem();
    // insert out of canonical order, with 2 in overall
    $di->photos()->create(['kind' => 'deposit', 'slot' => 'damage', 'path' => 'd.jpg', 'sort_order' => 0]);
    $di->photos()->create(['kind' => 'deposit', 'slot' => 'overall', 'path' => 'o1.jpg', 'sort_order' => 1]);
    $di->photos()->create(['kind' => 'deposit', 'slot' => 'overall', 'path' => 'o2.jpg', 'sort_order' => 2]);
    $di->photos()->create(['kind' => 'deposit', 'slot' => 'id', 'path' => 'i.jpg', 'sort_order' => 3]);

    // leads: overall(o1), id(i), damage(d); then extra overall(o2)
    expect($di->fresh('photos')->orderedPhotoPaths())->toBe(['o1.jpg', 'i.jpg', 'd.jpg', 'o2.jpg']);
});

test('orderedPhotoPaths keeps legacy null-slot photos after typed ones', function () {
    $di = depItem();
    $di->photos()->create(['kind' => 'deposit', 'slot' => null, 'path' => 'legacy.jpg', 'sort_order' => 0]);
    $di->photos()->create(['kind' => 'deposit', 'slot' => 'overall', 'path' => 'o.jpg', 'sort_order' => 1]);

    expect($di->fresh('photos')->orderedPhotoPaths())->toBe(['o.jpg', 'legacy.jpg']);
});
