<?php

use App\Livewire\Borrow\Show;
use App\Models\BorrowItemPhoto;
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

test('removePhoto cannot delete a photo of another borrow record (audit M1 / IDOR)', function () {
    $inv = InventoryItem::create(['slug' => 'idor', 'name' => 'Drill', 'quantity' => 20]);

    $a = approvedRecord($inv);
    $aItem = $a->items->first()->id;
    Livewire::test(Show::class, ['record' => $a])->call('openTake')
        ->set('takePhotos.'.$aItem, [UploadedFile::fake()->image('a.jpg')])->call('confirmTake');

    $b = approvedRecord($inv);
    $bItem = $b->items->first()->id;
    Livewire::test(Show::class, ['record' => $b])->call('openTake')
        ->set('takePhotos.'.$bItem, [UploadedFile::fake()->image('b.jpg')])->call('confirmTake');

    $victim = $b->refresh()->items->first()->photos()->first();
    expect($victim)->not->toBeNull();

    // ຢູ່ ໜ້າ record A ແລ້ວ ພະຍາຍາມ ລຶບ ຮູບ ຂອງ record B ຜ່ານ id → ຕ້ອງ ບໍ່ ມີ ຜົນ
    Livewire::test(Show::class, ['record' => $a])->call('removePhoto', $victim->id);

    expect(BorrowItemPhoto::find($victim->id))->not->toBeNull();
});

test('return qty is clamped to the borrowed qty — no stock over-increment (audit)', function () {
    $inv = InventoryItem::create(['slug' => 'clamp', 'name' => 'Drill', 'quantity' => 5]);
    $r = approvedRecord($inv);   // ຢືມ qty 2
    $itemId = $r->items->first()->id;

    Livewire::test(Show::class, ['record' => $r])->call('openTake')
        ->set('takePhotos.'.$itemId, [UploadedFile::fake()->image('t.jpg')])->call('confirmTake');
    expect($inv->refresh()->quantity)->toBe(3);   // 5 - 2
    $r->refresh();

    // ພະຍາຍາມ ຄືນ 100 ຕໍ່ ຢືມ 2 → clamp ເປັນ 2 → stock ກັບ ເປັນ 5 (ບໍ່ ແມ່ນ 103)
    Livewire::test(Show::class, ['record' => $r])
        ->call('openReturn')
        ->set('returnPhotos.'.$itemId, [UploadedFile::fake()->image('r.jpg')])
        ->set('returnQty.'.$itemId, 100)
        ->call('confirmReturn')
        ->assertHasNoErrors();

    expect($inv->refresh()->quantity)->toBe(5);
    expect($r->refresh()->items->first()->return_qty)->toBe(2);
});
