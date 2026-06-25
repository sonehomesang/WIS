<?php

namespace Tests\Feature\Auth;

use App\Models\User;

test('users:verify-precreated marks unverified accounts as verified', function () {
    $unverified = User::factory()->unverified()->create();
    expect($unverified->email_verified_at)->toBeNull();

    $this->artisan('users:verify-precreated')->assertSuccessful();

    expect($unverified->refresh()->email_verified_at)->not->toBeNull();
});
