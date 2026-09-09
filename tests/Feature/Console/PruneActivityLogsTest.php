<?php

use App\Models\Notification;
use App\Models\User;
use App\Models\UserHistory;

test('logs:prune deletes history and notifications older than the retention window', function () {
    $u = User::factory()->create();

    UserHistory::create(['record_id' => $u->id, 'action' => 'update', 'status' => 'active', 'created_at' => now()->subDays(90)]);
    UserHistory::create(['record_id' => $u->id, 'action' => 'update', 'status' => 'active', 'created_at' => now()->subDays(10)]);
    Notification::create(['user_id' => $u->id, 'type' => 'info', 'title' => 'old', 'created_at' => now()->subDays(90)]);
    Notification::create(['user_id' => $u->id, 'type' => 'info', 'title' => 'new', 'created_at' => now()->subDays(5)]);

    $this->artisan('logs:prune')->assertSuccessful();   // default 60 days

    expect(UserHistory::count())->toBe(1)
        ->and(Notification::count())->toBe(1)
        ->and(Notification::first()->title)->toBe('new');
});

test('logs:prune honours a custom retention window', function () {
    $u = User::factory()->create();
    UserHistory::create(['record_id' => $u->id, 'action' => 'update', 'status' => 'active', 'created_at' => now()->subDays(20)]);

    $this->artisan('logs:prune', ['--days' => 10])->assertSuccessful();

    expect(UserHistory::count())->toBe(0);   // 20 days old > 10-day window
});
