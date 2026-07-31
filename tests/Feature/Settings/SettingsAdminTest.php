<?php

use App\Livewire\Settings\Audit;
use App\Livewire\Settings\NotificationLog;
use App\Livewire\Settings\Notifications;
use App\Livewire\Settings\Reports;
use App\Livewire\Settings\System;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\User;
use App\Services\NotificationService;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($this->admin);
});

test('notification settings save flags + templates', function () {
    Livewire::test(Notifications::class)
        ->set('enabled', false)
        ->set('borrowReminder', false)
        ->call('saveFlags');

    expect(Setting::get('notifications')['enabled'])->toBeFalse();

    // index 1 = 'request.approve' (order of NotificationService::TEMPLATES)
    Livewire::test(Notifications::class)
        ->assertSet('templates.1.key', 'request.approve')
        ->set('templates.1.lo.title', 'ໃບ {number} ຜ່ານແລ້ວ')
        ->call('saveTemplates');

    expect(Setting::get('notification_templates')['request.approve']['lo']['title'])->toBe('ໃບ {number} ຜ່ານແລ້ວ');
});

test('notification email template saves both languages + send language', function () {
    Livewire::test(Notifications::class)
        ->set('lang', 'en')
        ->call('saveFlags')
        ->set('email.lo.subject', 'ຫົວ ຂໍ້ ລາວ ໃໝ່')
        ->set('email.en.subject', 'New EN subject')
        ->call('saveEmail');

    expect(Setting::get('notifications')['lang'])->toBe('en');
    $stored = Setting::get('notification_templates')['email.set_password'];
    expect($stored['lo']['subject'])->toBe('ຫົວ ຂໍ້ ລາວ ໃໝ່');
    expect($stored['en']['subject'])->toBe('New EN subject');
});

test('disabling an in-app template via the settings page persists', function () {
    Livewire::test(Notifications::class)
        ->set('templates.1.enabled', false)
        ->call('saveTemplates');

    expect(Setting::get('notification_templates')['request.approve']['enabled'])->toBeFalse();
});

test('master flag short-circuits notifications', function () {
    Setting::put('notifications', ['enabled' => false]);
    app(NotificationService::class)->notify($this->admin->id, 'info', 'hello');
    expect(Notification::count())->toBe(0);

    Setting::put('notifications', ['enabled' => true]);
    app(NotificationService::class)->notify($this->admin->id, 'info', 'hello');
    expect(Notification::count())->toBe(1);
});

test('template helper replaces placeholders + honors custom override', function () {
    Setting::put('notification_templates', ['request.approve' => ['title' => 'OK {number}', 'message' => '']]);
    $t = NotificationService::template('request.approve', ['number' => 'MR2026-0001']);
    expect($t['title'])->toBe('OK MR2026-0001');
});

test('notification log renders + filters', function () {
    Notification::create(['user_id' => $this->admin->id, 'type' => 'warning', 'title' => 'overdue thing', 'created_at' => now()]);
    Livewire::test(NotificationLog::class)
        ->assertOk()
        ->assertSee('overdue thing')
        ->set('typeFilter', 'error')
        ->assertDontSee('overdue thing');
});

test('audit log renders for admin', function () {
    Livewire::test(Audit::class)->assertOk()->assertSee('Audit log');
});

test('reports page renders with module summary', function () {
    Livewire::test(Reports::class)->assertOk()->assertSee('Borrow')->assertSee('Expo');
});

test('system general + currency save', function () {
    Livewire::test(System::class)
        ->set('appName', 'WH Test')
        ->set('defaultBorrowDays', 14)
        ->call('saveGeneral')
        ->set('curPrimary', 'thb')
        ->set('curSecondary', 'lak')
        ->set('exchangeRate', 650)
        ->call('saveCurrency');

    expect(Setting::get('general')['default_borrow_days'])->toBe(14);
    expect(Setting::get('currency')['primary'])->toBe('THB');
    expect((float) Setting::get('currency')['exchange_rate'])->toEqual(650.0);
});

test('non-admin without audit permission is forbidden', function () {
    $u = User::factory()->create(['is_super_admin' => false]);
    $u->assignRole('requester');
    $this->actingAs($u);
    Livewire::test(Audit::class)->assertForbidden();
});
