<?php

use App\Livewire\Survey\Form;
use App\Livewire\Survey\Results;
use App\Models\SurveyResponse;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

function makeUnit(string $slug = 'ciu', string $name = 'CIU'): Unit
{
    return Unit::create(['slug' => $slug, 'name' => $name]);
}

test('guest can submit the survey and it is recorded anonymously', function () {
    Livewire::test(Form::class)
        ->set('frequency', 'weekly')
        ->set('wh_receiving', 5)
        ->set('overall_wh', 4)
        ->set('doing_well', 'fast and accurate')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('done', true);

    expect(SurveyResponse::count())->toBe(1);
    $r = SurveyResponse::first();
    expect($r->frequency)->toBe('weekly')
        ->and($r->wh_receiving)->toBe(5)
        ->and($r->user_id)->toBeNull();
});

test('survey requires frequency and at least one rating', function () {
    Livewire::test(Form::class)->call('submit')->assertHasErrors('frequency');
    expect(SurveyResponse::count())->toBe(0);

    Livewire::test(Form::class)->set('frequency', 'daily')->call('submit')->assertHasErrors('overall_wh');
    expect(SurveyResponse::count())->toBe(0);
});

test('logged-in response captures the user and their unit', function () {
    $unit = makeUnit();
    $user = User::factory()->create(['unit_id' => $unit->id]);

    Livewire::actingAs($user)->test(Form::class)
        ->set('frequency', 'daily')
        ->set('overall_ie', 3)
        ->call('submit')
        ->assertSet('done', true);

    $r = SurveyResponse::first();
    expect($r->user_id)->toBe($user->id)->and($r->unit_id)->toBe($unit->id);
});

test('results dashboard is forbidden without reports.view', function () {
    $this->seed(RolePermissionSeeder::class);
    Livewire::actingAs(User::factory()->create())->test(Results::class)->assertForbidden();
});

test('results dashboard aggregates and filters', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create(['is_super_admin' => true]);

    SurveyResponse::create(['frequency' => 'daily', 'overall_wh' => 4, 'overall_ie' => 2]);
    SurveyResponse::create(['frequency' => 'weekly', 'overall_wh' => 2, 'overall_ie' => 4]);

    $c = Livewire::actingAs($admin)->test(Results::class);
    expect($c->viewData('total'))->toBe(2)
        ->and($c->viewData('overallWh'))->toBe(3.0)
        ->and($c->viewData('overallIe'))->toBe(3.0)
        ->and($c->viewData('dist')[4])->toBe(2);   // two 4-ratings across overall_wh + overall_ie

    // filter by frequency narrows the set
    $c->set('frequency', 'daily');
    expect($c->viewData('total'))->toBe(1)->and($c->viewData('overallWh'))->toBe(4.0);

    // service=wh drops the IE overall
    $c->set('frequency', '')->set('service', 'wh');
    expect($c->viewData('overallIe'))->toBe(3.0)   // avgOf still computes; view hides IE section
        ->and($c->viewData('total'))->toBe(2);
});

test('insights flags weak questions, strengths, and computes T2B/B2B', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create(['is_super_admin' => true]);

    // 5 responses: warehouse-receiving strong (5), customs weak (2)
    for ($i = 0; $i < 5; $i++) {
        SurveyResponse::create([
            'frequency' => 'weekly',
            'wh_receiving' => 5, 'wh_condition' => 5, 'wh_storage' => 5,
            'ie_customs' => 2, 'ie_communication' => 2, 'ie_urgent' => 2,
            'overall_wh' => 5, 'overall_ie' => 2,
        ]);
    }

    $c = Livewire::actingAs($admin)->test(Results::class);
    $ins = $c->viewData('insights');
    $texts = collect($ins['recos'])->pluck('text')->implode(' | ');

    expect($c->viewData('t2b'))->toBe(50)      // half the ratings are 5, half are 2
        ->and($c->viewData('b2b'))->toBe(50)
        ->and($texts)->toContain('ເຄລຍພາສີ')   // weak question surfaced
        ->and($texts)->toContain('ຮັບສິນຄ້າ')   // strength surfaced
        ->and(collect($ins['recos'])->pluck('level'))->toContain('critical');  // customs 2.0 < 3
});
