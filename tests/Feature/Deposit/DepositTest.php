<?php

use App\Livewire\Deposit\Create;
use App\Livewire\Deposit\Index;
use App\Livewire\Deposit\Show;
use App\Models\DepositItem;
use App\Models\DepositRecord;
use App\Models\Equipment;
use App\Models\InventoryItem;
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

test('only a super admin can reset a deposit status (correction)', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $r = aDepositDraft($admin);
    $r->update(['status' => 'claimed']);   // e.g. wrongly claimed

    // super admin resets claimed → stored
    Livewire::test(Show::class, ['record' => $r])
        ->call('openStatusReset')
        ->set('resetStatus', 'stored')
        ->call('applyStatusReset')
        ->assertHasNoErrors();
    expect($r->refresh()->status)->toBe('stored');
    expect($r->history()->where('action', 'status_reset')->exists())->toBeTrue();

    // a non-super warehouse user cannot
    $staff = User::factory()->create(['is_super_admin' => false]);
    $staff->syncRoles(['warehouse_staff']);
    $this->actingAs($staff);
    Livewire::test(Show::class, ['record' => $r->fresh()])->call('openStatusReset')->assertForbidden();
});

test('legacy deposit type carries original deposit date + receiver, editable on show', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);

    $r = app(DepositService::class)->createDraft([
        'request_type' => 'legacy', 'item_category' => 'Tools', 'origin_source' => 'Old store',
        'functional_status' => 'partial',
        'original_deposit_date' => '2019-06-01', 'original_receiver' => 'ທ້າວ ບຸນ',
        'deposit_reason' => 'sitting long', 'expected_duration' => 'n/a', 'deposit_date' => now()->toDateString(),
        'items' => [['item_name' => 'Dusty toolbox', 'qty' => 1]],
    ], $admin);

    expect($r->request_type)->toBe('legacy');
    expect($r->functional_status)->toBe('partial');
    expect($r->original_deposit_date->format('Y-m-d'))->toBe('2019-06-01');
    expect($r->original_receiver)->toBe('ທ້າວ ບຸນ');

    Livewire::test(Show::class, ['record' => $r])
        ->call('openEdit')
        ->assertSet('ef.functional_status', 'partial')
        ->assertSet('ef.original_deposit_date', '2019-06-01')
        ->assertSet('ef.original_receiver', 'ທ້າວ ບຸນ')
        ->set('ef.functional_status', 'unusable')
        ->set('ef.original_receiver', 'ນາງ ຄຳ')
        ->call('saveEdit')
        ->assertHasNoErrors();

    $r->refresh();
    expect($r->original_receiver)->toBe('ນາງ ຄຳ');
    expect($r->functional_status)->toBe('unusable');
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

    Livewire::test(Create::class)
        ->set('item_category', 'Tools')
        ->set('origin_source', 'Personal')
        ->set('deposit_reason', 'no space')
        ->set('expected_duration', '1 month')
        ->set('items', [[
            'item_name' => 'Fluke 175', 'asset_code' => 'EL-T004-1', 'fixed_asset_no' => 'FA-9001', 'qty' => 1,
        ]])
        ->call('save')
        ->assertHasNoErrors();

    $item = DepositItem::where('item_name', 'Fluke 175')->first();
    expect($item)->not->toBeNull();
    expect($item->asset_code)->toBe('EL-T004-1');
    expect($item->fixed_asset_no)->toBe('FA-9001');
});

test('the asset lookup can pull from the Equipment & Tools register', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $eq = Equipment::create([
        'asset_code' => 'EL-T004-1', 'fixed_asset_no' => 'FA-9001', 'name' => 'Fluke 175', 'quantity' => 1,
    ]);
    $this->actingAs($admin);

    Livewire::test(Create::class)
        ->set('items.0.asset_source', 'equipment')        // ເລືອກ ແຫຼ່ງ = Equipment
        ->set('items.0.asset_code', 'EL-T004')            // ພິມ → ຄົ້ນ
        ->assertSet('assetMatches.0.0.id', $eq->id)
        ->assertSet('assetMatches.0.0.source', 'equipment')
        ->call('pickAsset', 0, 'equipment', $eq->id)      // ເລືອກ
        ->assertSet('items.0.asset_code', 'EL-T004-1')
        ->assertSet('items.0.fixed_asset_no', 'FA-9001')  // Equipment ຕື່ມ ເລກ ຊັບສິນ ໃຫ້ ນຳ
        ->assertSet('items.0.item_name', 'Fluke 175')
        ->assertSet('assetMatches.0', []);
});

test('the asset lookup can pull from the Inventory register (material no.)', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $inv = InventoryItem::create(['slug' => 'EL-0001', 'name' => 'Cable 2.5mm', 'quantity' => 10]);
    $this->actingAs($admin);

    Livewire::test(Create::class)
        ->set('items.0.asset_source', 'inventory')        // ແຫຼ່ງ = Inventory (default)
        ->set('items.0.asset_code', 'EL-0001')
        ->assertSet('assetMatches.0.0.id', $inv->id)
        ->assertSet('assetMatches.0.0.source', 'inventory')
        ->call('pickAsset', 0, 'inventory', $inv->id)
        ->assertSet('items.0.asset_code', 'EL-0001')      // ດຶງ Material No. (slug)
        ->assertSet('items.0.item_name', 'Cable 2.5mm')
        ->assertSet('assetMatches.0', []);
});

test('a short asset-code term returns no lookup matches', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    Equipment::create(['asset_code' => 'EL-T004-1', 'name' => 'Fluke', 'quantity' => 1]);
    $this->actingAs($admin);

    Livewire::test(Create::class)
        ->set('items.0.asset_source', 'equipment')
        ->set('items.0.asset_code', 'E')                  // < 2 ຕົວ → ບໍ່ ຄົ້ນ
        ->assertSet('assetMatches.0', []);
});
