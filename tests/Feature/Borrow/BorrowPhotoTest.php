<?php

use App\Livewire\Borrow\Show;
use App\Models\InventoryItem;
use App\Models\User;
use App\Services\BorrowService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actor = User::factory()->create(['is_super_admin' => true, 'display_name' => 'WH']);
    $this->actingAs($this->actor);
    Storage::fake('public');
    $this->svc = app(BorrowService::class);
});

function approvedRecord(InventoryItem $inv)
{
    $svc = test()->svc;
    $actor = test()->actor;
    $r = $svc->createDraft([
        'borrow_type' => 'new_inventory',
        'borrow_date' => now()->toDateString(),
        'period_days' => 7,
        'items' => [['item_id' => $inv->id, 'item_name' => $inv->name, 'qty' => 2]],
    ], $actor);
    $svc->transition($r, 'submit', $actor);
    $svc->transition($r->refresh(), 'approve', $actor);

    return $r->refresh();
}

test('confirmTake requires a photo per item', function () {
    $r = approvedRecord(InventoryItem::create(['slug' => 'd1', 'name' => 'Drill', 'quantity' => 5]));

    Livewire::test(Show::class, ['record' => $r])->call('openTake')->call('confirmTake')->assertHasErrors('takePhotos');
    expect($r->refresh()->status)->toBe('approved');
});

test('confirmTake stores photo + condition, activates, decrements inventory', function () {
    $inv = InventoryItem::create(['slug' => 'd2', 'name' => 'Drill', 'quantity' => 5]);
    $r = approvedRecord($inv);
    $itemId = $r->items->first()->id;

    Livewire::test(Show::class, ['record' => $r])
        ->call('openTake')
        ->set('takePhotos.'.$itemId, [UploadedFile::fake()->image('t.jpg')])
        ->set('takeCondition.'.$itemId, 'good')
        ->call('confirmTake')
        ->assertHasNoErrors();

    $r->refresh();
    expect($r->status)->toBe('active');
    expect($r->items->first()->photos()->where('kind', 'take')->count())->toBe(1);
    expect($r->items->first()->condition_on_take)->toBe('good');
    expect($inv->refresh()->quantity)->toBe(3);
});

test('confirmReturn stores return photo + qty, returns, restores inventory', function () {
    $inv = InventoryItem::create(['slug' => 'd3', 'name' => 'Drill', 'quantity' => 5]);
    $r = approvedRecord($inv);
    $itemId = $r->items->first()->id;

    Livewire::test(Show::class, ['record' => $r])->call('openTake')
        ->set('takePhotos.'.$itemId, [UploadedFile::fake()->image('t.jpg')])->call('confirmTake');
    $r->refresh();

    Livewire::test(Show::class, ['record' => $r])
        ->call('openReturn')
        ->set('returnPhotos.'.$itemId, [UploadedFile::fake()->image('r.jpg')])
        ->set('returnQty.'.$itemId, 2)
        ->call('confirmReturn')
        ->assertHasNoErrors();

    $r->refresh();
    expect($r->status)->toBe('returned');
    expect($r->items->first()->return_qty)->toBe(2);
    expect($r->items->first()->photos()->where('kind', 'return')->count())->toBe(1);
    expect($inv->refresh()->quantity)->toBe(5);
});
