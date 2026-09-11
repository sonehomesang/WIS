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

test('activating a user notifies every super admin, including the actor', function () {
    $this->seed(RolePermissionSeeder::class);
    $actor = User::factory()->create(['is_super_admin' => true, 'display_name' => 'Admin A']);
    $other = User::factory()->create(['is_super_admin' => true, 'status' => 'active']);
    $target = User::factory()->create(['status' => 'pending', 'display_name' => 'User B']);

    Livewire::actingAs($actor)->test(Users::class)->call('approve', $target->id);

    foreach ([$actor, $other] as $admin) {
        $n = Notification::where('user_id', $admin->id)->latest('id')->first();
        expect($n)->not->toBeNull()
            ->and($n->message)->toContain('Admin A')     // who activated
            ->and($n->message)->toContain('User B');      // whom
    }
});

test('activating through the edit form also logs activate and notifies', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create(['is_super_admin' => true, 'display_name' => 'Admin A']);
    $target = User::factory()->create(['status' => 'pending', 'display_name' => 'User B', 'email' => 'userb@example.com']);

    Livewire::actingAs($admin)->test(Users::class)
        ->call('editUser', $target->id)
        ->set('role', 'requester')
        ->set('status', 'active')
        ->call('save')
        ->assertHasNoErrors();

    expect($target->fresh()->status)->toBe('active')
        ->and(UserHistory::where('record_id', $target->id)->where('action', 'activate')->exists())->toBeTrue()
        ->and(Notification::where('user_id', $admin->id)->where('message', 'like', '%User B%')->exists())->toBeTrue();
});

test('locking and unlocking a user are both recorded and notified', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create(['is_super_admin' => true]);
    $target = User::factory()->create(['status' => 'active']);

    Livewire::actingAs($admin)->test(Users::class)->call('toggleLock', $target->id);
    expect(UserHistory::where('record_id', $target->id)->where('action', 'lock')->exists())->toBeTrue()
        ->and(Notification::where('user_id', $admin->id)->where('type', 'warning')->exists())->toBeTrue();

    Livewire::actingAs($admin)->test(Users::class)->call('toggleLock', $target->id);
    expect(UserHistory::where('record_id', $target->id)->where('action', 'unlock')->exists())->toBeTrue();
});

test('a plain edit is recorded as update and notified', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create(['is_super_admin' => true, 'display_name' => 'Admin A']);
    $target = User::factory()->create(['status' => 'active', 'display_name' => 'User B']);

    Livewire::actingAs($admin)->test(Users::class)
        ->call('editUser', $target->id)
        ->set('role', 'requester')
        ->set('status', 'active')       // unchanged status → plain update
        ->call('save')
        ->assertHasNoErrors();

    expect(UserHistory::where('record_id', $target->id)->where('action', 'update')->exists())->toBeTrue()
        ->and(Notification::where('user_id', $admin->id)->where('message', 'like', '%User B%')->exists())->toBeTrue();
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
