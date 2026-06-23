<?php

use App\Livewire\Dashboard;
use App\Models\BorrowRecord;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('dashboard renders role-scoped summary cards for admin', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);

    BorrowRecord::create([
        'request_number' => 'BR'.now()->year.'-9001', 'borrower_user_id' => $admin->id,
        'borrower_email' => $admin->email, 'borrower_name' => 'A', 'borrow_type' => 'new_inventory',
        'borrow_date' => now()->toDateString(), 'period_days' => 5, 'planned_return_date' => now()->addDays(5)->toDateString(),
        'status' => 'active',
    ]);

    Livewire::test(Dashboard::class)
        ->assertOk()
        ->assertSee('ການຢືມ (Borrow)')
        ->assertSee('Shops Material');
});

test('dashboard loads for a plain requester (own-scoped, no errors)', function () {
    $u = User::factory()->create(['is_super_admin' => false]);
    $u->assignRole('requester');
    $this->actingAs($u);

    Livewire::test(Dashboard::class)->assertOk();
});
