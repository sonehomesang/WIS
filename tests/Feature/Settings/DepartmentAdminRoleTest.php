<?php

use App\Livewire\Settings\Users;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DepartmentAdminPresetSeeder;
use Database\Seeders\RolePermissionSeeder;

/** A user carrying the department_admin preset. */
function deptAdmin(): User
{
    $u = User::factory()->create(['is_super_admin' => false]);
    $u->assignRole('department_admin');

    return $u->fresh();
}

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

test('department_admin CAN operate its own transactions', function () {
    $u = deptAdmin();

    expect($u->can('borrow.create'))->toBeTrue()
        ->and($u->can('borrow.edit'))->toBeTrue()
        ->and($u->can('deposit.create'))->toBeTrue()
        ->and($u->can('deposit.edit'))->toBeTrue()
        ->and($u->can('request.create'))->toBeTrue()
        ->and($u->can('request.edit'))->toBeTrue();
});

test('department_admin can read stock + manage its own equipment', function () {
    $u = deptAdmin();

    expect($u->can('inventory.view'))->toBeTrue()      // read stock
        ->and($u->can('catalog.view'))->toBeTrue()
        ->and($u->can('equipment.create'))->toBeTrue()  // manage own-dept equipment
        ->and($u->can('equipment.edit'))->toBeTrue();
});

test('SECURITY — department_admin can NEVER touch the master inventory', function () {
    $u = deptAdmin();

    expect($u->can('inventory.create'))->toBeFalse()   // cannot add a master stock item
        ->and($u->can('inventory.edit'))->toBeFalse()
        ->and($u->can('inventory.delete'))->toBeFalse()
        ->and($u->can('catalog.create'))->toBeFalse()  // nor the supplier catalog
        ->and($u->can('catalog.edit'))->toBeFalse();
});

test('SECURITY — department_admin has no admin menus', function () {
    $u = deptAdmin();

    expect($u->can('users.view'))->toBeFalse()
        ->and($u->can('users.edit'))->toBeFalse()
        ->and($u->can('roles.view'))->toBeFalse()
        ->and($u->can('settings.view'))->toBeFalse()
        ->and($u->can('settings.edit'))->toBeFalse()
        ->and($u->can('audit.view'))->toBeFalse()
        ->and($u->can('reports.view'))->toBeFalse()   // org reports are not for a dept admin
        ->and($u->can('survey.view'))->toBeFalse();
});

test('SECURITY — department_admin cannot approve or delete transactions', function () {
    $u = deptAdmin();

    expect($u->can('borrow.activate'))->toBeFalse()    // no self-approval
        ->and($u->can('borrow.delete'))->toBeFalse()
        ->and($u->can('deposit.delete'))->toBeFalse()
        ->and($u->can('request.activate'))->toBeFalse()
        ->and($u->can('equipment.delete'))->toBeFalse();  // manage yes, delete no
});

test('department_admin is scoped to its own department', function () {
    $u = deptAdmin();

    expect($u->transactionScope())->toBe('department')     // borrow/deposit/request → own dept only
        ->and($u->equipmentScope())->toBe('department')
        ->and($u->is_super_admin)->toBeFalse();
});

test('SECURITY — hitting an admin Livewire page is forbidden (403)', function () {
    $this->actingAs(deptAdmin());

    Livewire\Livewire::test(Users::class)->assertForbidden();
});

test('the preset-only seeder installs department_admin without touching other roles', function () {
    // change another role, then run the surgical seeder, and confirm it is untouched
    $approver = Role::where('name', 'approver')->first();
    $before = $approver->permissions->pluck('name')->sort()->values()->all();

    $this->seed(DepartmentAdminPresetSeeder::class);

    $after = Role::where('name', 'approver')->first()->permissions->pluck('name')->sort()->values()->all();
    expect($after)->toBe($before)                                   // approver untouched
        ->and(Role::where('name', 'department_admin')->exists())->toBeTrue();
});
