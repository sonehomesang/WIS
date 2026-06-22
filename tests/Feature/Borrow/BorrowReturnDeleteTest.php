<?php

use App\Livewire\Borrow\Index;
use App\Livewire\Borrow\Show;
use App\Models\BorrowRecord;
use App\Models\User;
use App\Services\BorrowService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

/** draft → active (default workflow: approve required, ack optional/off). */
function anActiveRecord(User $actor): BorrowRecord
{
    $svc = app(BorrowService::class);
    $r = $svc->createDraft([
        'borrow_type' => 'new_inventory', 'borrow_date' => now()->toDateString(),
        'period_days' => 5, 'items' => [['item_name' => 'Drill', 'qty' => 1]],
    ], $actor);
    $svc->transition($r, 'submit', $actor);
    $svc->transition($r, 'approve', $actor);
    $svc->transition($r, 'confirmTake', $actor);

    return $r->refresh();
}

test('borrower can request return (step 1) without changing status', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $r = anActiveRecord($admin);

    expect($r->status)->toBe('active');
    expect($r->borrower_return_ack)->toBeFalse();

    Livewire::test(Show::class, ['record' => $r])
        ->call('openRequestReturn')
        ->set('rrDate', now()->toDateString())
        ->set('rrRemarks', 'ສົ່ງບໍ່ຄົບ 1 ອັນ')
        ->call('requestReturn')
        ->assertHasNoErrors();

    $r->refresh();
    expect($r->borrower_return_ack)->toBeTrue();
    expect($r->status)->toBe('active'); // ຍັງ active — ລໍ warehouse ຢືนยัน
    expect($r->return_remarks)->toBe('ສົ່ງບໍ່ຄົບ 1 ອັນ');
});

test('warehouse confirmReturn (step 2) sets returned + both acks', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $r = anActiveRecord($admin);

    app(BorrowService::class)->transition($r, 'confirmReturn', $admin, ['return_qty' => [$r->items->first()->id => 1]]);

    $r->refresh();
    expect($r->status)->toBe('returned');
    expect($r->borrower_return_ack)->toBeTrue();
    expect($r->wh_return_ack)->toBeTrue();
});

test('cannot request return twice', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $r = anActiveRecord($admin);

    $svc = app(BorrowService::class);
    $svc->transition($r, 'requestReturn', $admin, ['return_date' => now()->toDateString()]);

    expect(fn () => $svc->transition($r->refresh(), 'requestReturn', $admin, []))
        ->toThrow(ValidationException::class);
});

test('admin can soft-delete a returned record with reason and restore it', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $r = anActiveRecord($admin);
    app(BorrowService::class)->transition($r, 'confirmReturn', $admin, ['return_qty' => [$r->items->first()->id => 1]]);
    $r->refresh();

    Livewire::test(Show::class, ['record' => $r])
        ->call('openDelete')
        ->set('deleteReason', 'ຊ້ำ')
        ->call('deleteRecord');

    $r->refresh();
    expect($r->trashed())->toBeTrue();
    expect($r->deleted_reason)->toBe('ຊ້ำ');
    expect($r->deleted_by)->toBe($admin->id);

    // restore via index
    Livewire::test(Index::class)
        ->set('showDeleted', true)
        ->call('restore', $r->id);

    $r->refresh();
    expect($r->trashed())->toBeFalse();
    expect($r->deleted_reason)->toBeNull();
});

test('active record cannot be deleted', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $r = anActiveRecord($admin);

    Livewire::test(Show::class, ['record' => $r])->call('openDelete')->assertForbidden();
});

test('viewer without edit cannot delete', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $r = anActiveRecord($admin);
    app(BorrowService::class)->transition($r, 'confirmReturn', $admin, ['return_qty' => [$r->items->first()->id => 1]]);

    $viewer = User::factory()->create(['is_super_admin' => false]);
    $viewer->givePermissionTo('borrow.view');
    $this->actingAs($viewer);

    Livewire::test(Show::class, ['record' => $r->refresh()])->call('openDelete')->assertForbidden();
});
