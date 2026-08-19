<?php
use App\Livewire\Settings\RequestTypes;
use App\Models\RequestType;
use App\Models\User;
use App\Support\RequestType as Catalog;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

test('the catalogue exposes the seeded defaults incl. PM and PdM', function () {
    // migration seeded them; options come from the DB catalogue
    $opts = Catalog::options();
    expect(array_keys($opts))->toContain('CM', 'PM', 'PdM', 'eForm', 'project');
    expect($opts['PM'])->toBe('PM · Preventive Maintenance');
    expect($opts['PdM'])->toBe('PdM · Predictive Maintenance');
});

test('an admin can add a new request type and it appears in options', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(RequestTypes::class)
        ->call('newItem')
        ->set('key', 'Safety')
        ->set('label', 'Safety · ຄວາມ ປອດໄພ')
        ->call('save');

    expect(RequestType::where('key', 'Safety')->exists())->toBeTrue();
    expect(array_keys(Catalog::options()))->toContain('Safety');
});

test('toggling a type off removes it from active options but keeps label lookup', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
    $pm = RequestType::where('key', 'PM')->first();

    Livewire::test(RequestTypes::class)->call('toggle', $pm->id);

    expect(array_keys(Catalog::options()))->not->toContain('PM'); // hidden from dropdown
    expect(Catalog::label('PM'))->toBe('PM · Preventive Maintenance'); // old data still reads
});

test('a non-admin (no settings.view) cannot open the settings page', function () {
    $this->actingAs(User::factory()->create()); // plain user, no settings perm
    Livewire::test(RequestTypes::class)->assertForbidden();
});
