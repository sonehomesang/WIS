<?php

test('security headers are present on responses', function () {
    $res = $this->get('/login');

    $res->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $res->assertHeader('X-Content-Type-Options', 'nosniff');
    $res->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $res->assertHeader('Permissions-Policy', 'geolocation=(), microphone=()');
    expect($res->headers->get('Content-Security-Policy-Report-Only'))->toContain("default-src 'self'");
});
