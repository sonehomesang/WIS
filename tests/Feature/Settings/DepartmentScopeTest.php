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
