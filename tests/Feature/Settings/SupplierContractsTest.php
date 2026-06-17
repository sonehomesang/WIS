<?php

use App\Livewire\Settings\SupplierDetail;
use App\Livewire\Settings\Suppliers;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\SupplierVatChange;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function aSupplier(): Supplier
{
    return Supplier::create(['slug' => 'abc', 'name' => 'ABC', 'default_currency' => 'LAK', 'is_active' => true]);
}

test('super admin can add a contract record', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
    $s = aSupplier();

    Livewire::test(SupplierDetail::class, ['supplier' => $s])
        ->call('newContract')->set('contract_number', 'WIS-2026-SUP01')->set('status', 'active')->call('saveContract')->assertHasNoErrors();

    expect($s->contracts()->where('contract_number', 'WIS-2026-SUP01')->exists())->toBeTrue();
});

test('contract expiry before effective is rejected', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
    $s = aSupplier();

    Livewire::test(SupplierDetail::class, ['supplier' => $s])
        ->call('newContract')->set('contract_number', 'C2')->set('effective_date', '2026-12-31')->set('expiry_date', '2026-01-01')
        ->call('saveContract')->assertHasErrors(['expiry_date']);
});

test('resolveVat uses supplier rate over global', function () {
    Setting::put('vat', ['rate' => 10, 'enabled' => true]);
    $s = aSupplier();
    $s->update(['vat_rate' => 7]);

    $vat = $s->fresh()->resolveVat();
    expect($vat['rate'])->toEqual(7.0);
    expect($vat['source'])->toBe('supplier');
});

test('resolveVat falls back to global when supplier rate is null', function () {
    Setting::put('vat', ['rate' => 10, 'enabled' => true]);
    $s = aSupplier();

    $vat = $s->resolveVat();
    expect($vat['rate'])->toEqual(10.0);
    expect($vat['source'])->toBe('global');
});

test('changing supplier VAT requires a reason and logs the change', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
    $s = aSupplier();

    $c = Livewire::test(Suppliers::class)->call('editItem', $s->id)->set('vat_rate', 7);
    $c->call('save')->assertHasErrors(['vat_reason']);

    $c->set('vat_reason', 'ສັນຍາໃໝ່ 2026')->call('save')->assertHasNoErrors();

    expect($s->fresh()->vat_rate)->toEqual(7.0);
    expect(SupplierVatChange::where('supplier_id', $s->id)->where('new_rate', 7)->exists())->toBeTrue();
});
