<?php

use App\Models\Supplier;
use App\Models\User;
use App\Services\BorrowService;
use App\Services\RequestService;
use Database\Seeders\RolePermissionSeeder;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function makeBorrow(User $owner)
{
    return (new BorrowService)->createDraft([
        'borrow_type' => 'new_inventory',
        'purpose' => 'fix pump',
        'borrow_date' => now()->toDateString(),
        'period_days' => 7,
        'items' => [['item_name' => 'Drill', 'qty' => 1]],
    ], $owner);
}

test('borrow PDF: owner can download but an unrelated requester cannot (IDOR closed)', function () {
    $owner = User::factory()->create(['status' => 'active']);
    $owner->syncRoles(['requester']);
    $rec = makeBorrow($owner);

    actingAs($owner)->get(route('borrow.pdf', $rec))->assertOk();

    $intruder = User::factory()->create(['status' => 'active']);
    $intruder->syncRoles(['requester']);
    actingAs($intruder)->get(route('borrow.pdf', $rec))->assertForbidden();
});

test('borrow PDF: warehouse staff can download any record', function () {
    $owner = User::factory()->create();
    $owner->syncRoles(['requester']);
    $rec = makeBorrow($owner);

    $staff = User::factory()->create();
    $staff->syncRoles(['warehouse_staff']);
    actingAs($staff)->get(route('borrow.pdf', $rec))->assertOk();
});

test('request PDF: a supplier cannot download a request assigned to a different supplier', function () {
    $requester = User::factory()->create();
    $requester->syncRoles(['requester']);

    $supA = Supplier::create(['name' => 'Supplier A', 'slug' => 'supplier-a', 'is_active' => true]);
    $supB = Supplier::create(['name' => 'Supplier B', 'slug' => 'supplier-b', 'is_active' => true]);

    $rec = (new RequestService)->createDraft([
        'purpose' => 'x',
        'assigned_supplier_id' => $supA->id,
        'items' => [['description' => 'bolt', 'quantity' => 2, 'unit_price' => 1]],
    ], $requester);

    $supplierB = User::factory()->create(['supplier_id' => $supB->id]);
    $supplierB->syncRoles(['supplier']);
    actingAs($supplierB)->get(route('request.pdf', $rec))->assertForbidden();
});
