<?php

use App\Livewire\Settings\Users;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use App\Support\SecuritySettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

/** Turn the "require email verification" policy on (and clear the cache). */
function requireVerification(bool $on = true): void
{
    Setting::put('security', ['require_email_verification' => $on, 'idle_timeout_minutes' => 0], null);
    SecuritySettings::forget();
}

test('with the policy OFF, an unverified local user reaches the app', function () {
    $user = User::factory()->unverified()->create(['auth_provider' => 'password']);

    $this->actingAs($user)->get('/dashboard')->assertOk();
});

test('with the policy ON, an unverified local user is bounced to verify-email', function () {
    requireVerification();
    $user = User::factory()->unverified()->create(['auth_provider' => 'password']);

    $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
});

test('a verified local user passes even with the policy ON', function () {
    requireVerification();
    $user = User::factory()->create(['auth_provider' => 'password']);   // factory = verified

    $this->actingAs($user)->get('/dashboard')->assertOk();
});

test('a domain (AD) user is exempt — verified through AD, not email', function () {
    requireVerification();
    $user = User::factory()->unverified()->create(['auth_provider' => 'domain']);

    $this->actingAs($user)->get('/dashboard')->assertOk();
});

test('a super admin is exempt so break-glass is never locked out', function () {
    requireVerification();
    $user = User::factory()->unverified()->create(['auth_provider' => 'password', 'is_super_admin' => true]);

    $this->actingAs($user)->get('/dashboard')->assertOk();
});

test('the verify-email page itself is reachable while unverified (no redirect loop)', function () {
    requireVerification();
    $user = User::factory()->unverified()->create(['auth_provider' => 'password']);

    $this->actingAs($user)->get(route('verification.notice'))->assertOk();
});

test('approving a pending local user sends a verification email when the policy is ON', function () {
    $this->seed(RolePermissionSeeder::class);
    Notification::fake();
    requireVerification();

    $admin = User::factory()->create(['is_super_admin' => true]);
    $target = User::factory()->unverified()->create(['auth_provider' => 'password', 'status' => 'pending']);

    Livewire::actingAs($admin)->test(Users::class)->call('approve', $target->id);

    expect($target->fresh()->status)->toBe('active');
    Notification::assertSentTo($target, VerifyEmailNotification::class);
});

test('approving a domain user never sends an email verification', function () {
    $this->seed(RolePermissionSeeder::class);
    Notification::fake();
    requireVerification();

    $admin = User::factory()->create(['is_super_admin' => true]);
    $target = User::factory()->unverified()->create(['auth_provider' => 'domain', 'status' => 'pending']);

    Livewire::actingAs($admin)->test(Users::class)->call('approve', $target->id);

    Notification::assertNothingSentTo($target);
});

test('the POST logout route logs the user out and flags an idle exit', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'), ['reason' => 'idle'])
        ->assertRedirect(route('login', ['idle' => 1]));

    $this->assertGuest();
});
