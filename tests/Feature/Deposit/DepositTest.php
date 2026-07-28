<?php

use App\Livewire\Deposit\Index;
use App\Livewire\Deposit\Show;
use App\Models\DepositRecord;
use App\Models\User;
use App\Services\DepositService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function aDepositDraft(User $actor): DepositRecord
{
    return app(DepositService::class)->createDraft([
        'request_type' => 'walk_in', 'item_category' => 'Tools', 'origin_source' => 'Personal',
        'deposit_reason' => 'no space', 'expected_duration' => '1 month', 'deposit_date' => now()->toDateString(),
        'items' => [['item_name' => 'Drill set', 'qty' => 1]],
    ], $actor);
}

test('createDraft generates DP{year} number and history', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $r = aDepositDraft($admin);

    expect($r->request_number)->toStartWith('DP'.now()->year.'-');
    expect($r->status)->toBe('draft');
    expect($r->items)->toHaveCount(1);
    expect($r->history()->where('action', 'create')->exists())->toBeTrue();
});

test('full happy path: submit → accept → stored → claimed', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $svc = app(DepositService::class);
    $r = aDepositDraft($admin);

    $svc->transition($r, 'submit', $admin);
    expect($r->refresh()->status)->toBe('submitted');

    $svc->transition($r, 'accept', $admin, ['storage_shelf_label' => 'A-12', 'warehouse_instructions' => 'bring 8am']);
    $r->refresh();
    expect($r->status)->toBe('accepted');
    expect($r->storage_shelf_label)->toBe('A-12');
    expect($r->warehouse_staff_user_id)->toBe($admin->id);

    $svc->transition($r, 'confirmStored', $admin);
    expect($r->refresh()->status)->toBe('stored');
    expect($r->stored_at)->not->toBeNull();

    $svc->transition($r, 'confirmClaim', $admin, ['claim_date' => now()->toDateString()]);
    $r->refresh();
    expect($r->status)->toBe('claimed');
    expect($r->actual_claim_date)->not->toBeNull();
});

test('accept requires a storage location or shelf', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $svc = app(DepositService::class);
    $r = aDepositDraft($admin);
    $svc->transition($r, 'submit', $admin);

    expect(fn () => $svc->transition($r->refresh(), 'accept', $admin, ['warehouse_instructions' => 'x']))
        ->toThrow(ValidationException::class);
});

test('needs_fix loop: flag then confirmFixed returns to stored', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $svc = app(DepositService::class);
    $r = aDepositDraft($admin);
    $svc->transition($r, 'submit', $admin);
    $svc->transition($r, 'accept', $admin, ['storage_shelf_label' => 'A-1']);
    $svc->transition($r, 'confirmStored', $admin);

    $svc->transition($r->refresh(), 'flagIssue', $admin, ['reason' => 'rusty']);
    $r->refresh();
    expect($r->status)->toBe('needs_fix');
    expect($r->needs_fix_reason)->toBe('rusty');

    $svc->transition($r, 'confirmFixed', $admin);
    $r->refresh();
    expect($r->status)->toBe('stored');
    expect($r->needs_fix_reason)->toBeNull();
});

test('cancel works from submitted', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $svc = app(DepositService::class);
    $r = aDepositDraft($admin);
    $svc->transition($r, 'submit', $admin);

    $svc->transition($r->refresh(), 'cancel', $admin, ['reason' => 'mistake']);
    expect($r->refresh()->status)->toBe('cancelled');
});

test('cannot accept a stored record (invalid transition)', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $svc = app(DepositService::class);
    $r = aDepositDraft($admin);
    $svc->transition($r, 'submit', $admin);
    $svc->transition($r, 'accept', $admin, ['storage_shelf_label' => 'A-1']);
    $svc->transition($r, 'confirmStored', $admin);

    expect(fn () => $svc->transition($r->refresh(), 'accept', $admin, ['storage_shelf_label' => 'B-2']))
        ->toThrow(ValidationException::class);
});

test('admin can soft-delete a claimed record and restore it', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $svc = app(DepositService::class);
    $r = aDepositDraft($admin);
    $svc->transition($r, 'submit', $admin);
    $svc->transition($r, 'accept', $admin, ['storage_shelf_label' => 'A-1']);
    $svc->transition($r, 'confirmStored', $admin);
    $svc->transition($r, 'confirmClaim', $admin);
    $r->refresh();

    Livewire::test(Show::class, ['record' => $r])
        ->call('openDelete')
        ->set('deleteReason', 'dup')
        ->call('deleteRecord');

    $r->refresh();
    expect($r->trashed())->toBeTrue();
    expect($r->deleted_reason)->toBe('dup');

    Livewire::test(Index::class)->set('showDeleted', true)->call('restore', $r->id);
    $r->refresh();
    expect($r->trashed())->toBeFalse();
});

test('stored record cannot be deleted', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $svc = app(DepositService::class);
    $r = aDepositDraft($admin);
    $svc->transition($r, 'submit', $admin);
    $svc->transition($r, 'accept', $admin, ['storage_shelf_label' => 'A-1']);
    $svc->transition($r, 'confirmStored', $admin);

    Livewire::test(Show::class, ['record' => $r->refresh()])->call('openDelete')->assertForbidden();
});

test('non-permitted user cannot open deposit index', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => false]));
    Livewire::test(Index::class)->assertForbidden();
});

test('a deposit item stores asset_code (ທະບຽນເຄື່ອງ) and fixed_asset_no (ທະບຽນຊັບສິນ)', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(App\Livewire\Deposit\Create::class)
        ->set('item_category', 'Tools')
        ->set('origin_source', 'Personal')
        ->set('deposit_reason', 'no space')
        ->set('expected_duration', '1 month')
        ->set('items', [[
            'item_name' => 'Fluke 175', 'asset_code' => 'EL-T004-1', 'fixed_asset_no' => 'FA-9001', 'qty' => 1,
        ]])
        ->call('save')
        ->assertHasNoErrors();

    $item = App\Models\DepositItem::where('item_name', 'Fluke 175')->first();
    expect($item)->not->toBeNull();
    expect($item->asset_code)->toBe('EL-T004-1');
    expect($item->fixed_asset_no)->toBe('FA-9001');
});
