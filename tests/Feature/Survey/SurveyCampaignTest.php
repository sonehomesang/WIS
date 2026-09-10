<?php

use App\Livewire\Survey\Form;
use App\Livewire\Survey\Prompt;
use App\Livewire\Survey\Results;
use App\Models\Department;
use App\Models\Setting;
use App\Models\SurveyResponse;
use App\Models\Unit;
use App\Models\User;
use App\Support\SurveyCampaign;
use Livewire\Livewire;

function openCampaign(array $extra = []): void
{
    Setting::put('survey', array_merge(['active' => true, 'start_date' => null, 'end_date' => null], $extra), null);
}

test('the campaign is open only when active and inside the window', function () {
    Setting::put('survey', ['active' => false], null);
    expect(SurveyCampaign::isOpen())->toBeFalse();

    openCampaign();
    expect(SurveyCampaign::isOpen())->toBeTrue();

    openCampaign(['start_date' => now()->addDay()->toDateString()]);   // starts tomorrow
    expect(SurveyCampaign::isOpen())->toBeFalse();

    openCampaign(['end_date' => now()->subDay()->toDateString()]);     // ended yesterday
    expect(SurveyCampaign::isOpen())->toBeFalse();
});

test('a user is prompted until they answer, then never again this campaign', function () {
    openCampaign();
    $u = User::factory()->create();
    expect(SurveyCampaign::shouldPrompt($u))->toBeTrue();

    SurveyResponse::create(['user_id' => $u->id, 'frequency' => 'daily', 'overall_wh' => 4]);
    expect(SurveyCampaign::shouldPrompt($u->fresh()))->toBeFalse();
});

test('no one is prompted when the campaign is closed', function () {
    Setting::put('survey', ['active' => false], null);
    expect(SurveyCampaign::shouldPrompt(User::factory()->create()))->toBeFalse();
});

test('the popup shows for an eligible user and disappears once answered', function () {
    openCampaign();
    $u = User::factory()->create();
    Livewire::actingAs($u)->test(Prompt::class)->assertSee('ຕອບ ເລີຍ');

    SurveyResponse::create(['user_id' => $u->id, 'frequency' => 'daily', 'overall_wh' => 5]);
    Livewire::actingAs($u->fresh())->test(Prompt::class)->assertDontSee('ຕອບ ເລີຍ');
});

test('the public form rejects submissions when the campaign is closed', function () {
    Setting::put('survey', ['active' => false], null);

    Livewire::test(Form::class)
        ->set('frequency', 'daily')
        ->set('overall_wh', 4)
        ->call('submit')
        ->assertSet('closed', true);

    expect(SurveyResponse::count())->toBe(0);
});

test('a logged-in submission captures the user unit and department', function () {
    openCampaign();
    $unit = Unit::create(['slug' => 'omu', 'name' => 'OMU', 'is_active' => true]);
    $dept = Department::create(['unit_id' => $unit->id, 'slug' => 'wid', 'name' => 'WID', 'is_active' => true]);
    $u = User::factory()->create(['unit_id' => $unit->id, 'department_id' => $dept->id]);

    Livewire::actingAs($u)->test(Form::class)
        ->set('frequency', 'weekly')
        ->set('overall_wh', 5)
        ->call('submit')
        ->assertSet('done', true);

    $r = SurveyResponse::first();
    expect($r->user_id)->toBe($u->id)
        ->and($r->unit_id)->toBe($unit->id)
        ->and($r->department_id)->toBe($dept->id);
});

test('results can filter by department and the admin can save the campaign', function () {
    $unit = Unit::create(['slug' => 'omu', 'name' => 'OMU', 'is_active' => true]);
    $wid = Department::create(['unit_id' => $unit->id, 'slug' => 'wid', 'name' => 'WID', 'is_active' => true]);
    $other = Department::create(['unit_id' => $unit->id, 'slug' => 'mtn', 'name' => 'MTN', 'is_active' => true]);
    SurveyResponse::create(['frequency' => 'daily', 'overall_wh' => 5, 'department_id' => $wid->id, 'doing_well' => 'great WID']);
    SurveyResponse::create(['frequency' => 'daily', 'overall_wh' => 1, 'department_id' => $other->id, 'doing_well' => 'bad MTN']);

    $admin = User::factory()->create(['is_super_admin' => true]);

    Livewire::actingAs($admin)->test(Results::class)
        ->set('department_id', $wid->id)
        ->assertSee('great WID')
        ->assertDontSee('bad MTN')
        ->set('campaignActive', true)
        ->set('startDate', now()->toDateString())
        ->call('saveCampaign')
        ->assertHasNoErrors();

    expect(Setting::get('survey')['active'])->toBeTrue();
});
