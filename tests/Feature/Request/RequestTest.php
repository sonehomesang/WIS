<?php

use App\Livewire\Request\Create;
use App\Livewire\Request\Index;
use App\Livewire\Request\Show;
use App\Models\Material;
use App\Models\MaterialRequest;
use App\Models\Setting;
use App\Models\Supplier;
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

test('partial receipt keeps dispatched until every item is fully received', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $svc = app(RequestService::class);
    $r = aRequestDraft($admin, ['items' => [
        ['material_id' => null, 'description' => 'Bolt M8', 'unit' => 'pcs', 'quantity' => 10, 'unit_price' => 5],
        ['material_id' => null, 'description' => 'Nut M8', 'unit' => 'pcs', 'quantity' => 4, 'unit_price' => 2],
    ]]);
    foreach (['submit', 'approve', 'validate', 'dispatch'] as $a) {
        $svc->transition($r, $a, $admin);
    }
    expect($r->refresh()->status)->toBe('dispatched');

    $items = $r->items()->orderBy('id')->get();

    // partial: first item full (10/10), second item 2/4 → stays dispatched
    $svc->transition($r, 'confirmReceipt', $admin, ['received' => [$items[0]->id => 10, $items[1]->id => 2]]);
    $r->refresh();
    expect($r->status)->toBe('dispatched');
    expect($r->items()->find($items[1]->id)->received_qty)->toBe(2);
    expect($r->items()->find($items[0]->id)->receiver_confirmed)->toBeTrue();

    // receive the remainder → fully received
    $svc->transition($r, 'confirmReceipt', $admin, ['received' => [$items[0]->id => 10, $items[1]->id => 4]]);
    $r->refresh();
    expect($r->status)->toBe('received');
    expect($r->received_at)->not->toBeNull();
});

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
    $svc->transition($r, 'close', $admin, ['invoice_number' => 'INV-1', 'sap_reference' => 'PR-9', 'sap_status' => 'fr_issued']);
    $r->refresh();
    expect($r->status)->toBe('completed');
    expect($r->invoice_number)->toBe('INV-1');
    expect($r->sap_status)->toBe('fr_issued');
    expect($r->sapStatusLabel())->toBe('FR issued');
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

test('close via Livewire: SAP status optional when blank, persists when picked', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $svc = app(RequestService::class);

    $toReceived = function () use ($admin, $svc) {
        $r = aRequestDraft($admin);
        foreach (['submit', 'approve', 'validate', 'dispatch', 'confirmReceipt'] as $step) {
            $svc->transition($r, $step, $admin, []);
        }

        return $r->refresh();
    };

    // blank SAP status → close still succeeds (field is optional)
    Livewire::test(Show::class, ['record' => $toReceived()])
        ->set('invoiceNumber', 'INV-2')->set('sapReference', 'PR-2')
        ->call('close')->assertHasNoErrors();

    // picked SAP status → persists
    $r2 = $toReceived();
    Livewire::test(Show::class, ['record' => $r2])
        ->set('invoiceNumber', 'INV-3')->set('sapReference', 'PR-3')->set('sapStatus', 'pr_raised')
        ->call('close')->assertHasNoErrors();
    expect($r2->refresh()->sap_status)->toBe('pr_raised');

    // invalid SAP status → rejected
    Livewire::test(Show::class, ['record' => $toReceived()])
        ->set('invoiceNumber', 'INV-4')->set('sapReference', 'PR-4')->set('sapStatus', 'bogus')
        ->call('close')->assertHasErrors('sapStatus');
});

test('request index shows purpose, WO and SAP status columns', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $svc = app(RequestService::class);
    $r = aRequestDraft($admin);
    $r->forceFill(['purpose' => 'Maintain PWH pump', 'request_type' => 'CM', 'wo_e_form' => 'WO-83000123'])->save();
    foreach (['submit', 'approve', 'validate', 'dispatch', 'confirmReceipt'] as $s) {
        $svc->transition($r, $s, $admin, []);
    }
    $svc->transition($r->refresh(), 'close', $admin, ['invoice_number' => 'INV', 'sap_reference' => 'PR', 'sap_status' => 'fr_issued']);

    Livewire::test(Index::class)
        ->assertSee('ຈຸດປະສົງ')            // Purpose column header
        ->assertSee('Maintain PWH pump')  // purpose value
        ->assertSee('WO-83000123')        // work order value
        ->assertSee('FR issued');         // SAP status label
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

    Livewire::test(Create::class)
        ->set('purpose', 'no approver needed')
        ->call('addFreeItem')
        ->set('items.0.description', 'Wire')
        ->set('items.0.quantity', 2)
        ->set('items.0.unit_price', 3)
        ->call('save', true)   // submit without approver
        ->assertHasNoErrors();

    expect(MaterialRequest::where('purpose', 'no approver needed')->where('status', 'submitted')->exists())->toBeTrue();
});

test('price comparison captures other suppliers selling the same material_nbr', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);

    $s1 = Supplier::create(['slug' => 's1-'.uniqid(), 'name' => 'SupplierOne', 'is_active' => true]);
    $s2 = Supplier::create(['slug' => 's2-'.uniqid(), 'name' => 'SupplierTwo', 'is_active' => true]);
    $m1 = Material::create(['supplier_id' => $s1->id, 'material_nbr' => 'X-100', 'category' => 'C', 'description' => 'Pump', 'unit_price' => 500, 'currency' => 'THB']);
    Material::create(['supplier_id' => $s2->id, 'material_nbr' => 'X-100', 'category' => 'C', 'description' => 'Pump', 'unit_price' => 450, 'currency' => 'THB']);

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
