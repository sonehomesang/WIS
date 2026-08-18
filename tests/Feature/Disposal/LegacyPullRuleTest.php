<?php

use App\Models\DepositItem;
use App\Models\User;
use App\Services\DepositService;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
});

function depositWith(string $type, string $status, string $cond = 'obsolete'): DepositItem
{
    $rec = app(DepositService::class)->createDraft([
        'request_type' => $type, 'deposit_date' => now()->toDateString(),
        'items' => [['item_name' => 'X', 'qty' => 1, 'condition_status' => $cond]],
    ], auth()->user());
    $rec->update(['status' => $status]);

    return $rec->items()->first();
}

test('legacy deposit is pullable in draft (already in warehouse)', function () {
    $it = depositWith('legacy', 'draft');
    expect(DepositItem::whereKey($it->id)->pullableForDisposal()->exists())->toBeTrue();
});

test('regular deposit is NOT pullable in draft (must be accepted/stored)', function () {
    $it = depositWith('walk_in', 'draft');
    expect(DepositItem::whereKey($it->id)->pullableForDisposal()->exists())->toBeFalse();
});

test('regular deposit becomes pullable once stored', function () {
    $it = depositWith('walk_in', 'stored');
    expect(DepositItem::whereKey($it->id)->pullableForDisposal()->exists())->toBeTrue();
});

test('legacy deposit is NOT pullable once claimed/cancelled/disposed', function () {
    foreach (['claimed', 'cancelled', 'disposal', 'disposed'] as $gone) {
        $it = depositWith('legacy', $gone);
        expect(DepositItem::whereKey($it->id)->pullableForDisposal()->exists())
            ->toBeFalse("legacy {$gone} should not pull");
    }
});
