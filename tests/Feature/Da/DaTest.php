<?php

use App\Livewire\Da\Index;
use App\Livewire\Da\Show;
use App\Models\DiscrepancyAdvice;
use App\Models\User;
use App\Services\DiscrepancyService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function aDaDraft(User $actor): DiscrepancyAdvice
{
    return app(DiscrepancyService::class)->createDraft([
        'date' => now()->toDateString(), 'po_number' => 'PO-1', 'supplier_name' => 'ACME',
        'discrepancy_types' => ['damaged', 'undersupplied'],
        'items' => [['stock_code' => 'S1', 'description' => 'Valve', 'qty_ordered' => 10, 'qty_delivered' => 8, 'qty_received' => 8]],
    ], $actor);
}

test('createDraft makes DA number + history', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $r = aDaDraft($admin);

    expect($r->da_number)->toStartWith('DA'.now()->year.'-');
    expect($r->status)->toBe('draft');
    expect($r->discrepancy_types)->toBe(['damaged', 'undersupplied']);
    expect($r->items)->toHaveCount(1);
    expect($r->history()->where('action', 'create')->exists())->toBeTrue();
});

test('full workflow: submit → purchasingStart → decide → approve(resolved)', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $svc = app(DiscrepancyService::class);
    $r = aDaDraft($admin);

    $svc->transition($r, 'submit', $admin);
    expect($r->refresh()->status)->toBe('submitted');
    $svc->transition($r, 'purchasingStart', $admin);
    expect($r->refresh()->status)->toBe('purchasing_review');
    $svc->transition($r, 'purchasingDecide', $admin, ['decisions' => ['return_supplier'], 'note' => 'send back']);
    $r->refresh();
    expect($r->status)->toBe('pending_approval');
    expect($r->purchasing_decisions)->toBe(['return_supplier']);
    $svc->transition($r, 'approve', $admin, ['resolution' => 'returned', 'next_step' => 'oga']);
    $r->refresh();
    expect($r->status)->toBe('resolved');
    expect($r->next_step)->toBe('oga');
});

test('leader reject loops back to purchasing_review', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $svc = app(DiscrepancyService::class);
    $r = aDaDraft($admin);
    $svc->transition($r, 'submit', $admin);
    $svc->transition($r, 'purchasingStart', $admin);
    $svc->transition($r, 'purchasingDecide', $admin, ['decisions' => ['other']]);

    $svc->transition($r->refresh(), 'reject', $admin, ['reason' => 'redo']);
    $r->refresh();
    expect($r->status)->toBe('purchasing_review');
    expect($r->reject_reason)->toBe('redo');
});

test('reject requires a reason', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $svc = app(DiscrepancyService::class);
    $r = aDaDraft($admin);
    $svc->transition($r, 'submit', $admin);
    $svc->transition($r, 'purchasingStart', $admin);
    $svc->transition($r, 'purchasingDecide', $admin, ['decisions' => ['other']]);

    expect(fn () => $svc->transition($r->refresh(), 'reject', $admin, []))->toThrow(ValidationException::class);
});

test('cannot purchasingStart from draft', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $r = aDaDraft($admin);

    expect(fn () => app(DiscrepancyService::class)->transition($r, 'purchasingStart', $admin))
        ->toThrow(ValidationException::class);
});

test('admin can soft-delete a resolved DA and restore it', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $svc = app(DiscrepancyService::class);
    $r = aDaDraft($admin);
    $svc->transition($r, 'submit', $admin);
    $svc->transition($r, 'purchasingStart', $admin);
    $svc->transition($r, 'purchasingDecide', $admin, ['decisions' => ['end_user_accept']]);
    $svc->transition($r, 'approve', $admin, ['resolution' => 'ok', 'next_step' => 'finished']);
    $r->refresh();

    Livewire::test(Show::class, ['record' => $r])->call('openDelete')->set('deleteReason', 'dup')->call('deleteRecord');
    expect($r->refresh()->trashed())->toBeTrue();

    Livewire::test(Index::class)->set('showDeleted', true)->call('restore', $r->id);
    expect($r->refresh()->trashed())->toBeFalse();
});

test('non-permitted user cannot open DA index', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => false]));
    Livewire::test(Index::class)->assertForbidden();
});
