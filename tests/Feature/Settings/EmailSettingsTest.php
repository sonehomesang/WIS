<?php

use App\Livewire\Settings\Email;
use App\Models\Setting;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create(['is_super_admin' => true, 'status' => 'active', 'email_verified_at' => now()]);
});

test('admin can save SMTP settings and the password is stored encrypted', function () {
    actingAs($this->admin);

    Livewire::test(Email::class)
        ->set('enabled', true)
        ->set('host', 'smtp.gmail.com')
        ->set('port', 587)
        ->set('scheme', 'tls')
        ->set('username', 'me@gmail.com')
        ->set('password', 'secret-app-pw')
        ->set('fromAddress', 'noreply@homesang.pro')
        ->set('fromName', 'WH')
        ->call('save')
        ->assertHasNoErrors();

    $m = Setting::get('mail');
    expect($m['mailer'])->toBe('smtp');
    expect($m['host'])->toBe('smtp.gmail.com');
    expect($m['password'])->not->toBe('secret-app-pw');                 // ບໍ່ ໃຊ້ plaintext
    expect(Crypt::decryptString($m['password']))->toBe('secret-app-pw');
});

test('applyMailSettings overrides the mail config from DB', function () {
    Setting::put('mail', [
        'mailer' => 'smtp', 'host' => 'smtp.example.com', 'port' => 465, 'scheme' => 'ssl',
        'username' => 'u', 'password' => Crypt::encryptString('p'),
        'from_address' => 'from@x.com', 'from_name' => 'X',
    ]);
    Cache::forget('settings.mail');

    AppServiceProvider::applyMailSettings();

    expect(config('mail.default'))->toBe('smtp');
    expect(config('mail.mailers.smtp.host'))->toBe('smtp.example.com');
    expect(config('mail.mailers.smtp.port'))->toBe(465);
    expect(config('mail.mailers.smtp.password'))->toBe('p');
    expect(config('mail.mailers.smtp.scheme'))->toBe('smtps');          // ssl → smtps
    expect(config('mail.from.address'))->toBe('from@x.com');
});

test('leaving the password blank keeps the existing one', function () {
    Setting::put('mail', ['mailer' => 'smtp', 'host' => 'h', 'port' => 587,
        'password' => Crypt::encryptString('keepme'), 'from_address' => 'a@b.com']);
    actingAs($this->admin);

    Livewire::test(Email::class)
        ->set('enabled', true)
        ->set('host', 'h2')
        ->set('port', 587)
        ->set('fromAddress', 'a@b.com')
        ->set('password', '')
        ->call('save')
        ->assertHasNoErrors();

    expect(Crypt::decryptString(Setting::get('mail')['password']))->toBe('keepme');
});

test('the test-email flow runs without error via the configured mailer', function () {
    Mail::fake();
    Setting::put('mail', ['mailer' => 'smtp', 'host' => 'h', 'port' => 587,
        'password' => Crypt::encryptString('p'), 'from_address' => 'a@b.com', 'from_name' => 'WH']);
    actingAs($this->admin);

    Livewire::test(Email::class)
        ->set('testTo', 'target@x.com')
        ->call('sendTest')
        ->assertSet('resultType', 'ok');
});

test('a requester cannot open the email settings page', function () {
    $u = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
    $u->syncRoles(['requester']);
    actingAs($u);

    Livewire::test(Email::class)->assertForbidden();
});
