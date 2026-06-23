<?php

use App\Livewire\Expo\Create;
use App\Livewire\Expo\Index;
use App\Livewire\Expo\Show;
use App\Models\ExpoCompany;
use App\Models\ExpoContact;
use App\Models\ExpoEvent;
use App\Models\User;
use App\Services\ExpoService;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function anExpo(User $actor, array $attendees = []): ExpoEvent
{
    return app(ExpoService::class)->create([
        'title' => 'China Hydropower Expo', 'topic' => 'turbine', 'city' => 'Guangzhou', 'country' => 'China',
        'start_date' => now()->toDateString(), 'end_date' => now()->addDays(3)->toDateString(),
        'total_companies_at_expo' => 420, 'attendee_ids' => $attendees,
    ], $actor);
}

test('create makes EXP number + attendees', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $a1 = User::factory()->create();
    $this->actingAs($admin);
    $e = anExpo($admin, [$a1->id]);

    expect($e->expo_number)->toStartWith('EXP'.now()->year.'-');
    expect($e->status)->toBe('draft');
    expect($e->attendees()->count())->toBe(1);
    expect($e->attendees()->first()->user_name)->toBe($a1->display_name);
});

test('can add a company with score + a contact', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $e = anExpo($admin);

    Livewire::test(Show::class, ['record' => $e])
        ->call('openCompany')
        ->set('cf.name', 'Dongfang Electric')
        ->set('cf.interest_level', 'hot')
        ->set('cf.score', 4)
        ->set('cf.products', 'Turbine, generator')
        ->call('saveCompany')
        ->assertHasNoErrors();

    $c = ExpoCompany::where('event_id', $e->id)->first();
    expect($c->name)->toBe('Dongfang Electric');
    expect($c->score)->toBe(4);
    expect($c->interest_level)->toBe('hot');

    Livewire::test(Show::class, ['record' => $e])
        ->call('openContact', $c->id)
        ->set('kf.name', 'Mr Li Wei')
        ->set('kf.role', 'agent')
        ->set('kf.app_contact', 'WeChat: liwei')
        ->call('saveContact')
        ->assertHasNoErrors();

    $k = ExpoContact::where('company_id', $c->id)->first();
    expect($k->name)->toBe('Mr Li Wei');
    expect($k->role)->toBe('agent');
});

test('attendee opinions + overview + finalize save', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $a1 = User::factory()->create();
    $this->actingAs($admin);
    $e = anExpo($admin, [$a1->id]);
    $att = $e->attendees()->first();

    Livewire::test(Show::class, ['record' => $e])
        ->set("opinions.{$att->id}", 'ດີຫຼາຍ')
        ->call('saveOpinions')
        ->set('feedback', 'ງານໃຫຍ່')
        ->set('nextLocation', 'Beijing 2027')
        ->call('saveOverview')
        ->call('finalize');

    $e->refresh();
    expect($e->status)->toBe('finalized');
    expect($e->feedback)->toBe('ງານໃຫຍ່');
    expect($att->refresh()->opinion)->toBe('ດີຫຼາຍ');
});

test('admin can soft-delete an expo and restore it', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $e = anExpo($admin);

    Livewire::test(Show::class, ['record' => $e])->call('openDelete')->set('deleteReason', 'dup')->call('deleteRecord');
    expect($e->refresh()->trashed())->toBeTrue();

    Livewire::test(Index::class)->set('showDeleted', true)->call('restore', $e->id);
    expect($e->refresh()->trashed())->toBeFalse();
});

test('create form adds attendees one at a time via dropdown', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $u1 = User::factory()->create();
    $u2 = User::factory()->create();
    $this->actingAs($admin);

    Livewire::test(Create::class)
        ->set('pickUser', (string) $u1->id)   // updatedPickUser adds + clears
        ->assertSet('attendee_ids', [$u1->id])
        ->assertSet('pickUser', '')
        ->set('pickUser', (string) $u2->id)
        ->assertSet('attendee_ids', [$u1->id, $u2->id])
        ->call('removeAttendee', $u1->id)
        ->assertSet('attendee_ids', [$u2->id]);
});

test('can edit event meta via modal', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $e = anExpo($admin);

    Livewire::test(Show::class, ['record' => $e])
        ->call('openEvent')
        ->assertSet('ef.title', 'China Hydropower Expo')
        ->set('ef.title', 'Vietnam Machinery Expo')
        ->set('ef.city', 'Hanoi')
        ->set('ef.total_companies_at_expo', 555)
        ->call('saveEvent')
        ->assertHasNoErrors()
        ->assertSet('showEvent', false);

    $e->refresh();
    expect($e->title)->toBe('Vietnam Machinery Expo');
    expect($e->city)->toBe('Hanoi');
    expect($e->total_companies_at_expo)->toBe(555);
});

test('show page can add and remove attendees', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $u1 = User::factory()->create();
    $u2 = User::factory()->create();
    $this->actingAs($admin);
    $e = anExpo($admin, [$u1->id]);

    $t = Livewire::test(Show::class, ['record' => $e]);

    // add u2 via dropdown
    $t->set('pickUser', (string) $u2->id)->assertSet('pickUser', '');
    expect($e->refresh()->attendees()->count())->toBe(2);

    // adding the same user again is a no-op
    $t->set('pickUser', (string) $u1->id);
    expect($e->refresh()->attendees()->count())->toBe(2);

    // remove u1 — keeps u2 + its opinion intact
    $att1 = $e->attendees()->where('user_id', $u1->id)->first();
    $t->call('removeAttendee', $att1->id);
    expect($e->refresh()->attendees()->count())->toBe(1);
    expect($e->attendees()->first()->user_id)->toBe($u2->id);
});

test('non-permitted user cannot open expo index', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => false]));
    Livewire::test(Index::class)->assertForbidden();
});
