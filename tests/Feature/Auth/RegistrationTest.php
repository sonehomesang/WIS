<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Route;

test('self-registration is disabled', function () {
    // Accounts come from the AD/HR sync + admin import, not public signup.
    $this->get('/register')->assertNotFound();
    expect(Route::has('register'))->toBeFalse();
});
