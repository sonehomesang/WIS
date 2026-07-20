<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('a user with a temporary password is forced to the change-password page', function () {
    $user = User::factory()->create([
        'status' => 'active', 'email_verified_at' => now(), 'must_change_password' => true,
    ]);

    actingAs($user);
    get(route('dashboard'))->assertRedirect(route('password.force'));
});

test('a normal user is not redirected', function () {
    $user = User::factory()->create([
        'status' => 'active', 'email_verified_at' => now(), 'must_change_password' => false,
    ]);

    actingAs($user);
    get(route('dashboard'))->assertOk();
});

test('setting a new password clears the flag and lets the user in', function () {
    $user = User::factory()->create([
        'status' => 'active', 'email_verified_at' => now(), 'must_change_password' => true,
    ]);

    actingAs($user);

    Volt::test('pages.auth.force-password')
        ->set('password', 'NewStrongPass1!')
        ->set('password_confirmation', 'NewStrongPass1!')
        ->call('updatePassword')
        ->assertHasNoErrors();

    $fresh = $user->fresh();
    expect($fresh->must_change_password)->toBeFalse();
    expect(Hash::check('NewStrongPass1!', $fresh->password))->toBeTrue();
    // ຫຼັງ ປ່ຽນ → ເຂົ້າ dashboard ໄດ້ ແລ້ວ
    get(route('dashboard'))->assertOk();
});

test('the temp-password command targets domain users and skips super admins', function () {
    $domain = User::factory()->create(['auth_provider' => 'domain', 'is_super_admin' => false, 'must_change_password' => false]);
    $super = User::factory()->create(['auth_provider' => 'password', 'is_super_admin' => true, 'must_change_password' => false]);
    $pwUser = User::factory()->create(['auth_provider' => 'password', 'is_super_admin' => false, 'must_change_password' => false]);

    $this->artisan('users:temp-password', ['password' => 'Wh@Temp2026'])->assertSuccessful();

    expect($domain->fresh()->must_change_password)->toBeTrue();
    expect(Hash::check('Wh@Temp2026', $domain->fresh()->password))->toBeTrue();
    expect($super->fresh()->must_change_password)->toBeFalse();   // super admin ບໍ່ ຖືກ ແຕະ
    expect($pwUser->fresh()->must_change_password)->toBeFalse();   // ບໍ່ ແມ່ນ domain → ບໍ່ ຖືກ ແຕະ (ບໍ່ ໃສ່ --all)
});
