<?php

use App\Models\User;
use App\Services\DisposalService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function aDisposalDraft(User $actor, array $overrides = [])
{
    return app(DisposalService::class)->createDraft(array_merge([
        'title' => 'ຈຳໜ່າຍ ເຄື່ອງມື ຊຳລຸດ',
        'items' => [[
            'source_type' => 'equipment', 'source_id' => 10,
            'item_name' => 'ໄຂຄວງ ໄຟຟ້າ Bosch', 'asset_code' => 'EL-T004-1', 'fixed_asset_no' => 'FA-9001',
            'qty' => 1, 'unit' => 'ອັນ', 'condition' => 'ມໍເຕີ ໄໝ້',
            'reason' => 'ຊຳລຸດ', 'recommendation' => 'ທຳລາຍ',
            'history' => [['date' => '2026-07-20', 'kind' => 'repair', 'problem' => 'ມໍເຕີ ໄໝ້', 'action' => 'ບໍ່ ຄຸ້ມ ສ້ອມ']],
        ]],
    ], $overrides), $actor);
}

test('createDraft generates a DS number with items, history snapshot, and a create history', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $r = aDisposalDraft($admin);

    expect($r->request_number)->toStartWith('DS'.now()->year.'-');
    expect($r->status)->toBe('draft');
    expect($r->prepared_by_user_id)->toBe($admin->id);
    expect($r->items)->toHaveCount(1);

    $item = $r->items->first();
    expect($item->source_type)->toBe('equipment');
    expect($item->asset_code)->toBe('EL-T004-1');
    expect($item->reason)->toBe('ຊຳລຸດ');
    expect($item->history)->toBeArray()->toHaveCount(1);
    expect($item->history[0]['problem'])->toBe('ມໍເຕີ ໄໝ້');

    expect($r->history()->where('action', 'create')->exists())->toBeTrue();
});

test('the DS counter increments per record within the year', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $a = aDisposalDraft($admin);
    $b = aDisposalDraft($admin);

    expect($a->request_number)->toBe('DS'.now()->year.'-0001');
    expect($b->request_number)->toBe('DS'.now()->year.'-0002');
});

test('submit moves draft to committee_review and stamps the preparer sign-off', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $svc = app(DisposalService::class);
    $r = aDisposalDraft($admin);

    $svc->transition($r, 'submit', $admin);
    $r->refresh();

    expect($r->status)->toBe('committee_review');
    expect($r->prepared_at)->not->toBeNull();
    $sign = $r->signoffs()->where('role_key', 'preparer')->first();
    expect($sign)->not->toBeNull();
    expect($sign->signed_at)->not->toBeNull();
    expect($sign->user_id)->toBe($admin->id);
});

test('submit is blocked when the draft has no items', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $svc = app(DisposalService::class);
    $r = aDisposalDraft($admin, ['items' => []]);

    expect(fn () => $svc->transition($r, 'submit', $admin))->toThrow(ValidationException::class);
    expect($r->refresh()->status)->toBe('draft');
});

test('cancel sets the record cancelled with a reason', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $svc = app(DisposalService::class);
    $r = aDisposalDraft($admin);

    $svc->transition($r, 'cancel', $admin, ['reason' => 'ບັນທຶກ ຊ້ຳ']);
    $r->refresh();

    expect($r->status)->toBe('cancelled');
    expect($r->cancel_reason)->toBe('ບັນທຶກ ຊ້ຳ');
});

test('the disposal menu grants the expected permissions per role', function () {
    $ws = User::factory()->create();
    $ws->syncRoles(['warehouse_staff']);
    expect($ws->can('disposal.create'))->toBeTrue();
    expect($ws->can('disposal.activate'))->toBeTrue();   // adminPerm ໃຫ້ activate (=ເຊັນ/ອະນຸມັດ)
    expect($ws->can('disposal.delete'))->toBeFalse();     // adminPerm ບໍ່ ໃຫ້ delete

    $approver = User::factory()->create();
    $approver->syncRoles(['approver']);
    expect($approver->can('disposal.activate'))->toBeTrue();

    $requester = User::factory()->create();
    $requester->syncRoles(['requester']);
    expect($requester->can('disposal.view'))->toBeFalse();
});
