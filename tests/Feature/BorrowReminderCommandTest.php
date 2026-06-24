<?php

use App\Models\BorrowRecord;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Console\Scheduling\Schedule;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function anOverdueBorrow(User $borrower, string $n): BorrowRecord
{
    return BorrowRecord::create([
        'request_number' => $n, 'borrower_user_id' => $borrower->id,
        'borrower_email' => $borrower->email, 'borrower_name' => 'B', 'borrow_type' => 'new_inventory',
        'borrow_date' => now()->subDays(10)->toDateString(), 'period_days' => 5,
        'planned_return_date' => now()->subDays(2)->toDateString(), 'status' => 'active',
    ]);
}

test('borrow:remind sends one reminder per overdue record and is idempotent per day', function () {
    $b = User::factory()->create();
    anOverdueBorrow($b, 'BR'.now()->year.'-RC01');

    $this->artisan('borrow:remind')->assertSuccessful();
    expect(Notification::where('user_id', $b->id)->where('type', 'warning')->count())->toBe(1);

    // run again same day → no duplicate
    $this->artisan('borrow:remind')->assertSuccessful();
    expect(Notification::where('user_id', $b->id)->count())->toBe(1);
});

test('borrow:remind respects the borrow_reminder flag', function () {
    Setting::put('notifications', ['enabled' => true, 'borrow_reminder' => false]);
    $b = User::factory()->create();
    anOverdueBorrow($b, 'BR'.now()->year.'-RC02');

    $this->artisan('borrow:remind')->assertSuccessful();
    expect(Notification::count())->toBe(0);
});

test('the reminder command is scheduled daily', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($e) => str_contains($e->command ?? '', 'borrow:remind'));
    expect($events)->not->toBeEmpty();
});
