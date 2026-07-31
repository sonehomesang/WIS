<?php

use App\Livewire\Settings\Users;
use App\Models\User;
use App\Notifications\SetPasswordNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create(['is_super_admin' => true]);
});

test('creating a user sets no usable password and issues a set-password link', function () {
    Notification::fake();
    actingAs($this->admin);

    Livewire::test(Users::class)
        ->call('newUser')
        ->set('display_name', 'New Staff')
        ->set('email', 'newstaff@namtheun2.com')
        ->set('role', 'requester')
        ->set('status', 'active')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('setLinkEmail', 'newstaff@namtheun2.com')
        ->assertSeeHtml('reset-password'); // the copy-link box shows the reset URL

    $user = User::where('email', 'newstaff@namtheun2.com')->first();
    expect($user)->not->toBeNull();
    // admin never set a password; the random one is unguessable
    expect(Hash::check('password', $user->password))->toBeFalse();

    Notification::assertSentTo($user, SetPasswordNotification::class);
});

test('the resend button (linkFor) issues a fresh set-password link', function () {
    Notification::fake();
    $u = User::factory()->create(['email' => 'x@namtheun2.com']);
    $u->syncRoles(['requester']);

    actingAs($this->admin);
    Livewire::test(Users::class)
        ->call('linkFor', $u->id)
        ->assertSet('setLinkEmail', 'x@namtheun2.com')
        ->assertSeeHtml('reset-password');

    Notification::assertSentTo($u, SetPasswordNotification::class);
});

test('password policy requires at least 10 characters', function () {
    expect(Validator::make(['p' => 'short123'], ['p' => Password::defaults()])->fails())->toBeTrue();
    expect(Validator::make(['p' => 'longenough10'], ['p' => Password::defaults()])->fails())->toBeFalse();
});
