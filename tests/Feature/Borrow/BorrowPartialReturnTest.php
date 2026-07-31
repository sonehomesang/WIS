<?php

use App\Livewire\Borrow\Show;
use App\Models\BorrowRecord;
use App\Models\InventoryItem;
use App\Models\Setting;
use App\Models\User;
use App\Services\BorrowService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

/** draft → active ດ້ວຍ items ທີ່ ໃຫ້. */
function makeActiveBorrow(User $actor, array $items): BorrowRecord
{
    $svc = app(BorrowService::class);
    $r = $svc->createDraft([
        'borrow_type' => 'new_inventory', 'borrow_date' => now()->toDateString(),
        'period_days' => 5, 'items' => $items,
    ], $actor);
    $svc->transition($r, 'submit', $actor);
    $svc->transition($r, 'approve', $actor);
    $svc->transition($r, 'confirmTake', $actor);

    return $r->refresh();
}

function enablePartial(): void
{
    Setting::put('workflow', ['borrow' => ['partial_return' => 'on']]);
}

test('receiveReturn is blocked when the partial-return toggle is off (default)', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    actingAs($admin);
    $r = makeActiveBorrow($admin, [['item_name' => 'Drill', 'qty' => 5]]);

    expect(fn () => app(BorrowService::class)->transition($r, 'receiveReturn', $admin, [
        'receive' => [$r->items->first()->id => 2],
    ]))->toThrow(ValidationException::class);

    expect($r->refresh()->status)->toBe('active');
});

test('progressive receive keeps the slip active until fully returned, then closes it', function () {
    enablePartial();
    $admin = User::factory()->create(['is_super_admin' => true, 'display_name' => 'WH']);
    actingAs($admin);
    $r = makeActiveBorrow($admin, [['item_name' => 'Drill', 'qty' => 5]]);
    $itemId = $r->items->first()->id;
    $svc = app(BorrowService::class);

    // ຄັ້ງ 1: ຮັບ 2 ← ຍັງ active, ຄ້າງ 3
    $svc->transition($r, 'receiveReturn', $admin, ['receive' => [$itemId => 2]]);
    $r->refresh()->load('items', 'returnEvents.lines');
    expect($r->status)->toBe('active')
        ->and($r->items->first()->return_qty)->toBe(2)
        ->and($r->outstanding_qty)->toBe(3)
        ->and($r->is_partially_returned)->toBeTrue()
        ->and($r->returnEvents)->toHaveCount(1)
        ->and($r->returnEvents->first()->seq)->toBe(1)
        ->and($r->returnEvents->first()->lines->first()->qty)->toBe(2);

    // ຄັ້ງ 2: ຮັບ 3 ← ຄົບ → returned
    $svc->transition($r, 'receiveReturn', $admin, ['receive' => [$itemId => 3]]);
    $r->refresh()->load('items', 'returnEvents');
    expect($r->status)->toBe('returned')
        ->and($r->items->first()->return_qty)->toBe(5)
        ->and($r->outstanding_qty)->toBe(0)
        ->and($r->wh_return_ack)->toBeTrue()
        ->and($r->returnEvents)->toHaveCount(2)
        ->and($r->returnEvents->last()->seq)->toBe(2);
});

test('multi-item slip closes only when every line is fully received', function () {
    enablePartial();
    $admin = User::factory()->create(['is_super_admin' => true]);
    actingAs($admin);
    $r = makeActiveBorrow($admin, [
        ['item_name' => 'A', 'qty' => 2],
        ['item_name' => 'B', 'qty' => 3],
    ]);
    $a = $r->items->firstWhere('item_name', 'A')->id;
    $b = $r->items->firstWhere('item_name', 'B')->id;
    $svc = app(BorrowService::class);

    // ຄັ້ງ 1: A ຄົບ 2, B ພຽງ 1 → ຍັງ active (ຄ້າງ B 2)
    $svc->transition($r, 'receiveReturn', $admin, ['receive' => [$a => 2, $b => 1]]);
    $r->refresh()->load('items');
    expect($r->status)->toBe('active')
        ->and($r->outstanding_qty)->toBe(2);

    // ຄັ້ງ 2: B ອີກ 2 → ຄົບ → returned
    $svc->transition($r, 'receiveReturn', $admin, ['receive' => [$b => 2]]);
    $r->refresh();
    expect($r->status)->toBe('returned')
        ->and($r->outstanding_qty)->toBe(0);
});

