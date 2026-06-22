<?php

use App\Livewire\Request\Index;
use App\Livewire\Request\Show;
use App\Models\MaterialRequest;
use App\Models\Setting;
use App\Models\User;
use App\Services\RequestService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function aRequestDraft(User $actor, array $over = []): MaterialRequest
{
    return app(RequestService::class)->createDraft(array_merge([
        'purpose' => 'spare parts', 'currency' => 'THB', 'approver_user_id' => $actor->id,
        'items' => [['material_id' => null, 'description' => 'Bolt M8', 'unit' => 'pcs', 'quantity' => 10, 'unit_price' => 5]],
    ], $over), $actor);
}

test('createDraft makes MR number, computes total, logs history', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $r = aRequestDraft($admin);

    expect($r->request_number)->toStartWith('MR'.now()->year.'-');
    expect($r->status)->toBe('draft');
    expect((float) $r->total)->toBe(50.0);
    expect($r->history()->where('action', 'create')->exists())->toBeTrue();
});

test('submit freezes VAT snapshot from global setting', function () {
    Setting::put('vat', ['rate' => 10, 'enabled' => true], 1);
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $r = aRequestDraft($admin);

    app(RequestService::class)->transition($r, 'submit', $admin);
    $r->refresh();

    expect($r->status)->toBe('submitted');
    expect((float) $r->vat_rate)->toBe(10.0);
    expect($r->vat_enabled)->toBeTrue();
    expect((float) $r->vat_amount)->toBe(5.0);     // 50 * 10%
    expect((float) $r->grand_total)->toBe(55.0);
});

test('changing global VAT later does not affect a submitted request', function () {
    Setting::put('vat', ['rate' => 10, 'enabled' => true], 1);
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $r = aRequestDraft($admin);
    app(RequestService::class)->transition($r, 'submit', $admin);

    // VAT changes in the future
    Setting::put('vat', ['rate' => 7, 'enabled' => true], 1);

    expect((float) $r->refresh()->vat_rate)->toBe(10.0);    // frozen
    expect((float) $r->grand_total)->toBe(55.0);
});

test('full workflow: submit → approve → validate → dispatch → receive → close', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $svc = app(RequestService::class);
    $r = aRequestDraft($admin);

    $svc->transition($r, 'submit', $admin);
    $svc->transition($r, 'approve', $admin);
    expect($r->refresh()->status)->toBe('approved');
    $svc->transition($r, 'validate', $admin);
    $svc->transition($r, 'dispatch', $admin, ['delivery_method' => 'supplier_delivery']);
    expect($r->refresh()->status)->toBe('dispatched');
    $svc->transition($r, 'confirmReceipt', $admin, ['invoice_received' => true]);
    expect($r->refresh()->status)->toBe('received');
    $svc->transition($r, 'close', $admin, ['invoice_number' => 'INV-1', 'sap_reference' => 'PR-9']);
    $r->refresh();
    expect($r->status)->toBe('completed');
    expect($r->invoice_number)->toBe('INV-1');
});

test('reject requires reason and moves to rejected', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $svc = app(RequestService::class);
    $r = aRequestDraft($admin);
    $svc->transition($r, 'submit', $admin);

    expect(fn () => $svc->transition($r->refresh(), 'reject', $admin, []))->toThrow(ValidationException::class);

    $svc->transition($r->refresh(), 'reject', $admin, ['reason' => 'budget']);
    expect($r->refresh()->status)->toBe('rejected');
});

test('close requires invoice and SAP', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $svc = app(RequestService::class);
    $r = aRequestDraft($admin);
    foreach (['submit', 'approve', 'validate'] as $a) {
        $svc->transition($r, $a, $admin);
    }
    $svc->transition($r, 'dispatch', $admin, []);
    $svc->transition($r, 'confirmReceipt', $admin, []);

    expect(fn () => $svc->transition($r->refresh(), 'close', $admin, ['invoice_number' => 'X']))
        ->toThrow(ValidationException::class);
});

test('admin can soft-delete a completed request and restore it', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $svc = app(RequestService::class);
    $r = aRequestDraft($admin);
    foreach (['submit', 'approve', 'validate'] as $a) {
        $svc->transition($r, $a, $admin);
    }
    $svc->transition($r, 'dispatch', $admin, []);
    $svc->transition($r, 'confirmReceipt', $admin, []);
    $svc->transition($r, 'close', $admin, ['invoice_number' => 'INV', 'sap_reference' => 'PR']);
    $r->refresh();

    Livewire::test(Show::class, ['record' => $r])->call('openDelete')->set('deleteReason', 'dup')->call('deleteRecord');
    expect($r->refresh()->trashed())->toBeTrue();

    Livewire::test(Index::class)->set('showDeleted', true)->call('restore', $r->id);
    expect($r->refresh()->trashed())->toBeFalse();
});

test('requester sees only own requests', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $r1 = aRequestDraft($admin, ['purpose' => 'ADMIN-REQ']);

    $req = User::factory()->create(['is_super_admin' => false]);
    $req->assignRole('requester');
    $this->actingAs($req);
    $mine = aRequestDraft($req, ['purpose' => 'MINE-REQ', 'approver_user_id' => $admin->id]);

    Livewire::test(Index::class)->assertSee($mine->request_number)->assertDontSee($r1->request_number);
});

test('non-permitted user cannot open request index', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => false]));
    Livewire::test(Index::class)->assertForbidden();
});

test('approver is optional when admin disables the field', function () {
    Setting::put('request', ['fields' => ['supplier' => true, 'currency' => true, 'rooms' => true, 'functions' => true, 'approver' => false, 'request_type' => true, 'wo_e_form' => true]], 1);
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(\App\Livewire\Request\Create::class)
        ->set('purpose', 'no approver needed')
        ->call('addFreeItem')
        ->set('items.0.description', 'Wire')
        ->set('items.0.quantity', 2)
        ->set('items.0.unit_price', 3)
        ->call('save', true)   // submit without approver
        ->assertHasNoErrors();

    expect(\App\Models\MaterialRequest::where('purpose', 'no approver needed')->where('status', 'submitted')->exists())->toBeTrue();
});

test('price comparison captures other suppliers selling the same material_nbr', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);

    $s1 = \App\Models\Supplier::create(['slug' => 's1-'.uniqid(), 'name' => 'SupplierOne', 'is_active' => true]);
    $s2 = \App\Models\Supplier::create(['slug' => 's2-'.uniqid(), 'name' => 'SupplierTwo', 'is_active' => true]);
    $m1 = \App\Models\Material::create(['supplier_id' => $s1->id, 'material_nbr' => 'X-100', 'category' => 'C', 'description' => 'Pump', 'unit_price' => 500, 'currency' => 'THB']);
    \App\Models\Material::create(['supplier_id' => $s2->id, 'material_nbr' => 'X-100', 'category' => 'C', 'description' => 'Pump', 'unit_price' => 450, 'currency' => 'THB']);

    $r = app(RequestService::class)->createDraft([
        'purpose' => 'pump', 'currency' => 'THB', 'approver_user_id' => $admin->id,
        'items' => [['material_id' => $m1->id, 'description' => 'Pump', 'quantity' => 1, 'unit_price' => 500]],
    ], $admin);

    $item = $r->items->first();
    expect($item->shop_prices)->toBeArray();
    expect($item->shop_prices)->toHaveCount(1);
    expect($item->shop_prices[0]['supplier_name'])->toBe('SupplierTwo');
    expect((float) $item->shop_prices[0]['unit_price'])->toBe(450.0);
});
