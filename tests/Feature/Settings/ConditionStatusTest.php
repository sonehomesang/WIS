<?php

use App\Livewire\Settings\ConditionStatuses;
use App\Models\ConditionStatus as Status;
use App\Models\User;
use App\Support\ConditionStatus as Catalog;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Catalog::forget();
});

test('the migration seeds the 8 defaults and the facade reads them from the DB', function () {
    expect(Status::count())->toBe(8);
    expect(Catalog::options())->toHaveKey('in_service')->toHaveKey('deteriorated');
    // disposable() reflects the is_disposable flags
    expect(Catalog::disposable())->toContain('beyond_repair')->not->toContain('in_service');
    expect(Catalog::shortLabel('deteriorated'))->toBe('ເສື່ອມ ສະພາບ');
});

test('an admin can add a new condition status; it flows into the catalogue', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(ConditionStatuses::class)
        ->call('newItem')
        ->set('key', 'lost')
        ->set('label', 'ເສຍ ຫາຍ · Lost')
        ->set('color', 'pink')
        ->set('is_disposable', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(Status::where('key', 'lost')->exists())->toBeTrue();
    expect(Catalog::options())->toHaveKey('lost');
    expect(Catalog::disposable())->toContain('lost');        // is_disposable respected
    expect(Catalog::badge('lost'))->toBe('bg-pink-50 text-pink-700');
});

test('editing keeps the key immutable but updates label/color/disposable', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
    $s = Status::where('key', 'obsolete')->first();

    Livewire::test(ConditionStatuses::class)
        ->call('editItem', $s->id)
        ->assertSet('key', 'obsolete')
        ->set('label', 'ເກົ່າ ຫຼາຍ · Very old')
        ->set('color', 'gray')
        ->set('is_disposable', false)
        ->call('save')
        ->assertHasNoErrors();

    $s->refresh();
    expect($s->key)->toBe('obsolete');                        // unchanged
    expect($s->label)->toBe('ເກົ່າ ຫຼາຍ · Very old');
    expect(Catalog::disposable())->not->toContain('obsolete'); // flag change flows through
});

test('toggling active removes a status from the pickable options', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
    $s = Status::where('key', 'awaiting_parts')->first();

    Livewire::test(ConditionStatuses::class)->call('toggle', $s->id);

    expect($s->refresh()->is_active)->toBeFalse();
    expect(Catalog::options())->not->toHaveKey('awaiting_parts');   // inactive → not offered
    expect(Catalog::label('awaiting_parts'))->toContain('ລໍ ອາໄຫຼ່'); // but old data still reads
});

test('adding a duplicate or malformed key is rejected', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(ConditionStatuses::class)
        ->call('newItem')->set('key', 'in_service')->set('label', 'dup')->call('save')
        ->assertHasErrors('key');

    Livewire::test(ConditionStatuses::class)
        ->call('newItem')->set('key', 'Bad Key!')->set('label', 'x')->call('save')
        ->assertHasErrors('key');
});

test('a non-settings user cannot open the condition-status admin', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => false]));
    Livewire::test(ConditionStatuses::class)->assertForbidden();
});
