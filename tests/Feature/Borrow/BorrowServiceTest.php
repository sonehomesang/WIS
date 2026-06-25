<?php

use App\Models\BorrowRecord;
use App\Models\InventoryItem;
use App\Models\Setting;
use App\Models\User;
use App\Services\BorrowService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->svc = new BorrowService;
    $this->actor = User::factory()->create(['is_super_admin' => true, 'display_name' => 'Test Admin']);
});

function draft(array $over = []): BorrowRecord
{
    return test()->svc->createDraft(array_merge([
        'borrow_type' => 'new_inventory',
        'purpose' => 'fix pump',
        'borrow_date' => now()->toDateString(),
        'period_days' => 7,
        'items' => [['item_name' => 'Drill', 'qty' => 2]],
    ], $over), test()->actor);
}

test('createDraft generates BR number, draft status, computed return date, history', function () {
    $r = draft();

    expect($r->request_number)->toMatch('/^BR\d{4}-0001$/');
    expect($r->status)->toBe('draft');
    expect($r->planned_return_date->toDateString())->toBe(now()->addDays(7)->toDateString());
    expect($r->items)->toHaveCount(1);
    expect($r->history()->where('action', 'create')->exists())->toBeTrue();
});

test('BR counter increments per year', function () {
    $a = draft();
    $b = draft();
    expect($a->request_number)->toEndWith('-0001');
    expect($b->request_number)->toEndWith('-0002');
});

test('default config (approve required, ack optional, requires_acknowledge=false): submit -> acknowledged -> approve -> approved', function () {
    $r = draft(['requires_acknowledge' => false]);

    $this->svc->transition($r, 'submit', $this->actor);
    expect($r->refresh()->status)->toBe('acknowledged');

    $this->svc->transition($r, 'approve', $this->actor);
    expect($r->refresh()->status)->toBe('approved');
});

test('acknowledge step active when requires_acknowledge=true: must acknowledge before approve', function () {
    $r = draft(['requires_acknowledge' => true]);
    $this->svc->transition($r, 'submit', $this->actor);

    expect(fn () => $this->svc->transition($r->refresh(), 'approve', $this->actor))
        ->toThrow(ValidationException::class);

    $this->svc->transition($r->refresh(), 'acknowledge', $this->actor);
    expect($r->refresh()->acknowledged_at)->not->toBeNull();

    $this->svc->transition($r->refresh(), 'approve', $this->actor);
    expect($r->refresh()->status)->toBe('approved');
});

test('admin can disable both steps: submit goes straight to approved', function () {
    Setting::put('workflow', ['borrow' => ['acknowledge' => 'off', 'approve' => 'off']], $this->actor->id);

    $r = draft();
    $this->svc->transition($r, 'submit', $this->actor);

    expect($r->refresh()->status)->toBe('approved');
});

test('confirmTake decrements inventory, confirmReturn increments back', function () {
    $inv = InventoryItem::create(['slug' => 'drill-x', 'name' => 'Drill X', 'quantity' => 10]);
    $r = draft(['items' => [['item_id' => $inv->id, 'item_name' => 'Drill X', 'qty' => 3]]]);

    // walk to approved
    $this->svc->transition($r, 'submit', $this->actor);
    $this->svc->transition($r->refresh(), 'approve', $this->actor);

    $this->svc->transition($r->refresh(), 'confirmTake', $this->actor);
    expect($r->refresh()->status)->toBe('active');
    expect($inv->refresh()->quantity)->toBe(7);

    $this->svc->transition($r->refresh(), 'confirmReturn', $this->actor);
    expect($r->refresh()->status)->toBe('returned');
    expect($inv->refresh()->quantity)->toBe(10);
});

test('confirmTake is blocked when stock is insufficient (no negative inventory)', function () {
    $inv = InventoryItem::create(['slug' => 'pump-y', 'name' => 'Pump Y', 'quantity' => 2]);
    $r = draft(['items' => [['item_id' => $inv->id, 'item_name' => 'Pump Y', 'qty' => 5]]]);
    $this->svc->transition($r, 'submit', $this->actor);
    $this->svc->transition($r->refresh(), 'approve', $this->actor);

    expect(fn () => $this->svc->transition($r->refresh(), 'confirmTake', $this->actor))
        ->toThrow(ValidationException::class);

    expect($inv->refresh()->quantity)->toBe(2);          // unchanged, not negative
    expect($r->refresh()->status)->toBe('approved');     // transition rolled back
});

function activeRecord(): BorrowRecord
{
    $svc = test()->svc;
    $a = test()->actor;
    $r = draft(['requires_acknowledge' => false]);
    $svc->transition($r, 'submit', $a);
    $svc->transition($r->refresh(), 'approve', $a);
    $svc->transition($r->refresh(), 'confirmTake', $a);

    return $r->refresh();
}

test('extension: request → pending, approve → planned_return_date updated', function () {
    $r = activeRecord();
    $newDate = now()->addDays(20)->toDateString();

    $this->svc->transition($r, 'requestExtension', $this->actor, ['reason' => 'need more time', 'proposed_date' => $newDate]);
    expect($r->refresh()->extension_status)->toBe('pending');

    $this->svc->transition($r->refresh(), 'approveExtension', $this->actor);
    $r->refresh();
    expect($r->extension_status)->toBe('approved');
    expect($r->planned_return_date->toDateString())->toBe($newDate);
});

test('extension: reject keeps planned_return_date', function () {
    $r = activeRecord();
    $orig = $r->planned_return_date->toDateString();

    $this->svc->transition($r, 'requestExtension', $this->actor, ['proposed_date' => now()->addDays(20)->toDateString()]);
    $this->svc->transition($r->refresh(), 'rejectExtension', $this->actor);
    $r->refresh();
    expect($r->extension_status)->toBe('rejected');
    expect($r->planned_return_date->toDateString())->toBe($orig);
});

test('extension: cannot approve when none pending', function () {
    $r = activeRecord();
    expect(fn () => $this->svc->transition($r, 'approveExtension', $this->actor))
        ->toThrow(ValidationException::class);
});

test('cancel from draft sets cancelled', function () {
    $r = draft();
    $this->svc->transition($r, 'cancel', $this->actor, ['reason' => 'no need']);
    expect($r->refresh()->status)->toBe('cancelled');
    expect($r->cancel_reason)->toBe('no need');
});
