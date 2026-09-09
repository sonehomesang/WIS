<?php

use App\Models\User;

test('the POST logout route logs the user out and flags an idle exit', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'), ['reason' => 'idle'])
        ->assertRedirect(route('login', ['idle' => 1]));

    $this->assertGuest();
});

test('the POST logout route redirects plainly without the idle flag', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
