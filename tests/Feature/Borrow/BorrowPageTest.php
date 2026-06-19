<?php

use App\Models\User;
use App\Services\BorrowService;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->create(['is_super_admin' => true, 'display_name' => 'Admin']));
});

test('borrow index renders', function () {
    $this->get(route('borrow'))->assertOk()->assertSee('Borrow Request')->assertSee('Borrowing & Tracking');
});

test('borrow create page renders', function () {
    $this->get(route('borrow.create'))->assertOk()->assertSee('ສາຍອະນຸມັດ');
});

test('borrow show page renders a record with timeline', function () {
    $r = app(BorrowService::class)->createDraft([
        'borrow_type' => 'new_inventory', 'purpose' => 'x', 'borrow_date' => now()->toDateString(),
        'period_days' => 5, 'items' => [['item_name' => 'Drill', 'qty' => 1]],
    ], auth()->user());

    $this->get(route('borrow.show', $r))->assertOk()
        ->assertSee($r->request_number)
        ->assertSee('ສົ່ງຂໍອະນຸມັດ'); // draft action button
});
