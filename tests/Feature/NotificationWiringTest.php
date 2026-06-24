<?php

use App\Livewire\Borrow\Index as BorrowIndex;
use App\Models\BorrowRecord;
use App\Models\DepositRecord;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\User;
use App\Services\DepositService;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function aDepositFor(User $owner): DepositRecord
{
    return app(DepositService::class)->createDraft([
        'request_type' => 'walk_in', 'item_category' => 'Tools', 'origin_source' => 'Personal',
        'deposit_reason' => 'no space', 'expected_duration' => '1 month', 'deposit_date' => now()->toDateString(),
        'items' => [['item_name' => 'Drill', 'qty' => 1]],
    ], $owner);
}

test('deposit submit notifies warehouse staff, accept notifies owner', function () {
    $staff = User::factory()->create(['is_super_admin' => false]);
    $staff->assignRole('warehouse_staff');
    $owner = User::factory()->create();
    $this->actingAs($staff);

    $svc = app(DepositService::class);
    $r = aDepositFor($owner);

    $svc->transition($r, 'submit', $owner);
    expect(Notification::where('user_id', $staff->id)->where('title', 'like', '%'.$r->request_number.'%')->exists())->toBeTrue();

    $svc->transition($r, 'accept', $staff, ['storage_shelf_label' => 'A-1']);
    expect(Notification::where('user_id', $owner->id)->where('title', 'like', '%ຖูกຮັບ%')->exists())->toBeTrue();
});

test('master notifications flag suppresses creation', function () {
    Setting::put('notifications', ['enabled' => false]);
    $staff = User::factory()->create();
    $staff->assignRole('warehouse_staff');
    $owner = User::factory()->create();
    $this->actingAs($staff);

    app(DepositService::class)->transition(aDepositFor($owner), 'submit', $owner);
    expect(Notification::count())->toBe(0);
});

test('borrow daily check sends one reminder per overdue record, no duplicate', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $borrower = User::factory()->create();
    $this->actingAs($admin);

    BorrowRecord::create([
        'request_number' => 'BR'.now()->year.'-7001', 'borrower_user_id' => $borrower->id,
        'borrower_email' => $borrower->email, 'borrower_name' => 'B', 'borrow_type' => 'new_inventory',
        'borrow_date' => now()->subDays(10)->toDateString(), 'period_days' => 5,
        'planned_return_date' => now()->subDays(2)->toDateString(), 'status' => 'active',
    ]);

    Livewire::test(BorrowIndex::class)->call('runDailyCheck');
    expect(Notification::where('user_id', $borrower->id)->where('type', 'warning')->count())->toBe(1);

    // second run same day → no duplicate
    Livewire::test(BorrowIndex::class)->call('runDailyCheck');
    expect(Notification::where('user_id', $borrower->id)->count())->toBe(1);
});

test('borrow daily check respects the borrow_reminder flag', function () {
    Setting::put('notifications', ['enabled' => true, 'borrow_reminder' => false]);
    $admin = User::factory()->create(['is_super_admin' => true]);
    $borrower = User::factory()->create();
    $this->actingAs($admin);

    BorrowRecord::create([
        'request_number' => 'BR'.now()->year.'-7002', 'borrower_user_id' => $borrower->id,
        'borrower_email' => $borrower->email, 'borrower_name' => 'B', 'borrow_type' => 'new_inventory',
        'borrow_date' => now()->subDays(10)->toDateString(), 'period_days' => 5,
        'planned_return_date' => now()->subDays(1)->toDateString(), 'status' => 'active',
    ]);

    Livewire::test(BorrowIndex::class)->call('runDailyCheck');
    expect(Notification::count())->toBe(0);
});

test('daily check is forbidden for non-staff', function () {
    $u = User::factory()->create(['is_super_admin' => false]);
    $u->assignRole('requester');
    $this->actingAs($u);
    Livewire::test(BorrowIndex::class)->call('runDailyCheck')->assertForbidden();
});
