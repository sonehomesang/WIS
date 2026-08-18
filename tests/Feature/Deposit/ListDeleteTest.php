<?php

use App\Livewire\Deposit\Index;
use App\Models\DepositRecord;
use App\Models\User;
use App\Services\DepositService;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function makeDeposit(string $status = 'draft'): DepositRecord
{
    $u = User::factory()->create(['is_super_admin' => true]);
    $rec = app(DepositService::class)->createDraft([
        'request_type' => 'legacy', 'deposit_date' => now()->toDateString(),
        'items' => [['item_name' => 'X', 'qty' => 1]],
    ], $u);
    $rec->update(['status' => $status]);

    return $rec;
}

test('admin can soft-delete a deposit from the list with a reason', function () {
    $rec = makeDeposit('draft');
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(Index::class)
        ->call('openDelete', $rec->id)
        ->set('deleteReason', 'ບັນທຶກ ຊ້ຳ')
        ->call('deleteRecord');

    expect(DepositRecord::withTrashed()->find($rec->id)->trashed())->toBeTrue();
    expect(DepositRecord::withTrashed()->find($rec->id)->deleted_reason)->toBe('ບັນທຶກ ຊ້ຳ');
    expect($rec->history()->where('action', 'delete')->exists())->toBeTrue();
});

test('delete is blocked without a reason', function () {
    $rec = makeDeposit('draft');
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(Index::class)
        ->call('openDelete', $rec->id)
        ->set('deleteReason', '')
        ->call('deleteRecord')
        ->assertHasErrors(['deleteReason']);

    expect(DepositRecord::find($rec->id))->not->toBeNull(); // still there
});

test('a non-admin without deposit.delete cannot open the delete modal', function () {
    $rec = makeDeposit('draft');
    $viewer = User::factory()->create();
    $viewer->givePermissionTo('deposit.view');
    $this->actingAs($viewer);

    Livewire::test(Index::class)->call('openDelete', $rec->id)->assertForbidden();
});

test('a deposit that is accepted/stored cannot be deleted (guard)', function () {
    $rec = makeDeposit('stored');
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(Index::class)->call('openDelete', $rec->id)->assertForbidden();
});

test('a deleted deposit can be restored', function () {
    $rec = makeDeposit('draft');
    $rec->forceFill(['deleted_reason' => 'x', 'deleted_by' => 1])->save();
    $rec->delete();
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(Index::class)->call('restore', $rec->id);

    expect(DepositRecord::find($rec->id))->not->toBeNull();
    expect(DepositRecord::find($rec->id)->deleted_reason)->toBeNull();
});
