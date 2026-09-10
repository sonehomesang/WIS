<?php

use App\Livewire\Settings\Users;
use App\Livewire\Survey\Results;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SurveyPermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

test('survey is a first-class RBAC menu and is grantable per-person', function () {
    expect(Permission::where('name', 'survey.view')->exists())->toBeTrue()
        ->and(array_keys(Users::grantableMenus()))
        ->toContain('survey', 'disposal', 'ansi', 'area_inspection');
});

test('a user with survey.view can open the results dashboard', function () {
    $u = User::factory()->create(['is_super_admin' => false]);
    $u->givePermissionTo('survey.view');

    Livewire::actingAs($u->fresh())->test(Results::class)->assertOk();
});

test('reports.view still opens survey results (backward compatible)', function () {
    $u = User::factory()->create(['is_super_admin' => false]);
    $u->givePermissionTo('reports.view');

    Livewire::actingAs($u->fresh())->test(Results::class)->assertOk();
});

test('a user with neither permission is forbidden from survey results', function () {
    $u = User::factory()->create(['is_super_admin' => false]);

    Livewire::actingAs($u)->test(Results::class)->assertForbidden();
});

test('the surgical seeder mirrors reports access onto survey without touching others', function () {
    // pick a role that has reports.view and one that does not
    $withReports = Role::whereHas('permissions', fn ($q) => $q->where('name', 'reports.view'))->first();
    $without = Role::where('name', 'requester')->first();
    $beforeWithout = $without->permissions->pluck('name')->sort()->values()->all();

    $this->seed(SurveyPermissionSeeder::class);

    expect($withReports->fresh()->hasPermissionTo('survey.view'))->toBeTrue()
        ->and($without->fresh()->permissions->pluck('name')->sort()->values()->all())->toBe($beforeWithout);
});
