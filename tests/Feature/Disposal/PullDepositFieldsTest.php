<?php
use App\Livewire\Disposal\Create;
use App\Models\User;
use App\Services\DepositService;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
});

test('pulling a deposit item into disposal carries qty, value, currency, unit and condition', function () {
    $rec = app(DepositService::class)->createDraft([
        'request_type' => 'legacy', 'deposit_date' => now()->toDateString(),
        'functional_status' => 'unusable',
        'items' => [[
            'item_name' => 'Old motor', 'qty' => 5, 'unit' => 'ໜ່ວຍ',
            'estimated_value' => 1200.50, 'currency' => 'THB',
            'condition_on_deposit' => 'ໄໝ້ ຂົດ ລວດ',
        ]],
    ], auth()->user());
    // deposit must be in a pullable status
    $rec->update(['status' => 'stored']);
    $it = $rec->items()->first();

    $c = Livewire::test(Create::class)->call('pickAsset', 0, 'deposit', $it->id);

    expect($c->get('items.0.qty'))->toBe(5);
    expect((string) $c->get('items.0.estimated_value'))->toBe('1200.50');
    expect($c->get('items.0.currency'))->toBe('THB');
    expect($c->get('items.0.unit'))->toBe('ໜ່ວຍ');
    expect($c->get('items.0.condition'))->toBe('ໄໝ້ ຂົດ ລວດ');
    expect($c->get('items.0.functional_status'))->toBe('unusable');
});
