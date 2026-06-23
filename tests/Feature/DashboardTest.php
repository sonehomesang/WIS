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

test('action queue surfaces overdue borrow for staff', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);

    BorrowRecord::create([
        'request_number' => 'BR'.now()->year.'-9100', 'borrower_user_id' => $admin->id,
        'borrower_email' => $admin->email, 'borrower_name' => 'A', 'borrow_type' => 'new_inventory',
        'borrow_date' => now()->subDays(10)->toDateString(), 'period_days' => 5,
        'planned_return_date' => now()->subDays(2)->toDateString(), 'status' => 'active',
    ]);

    Livewire::test(Dashboard::class)
        ->assertOk()
        ->assertSee('ສິ່ງທີ່ຕ້ອງເຮັດ')
        ->assertSee('ການຢືມ ເກີນກຳນົດ')
        ->assertSee('ກິດຈະກຳລ່າສຸດ');
});

test('widget toggle persists to the user dashboard_prefs', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(Dashboard::class)
        ->assertSet('prefs.kpi', true)
        ->call('toggle', 'kpi')
        ->assertSet('prefs.kpi', false);

    expect($admin->refresh()->dashboard_prefs['kpi'])->toBeFalse();
});

test('charts data only computed for staff', function () {
    $staff = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($staff);
    Livewire::test(Dashboard::class)->assertViewHas('showCharts', true)->assertViewHas('chart');

    $req = User::factory()->create(['is_super_admin' => false]);
    $req->assignRole('requester');
    $this->actingAs($req);
    Livewire::test(Dashboard::class)->assertViewHas('showCharts', false)->assertViewHas('chart', null);
});
