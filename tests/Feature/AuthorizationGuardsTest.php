<?php

use App\Livewire\Borrow\Show as BorrowShow;
use App\Livewire\Deposit\Show as DepositShow;
use App\Models\BorrowRecord;
use App\Models\DepositRecord;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function aBorrow(User $owner): BorrowRecord
{
    return BorrowRecord::create([
        'request_number' => 'BR'.now()->year.'-AG01', 'borrower_user_id' => $owner->id,
        'borrower_email' => $owner->email, 'borrower_name' => 'O', 'borrow_type' => 'new_inventory',
        'borrow_date' => now()->toDateString(), 'period_days' => 5,
        'planned_return_date' => now()->addDays(5)->toDateString(), 'status' => 'acknowledged',
    ]);
}

test('a requester cannot open a borrow record that is not theirs (scope leak closed)', function () {
    $owner = User::factory()->create();
    $r = aBorrow($owner);

    $intruder = User::factory()->create(['is_super_admin' => false]);
    $intruder->assignRole('requester');   // has borrow.view globally
    $this->actingAs($intruder);

    Livewire::test(BorrowShow::class, ['record' => $r])->assertForbidden();
});

test('the borrower cannot approve their own borrow (warehouse/approver step)', function () {
    $owner = User::factory()->create(['is_super_admin' => false]);
    $owner->assignRole('requester');
    $r = aBorrow($owner);
    $this->actingAs($owner);

    // owner may open (party) but approve must be blocked
    Livewire::test(BorrowShow::class, ['record' => $r])
        ->call('approve')
        ->assertForbidden();

    expect($r->refresh()->status)->toBe('acknowledged');   // unchanged
});

test('staff can approve a borrow', function () {
    $staff = User::factory()->create(['is_super_admin' => false]);
    $staff->assignRole('warehouse_staff');
    $owner = User::factory()->create();
    $r = aBorrow($owner);
    $this->actingAs($staff);

    Livewire::test(BorrowShow::class, ['record' => $r])->call('approve');
    expect($r->refresh()->status)->toBe('approved');
});

test('a non-owner non-staff cannot open a deposit', function () {
    $owner = User::factory()->create();
    $d = DepositRecord::create([
        'request_number' => 'DP'.now()->year.'-AG01', 'owner_user_id' => $owner->id,
        'owner_email' => $owner->email, 'owner_name' => 'O', 'request_type' => 'walk_in',
        'deposit_date' => now()->toDateString(), 'status' => 'submitted',
    ]);

    $intruder = User::factory()->create(['is_super_admin' => false]);
    $intruder->assignRole('requester');
    $this->actingAs($intruder);

    Livewire::test(DepositShow::class, ['record' => $d])->assertForbidden();
});

test('a requester cannot accept a deposit (warehouse step) even their own', function () {
    $owner = User::factory()->create(['is_super_admin' => false]);
    $owner->assignRole('requester');
    $d = DepositRecord::create([
        'request_number' => 'DP'.now()->year.'-AG02', 'owner_user_id' => $owner->id,
        'owner_email' => $owner->email, 'owner_name' => 'O', 'request_type' => 'walk_in',
        'deposit_date' => now()->toDateString(), 'status' => 'submitted',
    ]);
    $this->actingAs($owner);

    Livewire::test(DepositShow::class, ['record' => $d])
        ->call('accept')
        ->assertForbidden();

    expect($d->refresh()->status)->toBe('submitted');
});
