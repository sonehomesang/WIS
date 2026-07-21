<?php

use App\Livewire\Dashboard;
use App\Models\BorrowRecord;
use App\Models\MaterialRequest;
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
        ->assertSee('ການຢືມ active')
        ->assertSee('ການແຈ້ງເຕືອນ')   // notifications KPI always present
        ->assertSee('DA & OGA');        // bottom panel
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

test('action queue surfaces completed requests with SAP still open for staff', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);

    // completed but SAP not yet closed → should be tracked
    MaterialRequest::create([
        'request_number' => 'MR'.now()->year.'-9200', 'requester_user_id' => $admin->id,
        'requester_email' => $admin->email, 'requester_name' => 'A', 'currency' => 'THB',
        'status' => 'completed', 'sap_status' => 'pr_raised',
    ]);
    // completed + SAP closed → should NOT be tracked
    MaterialRequest::create([
        'request_number' => 'MR'.now()->year.'-9201', 'requester_user_id' => $admin->id,
        'requester_email' => $admin->email, 'requester_name' => 'B', 'currency' => 'THB',
        'status' => 'completed', 'sap_status' => 'closed',
    ]);

    Livewire::test(Dashboard::class)
        ->assertOk()
        ->assertSee('SAP ຍັງບໍ່ closed')
        ->assertViewHas('actionRows', function ($rows) {
            $row = collect($rows)->firstWhere('label', 'ໃບເບີກ completed · SAP ຍັງບໍ່ closed');

            return $row && $row['count'] === 1;   // pr_raised counted, closed excluded
        });
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
