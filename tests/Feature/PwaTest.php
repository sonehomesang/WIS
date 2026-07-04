<?php

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('manifest is public, well-formed, and reflects the app name', function () {
    Setting::put('general', ['app_name' => 'WH ສາງ ນ້ຳເທີນ 2']);

    $res = $this->get('/manifest.webmanifest');
    $res->assertOk();
    expect($res->headers->get('Content-Type'))->toContain('application/manifest+json');

    $json = $res->json();
    expect($json['name'])->toBe('WH ສາງ ນ້ຳເທີນ 2');
    expect($json['display'])->toBe('standalone');
    expect($json['start_url'])->toBe('/dashboard');
    expect($json['icons'])->toHaveCount(3);
});

test('pages link the manifest + (testing) auto-clear any stale service worker', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    // ໄລຍະ ທົດສອບ: SW register ຖືກ ປິດ, ແທນ ດ້ວຍ getRegistrations()→unregister (ກັນ cache ເກົ່າ).
    // ກ່ອນ ຂຶ້ນ production ໃຫ້ ເປີດ register('/sw.js') ຄືນ ໃນ partials/_pwa-head.blade.php.
    $this->get('/dashboard')->assertOk()
        ->assertSee('manifest.webmanifest', false)
        ->assertSee('serviceWorker.getRegistrations', false)
        ->assertSee('apple-touch-icon', false)
        ->assertSee('window.updateApp()', false);   // update-app button by the bell
});

test('login page is also installable', function () {
    $this->get('/login')->assertOk()->assertSee('manifest.webmanifest', false);
});

test('service worker + offline shell + icons exist', function () {
    expect(file_exists(public_path('sw.js')))->toBeTrue();
    expect(file_exists(public_path('offline.html')))->toBeTrue();
    expect(file_exists(public_path('icons/icon-512.png')))->toBeTrue();
    expect(file_exists(public_path('icons/maskable-512.png')))->toBeTrue();
});
