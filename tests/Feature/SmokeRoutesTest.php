<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

/**
 * Visit every GET page as a super_admin and assert it does not 500.
 * Catches broken blades / undefined vars / missing route refs across the app.
 * Record-bound routes 404 on the empty test DB — that's fine (not a fatal).
 */
test('every GET route renders without a server error', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    $skip = ['logout', 'password.confirm', 'verification.verify'];
    $failures = [];

    foreach (Route::getRoutes() as $route) {
        if (! in_array('GET', $route->methods(), true)) {
            continue;
        }
        $name = $route->getName();
        if ($name && in_array($name, $skip, true)) {
            continue;
        }
        $uri = $route->uri();
        if (str_contains($uri, '{') && ! preg_match('/\{[a-zA-Z_]+\??\}/', $uri)) {
            continue;
        }
        // substitute any route params with "1"
        $url = preg_replace('/\{[^}]+\}/', '1', $uri);
        if (str_starts_with($url, '_') || str_contains($url, 'sanctum') || $url === 'up') {
            continue;
        }

        $status = $this->get('/'.ltrim($url, '/'))->getStatusCode();
        if ($status >= 500) {
            $failures[] = "{$uri} → {$status}";
        }
    }

    expect($failures)->toBe([]);
});
