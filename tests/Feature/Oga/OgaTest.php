<?php

use App\Livewire\Oga\Index;
use App\Livewire\Oga\Show;
use App\Models\DiscrepancyAdvice;
use App\Models\OutwardsGoodsAdvice;
use App\Models\User;
use App\Services\OgaService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function anOgaDraft(User $actor): OutwardsGoodsAdvice
{
    return app(OgaService::class)->createDraft([
        'date' => now()->toDateString(), 'ship_via' => 'road', 'goods_consigned' => '1 x pump',
        'items' => [['description' => 'Pump', 'unit' => 'EA', 'qty' => 1, 'unit_weight_kg' => 18.5]],
    ], $actor);
}

test('createDraft makes OGA number, computes weight, logs history', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $r = anOgaDraft($admin);

    expect($r->oga_number)->toStartWith('OGA'.now()->year.'-');
    expect($r->status)->toBe('draft');
    expect((float) $r->total_weight_kg)->toBe(18.5);
    expect($r->source_type)->toBe('oga');
    expect($r->history()->where('action', 'create')->exists())->toBeTrue();
});

test('full workflow: dispatch → delivered', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $svc = app(OgaService::class);
    $r = anOgaDraft($admin);

    $svc->transition($r, 'confirmDispatch', $admin, ['driver_name' => 'Mr A', 'truck_plate_number' => 'KH1']);
    $r->refresh();
    expect($r->status)->toBe('dispatched');
    expect($r->driver_name)->toBe('Mr A');
    expect($r->authorized_at)->not->toBeNull();

    $svc->transition($r, 'confirmDelivery', $admin);
    $r->refresh();
    expect($r->status)->toBe('delivered');
    expect($r->completed_at)->not->toBeNull();
});

test('dispatched can be returned with a reason', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $svc = app(OgaService::class);
    $r = anOgaDraft($admin);
    $svc->transition($r, 'confirmDispatch', $admin, []);

    expect(fn () => $svc->transition($r->refresh(), 'returnRejected', $admin, []))->toThrow(ValidationException::class);

    $svc->transition($r->refresh(), 'returnRejected', $admin, ['reason' => 'refused']);
    expect($r->refresh()->status)->toBe('returned');
});

test('cannot deliver before dispatch', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $r = anOgaDraft($admin);

    expect(fn () => app(OgaService::class)->transition($r, 'confirmDelivery', $admin))
        ->toThrow(ValidationException::class);
});

test('admin can soft-delete a delivered OGA and restore it', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $svc = app(OgaService::class);
    $r = anOgaDraft($admin);
    $svc->transition($r, 'confirmDispatch', $admin, []);
    $svc->transition($r, 'confirmDelivery', $admin);
    $r->refresh();

    Livewire::test(Show::class, ['record' => $r])->call('openDelete')->set('deleteReason', 'dup')->call('deleteRecord');
    expect($r->refresh()->trashed())->toBeTrue();

    Livewire::test(Index::class)->set('showDeleted', true)->call('restore', $r->id);
    expect($r->refresh()->trashed())->toBeFalse();
});

test('OGA created from a DA is tagged with the source DA', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $da = DiscrepancyAdvice::create([
        'da_number' => 'DA'.now()->year.'-9001', 'date' => now()->toDateString(),
        'status' => 'resolved', 'next_step' => 'oga', 'supplier_name' => 'ACME',
    ]);

    $oga = app(OgaService::class)->createDraft([
        'date' => now()->toDateString(), 'ship_via' => 'road', 'goods_consigned' => 'return',
        'source_da_id' => $da->id,
        'items' => [['description' => 'X', 'qty' => 1]],
    ], $admin);

    expect($oga->source_type)->toBe('da');
    expect($oga->source_da_id)->toBe($da->id);
    expect($oga->source_da_number)->toBe($da->da_number);

    // DA Show lists the linked OGA
    Livewire::test(App\Livewire\Da\Show::class, ['record' => $da])->assertSee($oga->oga_number);
});

test('non-permitted user cannot open OGA index', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => false]));
    Livewire::test(Index::class)->assertForbidden();
});
