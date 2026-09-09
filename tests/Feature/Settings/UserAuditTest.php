<?php

use App\Livewire\Settings\Audit;
use App\Livewire\Settings\Users;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserHistory;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

test('approving a user records who activated it and when', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create(['is_super_admin' => true, 'display_name' => 'Admin A']);
    $target = User::factory()->create(['status' => 'pending', 'username' => 'userb', 'display_name' => 'User B']);

    Livewire::actingAs($admin)->test(Users::class)->call('approve', $target->id);

    $h = UserHistory::where('record_id', $target->id)->where('action', 'activate')->first();
    expect($h)->not->toBeNull()
        ->and($h->user_name)->toBe('Admin A')          // which admin
        ->and($h->status)->toBe('active')
        ->and($h->created_at)->not->toBeNull()          // when
        ->and($target->fresh()->status)->toBe('active');
});

test('activating a user notifies the OTHER super admins, not the actor', function () {
    $this->seed(RolePermissionSeeder::class);
    $actor = User::factory()->create(['is_super_admin' => true, 'display_name' => 'Admin A']);
    $other = User::factory()->create(['is_super_admin' => true, 'status' => 'active']);
    $target = User::factory()->create(['status' => 'pending', 'display_name' => 'User B']);

    Livewire::actingAs($actor)->test(Users::class)->call('approve', $target->id);

    $n = Notification::where('user_id', $other->id)->latest('id')->first();
    expect($n)->not->toBeNull()
        ->and($n->message)->toContain('Admin A')       // who
        ->and($n->message)->toContain('User B');        // whom
    expect(Notification::where('user_id', $actor->id)->exists())->toBeFalse();   // no self-notify
});

test('locking and unlocking a user are both recorded', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create(['is_super_admin' => true]);
    $target = User::factory()->create(['status' => 'active']);

    Livewire::actingAs($admin)->test(Users::class)->call('toggleLock', $target->id);
    expect(UserHistory::where('record_id', $target->id)->where('action', 'lock')->exists())->toBeTrue();

    Livewire::actingAs($admin)->test(Users::class)->call('toggleLock', $target->id);
    expect(UserHistory::where('record_id', $target->id)->where('action', 'unlock')->exists())->toBeTrue();
});

test('the audit log page surfaces user account actions', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create(['is_super_admin' => true, 'display_name' => 'Admin A']);
    $target = User::factory()->create(['status' => 'pending', 'username' => 'userb']);

    Livewire::actingAs($admin)->test(Users::class)->call('approve', $target->id);

    Livewire::actingAs($admin)->test(Audit::class)
        ->assertSee('activate')
        ->assertSee('Admin A');
});
