<?php

use App\Livewire\Borrow\Index;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('transactionScope resolves per role', function () {
    $req = User::factory()->create(['status' => 'active']);
    $req->syncRoles(['requester']);
    expect($req->transactionScope())->toBe('own');

    $dept = User::factory()->create(['status' => 'active']);
    $dept->syncRoles(['department_admin']);
    expect($dept->transactionScope())->toBe('department');

    $wh = User::factory()->create(['status' => 'active']);
    $wh->syncRoles(['warehouse_staff']);
    expect($wh->transactionScope())->toBe('all');

    $sa = User::factory()->create(['status' => 'active', 'is_super_admin' => true]);
    expect($sa->transactionScope())->toBe('all');
});

test('department_admin can view the (department-scoped) borrow list', function () {
    $u = User::factory()->create(['status' => 'active', 'department_id' => 5]);
    $u->syncRoles(['department_admin']);

    expect($u->can('borrow.view'))->toBeTrue();   // seeder now grants transaction view

    actingAs($u);
    Livewire::test(Index::class)->assertOk();
});

test('department_admin can open a borrow Show for a record in their department, not others (audit dept-link)', function () {
    $da = User::factory()->create(['status' => 'active', 'department_id' => 5]);
    $da->syncRoles(['department_admin']);
    $owner = User::factory()->create(['department_id' => 5]);   // ຄົນ ອື່ນ ໃນ ພະແນກ (da ບໍ່ ແມ່ນ ເຈົ້າ ໃບ)
    $svc = app(App\Services\BorrowService::class);

    $mk = function (int $dept) use ($svc, $owner) {
        $r = $svc->createDraft([
            'borrow_type' => 'new_inventory', 'borrow_date' => now()->toDateString(), 'period_days' => 7,
            'borrower_dept_id' => $dept, 'items' => [['item_name' => 'X', 'qty' => 1]],
        ], $owner);
        $r->forceFill(['status' => 'approved'])->save();

        return $r->refresh();
    };

    actingAs($da);
    Livewire::test(App\Livewire\Borrow\Show::class, ['record' => $mk(5)])->assertOk();         // ພະແນກ ຕົນ
    Livewire::test(App\Livewire\Borrow\Show::class, ['record' => $mk(9)])->assertForbidden();  // ພະແນກ ອື່ນ
});
