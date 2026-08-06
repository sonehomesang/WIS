<?php

use App\Models\BorrowRecord;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function writeBorrowJson(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'brw').'.json';
    file_put_contents($path, json_encode($rows));

    return $path;
}

test('--refresh updates existing status/return + items and adds new records', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    // ① initial import — BR-0001 active, not yet returned
    $p1 = writeBorrowJson([[
        'request_number' => 'BR2026-0001', 'borrower_email' => 'a@x.com', 'borrower_name' => 'A',
        'borrow_type' => 'new_inventory', 'borrow_date' => '2026-05-01', 'period_days' => 7,
        'planned_return_date' => '2026-05-08', 'status' => 'active',
        'items' => [['item_name' => 'Drill', 'qty' => 2, 'return_qty' => null]], 'history' => [],
    ]]);
    $this->artisan('borrow:import-json', ['path' => $p1, '--by' => $admin->id])->assertOk();

    $r = BorrowRecord::where('request_number', 'BR2026-0001')->first();
    expect($r->status)->toBe('active')
        ->and($r->items->first()->return_qty)->toBeNull();

    // ② refresh — BR-0001 now returned (qty 2 back, condition) + a NEW BR-0002
    $p2 = writeBorrowJson([
        [
            'request_number' => 'BR2026-0001', 'borrower_email' => 'a@x.com', 'borrower_name' => 'A',
            'borrow_type' => 'new_inventory', 'borrow_date' => '2026-05-01', 'period_days' => 7,
            'planned_return_date' => '2026-05-08', 'actual_return_date' => '2026-05-06',
            'returned_at' => '2026-05-06T00:00:00Z', 'status' => 'returned',
            'items' => [['item_name' => 'Drill', 'qty' => 2, 'return_qty' => 2, 'condition_on_return' => 'Good']],
            'history' => [],
        ],
        [
            'request_number' => 'BR2026-0002', 'borrower_email' => 'b@x.com', 'borrower_name' => 'B',
            'borrow_type' => 'new_inventory', 'borrow_date' => '2026-06-01', 'period_days' => 7,
            'planned_return_date' => '2026-06-08', 'status' => 'active',
            'items' => [['item_name' => 'Hammer', 'qty' => 1]], 'history' => [],
        ],
    ]);
    $this->artisan('borrow:import-json', ['path' => $p2, '--by' => $admin->id, '--refresh' => true])->assertOk();

    $r->refresh()->load('items');
    expect($r->status)->toBe('returned')
        ->and($r->actual_return_date?->toDateString())->toBe('2026-05-06')
        ->and($r->items->first()->return_qty)->toBe(2)
        ->and($r->items->first()->condition_on_return)->toBe('Good');
    expect(BorrowRecord::where('request_number', 'BR2026-0002')->exists())->toBeTrue()
        ->and(BorrowRecord::count())->toBe(2);
});

test('without --refresh, an existing record is skipped (status not changed)', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $base = [
        'request_number' => 'BR2026-0001', 'borrower_email' => 'a@x.com',
        'borrow_type' => 'new_inventory', 'borrow_date' => '2026-05-01',
        'items' => [['item_name' => 'Drill', 'qty' => 1]], 'history' => [],
    ];
    $this->artisan('borrow:import-json', ['path' => writeBorrowJson([$base + ['status' => 'active']]), '--by' => $admin->id])->assertOk();
    // re-import same number as returned WITHOUT --refresh → must stay active
    $this->artisan('borrow:import-json', ['path' => writeBorrowJson([$base + ['status' => 'returned']]), '--by' => $admin->id])->assertOk();

    expect(BorrowRecord::where('request_number', 'BR2026-0001')->first()->status)->toBe('active');
});