test('received qty is clamped to what is still outstanding', function () {
    enablePartial();
    $admin = User::factory()->create(['is_super_admin' => true]);
    actingAs($admin);
    $r = makeActiveBorrow($admin, [['item_name' => 'Drill', 'qty' => 3]]);
    $itemId = $r->items->first()->id;

    // ຮັບ 10 ໃສ່ ຂໍ້ ທີ່ ຢືມ 3 → clamp = 3
    app(BorrowService::class)->transition($r, 'receiveReturn', $admin, ['receive' => [$itemId => 10]]);
    $r->refresh()->load('items');
    expect($r->items->first()->return_qty)->toBe(3)
        ->and($r->status)->toBe('returned');
});

test('each partial receive returns the received qty to inventory stock', function () {
    enablePartial();
    $admin = User::factory()->create(['is_super_admin' => true]);
    actingAs($admin);
    $inv = InventoryItem::create(['slug' => 'drill-x', 'name' => 'Drill', 'quantity' => 10]);
    $r = makeActiveBorrow($admin, [['item_id' => $inv->id, 'item_name' => 'Drill', 'qty' => 5]]);
    expect($inv->refresh()->quantity)->toBe(5);   // confirmTake ໄດ້ ຫັກ 5

    $itemId = $r->items->first()->id;
    $svc = app(BorrowService::class);
    $svc->transition($r, 'receiveReturn', $admin, ['receive' => [$itemId => 2]]);
    expect($inv->refresh()->quantity)->toBe(7);   // ຄືນ 2

    $svc->transition($r, 'receiveReturn', $admin, ['receive' => [$itemId => 3]]);
    expect($inv->refresh()->quantity)->toBe(10);  // ຄືນ ຄົບ
});

test('the receive modal records an event with photos linked to that event', function () {
    Storage::fake('public');
    enablePartial();
    $admin = User::factory()->create(['is_super_admin' => true]);
    actingAs($admin);
    $r = makeActiveBorrow($admin, [['item_name' => 'Drill', 'qty' => 5]]);
    $itemId = $r->items->first()->id;

    Livewire::test(Show::class, ['record' => $r])
        ->call('openReceive')
        ->set("receiveQty.{$itemId}", 2)
        ->set("receivePhotos.{$itemId}", [UploadedFile::fake()->image('ret.jpg')])
        ->call('receiveReturn')
        ->assertHasNoErrors();

    $r->refresh()->load('returnEvents.photos', 'items');
    expect($r->status)->toBe('active')
        ->and($r->items->first()->return_qty)->toBe(2)
        ->and($r->returnEvents)->toHaveCount(1)
        ->and($r->returnEvents->first()->photos)->toHaveCount(1);
    $eventId = $r->returnEvents->first()->id;
    $photo = $r->returnEvents->first()->photos->first();
    expect($photo->return_event_id)->toBe($eventId)
        ->and($photo->path)->toContain('bw_')     // ຜ່ານ stampAndStore (ຝັງ ວັນ+ເວລາ)
        ->and($photo->path)->toEndWith('.jpg');
    Storage::disk('public')->assertExists($photo->path);
});

test('receiveReturn requires a photo for each item received in the event', function () {
    Storage::fake('public');
    enablePartial();
    $admin = User::factory()->create(['is_super_admin' => true]);
    actingAs($admin);
    $r = makeActiveBorrow($admin, [['item_name' => 'Drill', 'qty' => 5]]);
    $itemId = $r->items->first()->id;

    Livewire::test(Show::class, ['record' => $r])
        ->call('openReceive')
        ->set("receiveQty.{$itemId}", 2)   // ບໍ່ ໃສ່ ຮູບ
        ->call('receiveReturn')
        ->assertHasErrors('receivePhotos');

    expect($r->refresh()->status)->toBe('active');
    expect(BorrowRecord::find($r->id)->returnEvents()->count())->toBe(0);
});
