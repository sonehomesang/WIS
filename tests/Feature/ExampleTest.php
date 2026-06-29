<?php

it('redirects guests from / to the login page', function () {
    $this->get('/')->assertRedirect(route('login'));
});

it('serves the health check', function () {
    $this->get('/up')->assertOk();
});
