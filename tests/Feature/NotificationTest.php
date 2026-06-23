<?php

use App\Livewire\Notifications\Bell;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\RequestService;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('NotificationService creates an unread notification', function () {
    $u = User::factory()->create();
    app(NotificationService::class)->notify($u->id, 'info', 'Hi', 'body', '/x');

    $n = Notification::first();
    expect($n->user_id)->toBe($u->id);
    expect($n->read_at)->toBeNull();
    expect(Notification::where('user_id', $u->id)->unread()->count())->toBe(1);
});

test('submitting a request notifies the approver', function () {
    $requester = User::factory()->create(['is_super_admin' => true]);
    $approver = User::factory()->create();
    $this->actingAs($requester);

    $r = app(RequestService::class)->createDraft([
        'purpose' => 'x', 'currency' => 'THB', 'approver_user_id' => $approver->id,
        'items' => [['description' => 'Bolt', 'quantity' => 1, 'unit_price' => 5]],
    ], $requester);
    app(RequestService::class)->transition($r, 'submit', $requester);

    expect(Notification::where('user_id', $approver->id)->where('title', 'like', '%ລໍ approve%')->exists())->toBeTrue();
});

test('bell marks all read', function () {
    $u = User::factory()->create();
    $this->actingAs($u);
    app(NotificationService::class)->notify($u->id, 'info', 'a');
    app(NotificationService::class)->notify($u->id, 'info', 'b');

    Livewire::test(Bell::class)->assertSee('a')->call('markAllRead');
    expect(Notification::where('user_id', $u->id)->unread()->count())->toBe(0);
});

test('marking a notification read redirects to its link', function () {
    $u = User::factory()->create();
    $this->actingAs($u);
    app(NotificationService::class)->notify($u->id, 'info', 'go', null, '/inventory');
    $id = Notification::first()->id;

    Livewire::test(Bell::class)->call('markRead', $id)->assertRedirect('/inventory');
    expect(Notification::find($id)->read_at)->not->toBeNull();
});
