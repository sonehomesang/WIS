<?php

use App\Livewire\Settings\Notifications;
use App\Models\Setting;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\TeamsNotifier;
use App\Support\TeamsSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/** Configure the teams setting + clear its cache. */
function teamsConfig(array $s): void
{
    Setting::put('teams', $s, null);
    TeamsSettings::forget();
}

test('webhookFor resolves module override, falls back to default, and honours the switches', function () {
    teamsConfig([
        'enabled' => true,
        'default_webhook' => 'https://d.example/hook',
        'modules' => [
            'borrow' => ['enabled' => true, 'webhook' => 'https://b.example/hook'],
            'deposit' => ['enabled' => true, 'webhook' => ''],
            'request' => ['enabled' => false, 'webhook' => 'https://r.example/hook'],
        ],
    ]);

    expect(TeamsSettings::webhookFor('borrow'))->toBe('https://b.example/hook')   // own override
        ->and(TeamsSettings::webhookFor('deposit'))->toBe('https://d.example/hook') // falls back to default
        ->and(TeamsSettings::webhookFor('request'))->toBeNull()                     // module off
        ->and(TeamsSettings::webhookFor('oga'))->toBeNull();                        // no config

    teamsConfig(['enabled' => false, 'default_webhook' => 'https://d.example/hook', 'modules' => ['borrow' => ['enabled' => true, 'webhook' => 'https://b.example/hook']]]);
    expect(TeamsSettings::webhookFor('borrow'))->toBeNull();   // master off
});

test('a template notification posts a MessageCard to the module webhook', function () {
    Http::fake(['*' => Http::response('', 200)]);
    Setting::put('notifications', ['enabled' => true], null);
    teamsConfig(['enabled' => true, 'default_webhook' => '', 'modules' => ['borrow' => ['enabled' => true, 'webhook' => 'https://b.example/hook']]]);

    $u = User::factory()->create();
    app(NotificationService::class)->notifyTemplate($u->id, 'success', 'borrow.approve', ['number' => 'BR2026-0001'], '/borrow/1');

    Http::assertSent(fn ($req) => $req->url() === 'https://b.example/hook'
        && ($req->data()['@type'] ?? null) === 'MessageCard'
        && str_contains(json_encode($req->data()), 'BR2026-0001'));
});

test('nothing is posted to Teams when the module is off', function () {
    Http::fake();
    Setting::put('notifications', ['enabled' => true], null);
    teamsConfig(['enabled' => true, 'default_webhook' => 'https://d.example/hook', 'modules' => ['borrow' => ['enabled' => false, 'webhook' => '']]]);

    app(NotificationService::class)->notifyTemplate(User::factory()->create()->id, 'success', 'borrow.approve', ['number' => 'X'], null);

    Http::assertNothingSent();
});

test('nothing is posted when the master notification switch is off', function () {
    Http::fake();
    Setting::put('notifications', ['enabled' => false], null);
    teamsConfig(['enabled' => true, 'default_webhook' => 'https://d.example/hook', 'modules' => ['borrow' => ['enabled' => true, 'webhook' => '']]]);

    app(NotificationService::class)->notifyTemplate(User::factory()->create()->id, 'success', 'borrow.approve', ['number' => 'X'], null);

    Http::assertNothingSent();
});

test('testWebhook reports success and failure', function () {
    Http::fake(['ok.example/*' => Http::response('', 200), 'bad.example/*' => Http::response('nope', 500)]);

    expect(app(TeamsNotifier::class)->testWebhook('https://ok.example/hook')['ok'])->toBeTrue()
        ->and(app(TeamsNotifier::class)->testWebhook('https://bad.example/hook')['ok'])->toBeFalse();
});

test('the notifications page saves the Teams config', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(Notifications::class)
        ->set('teamsEnabled', true)
        ->set('teamsDefaultWebhook', 'https://d.example/hook')
        ->set('teamsModules.borrow.enabled', true)
        ->set('teamsModules.borrow.webhook', 'https://b.example/hook')
        ->call('saveTeams')
        ->assertHasNoErrors();

    $stored = Setting::get('teams');
    expect($stored['enabled'])->toBeTrue()
        ->and($stored['modules']['borrow']['webhook'])->toBe('https://b.example/hook')
        ->and(TeamsSettings::webhookFor('borrow'))->toBe('https://b.example/hook');
});
