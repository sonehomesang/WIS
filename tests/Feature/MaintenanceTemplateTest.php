<?php

use App\Livewire\Equipment\MaintenanceTemplates;
use App\Models\MaintenanceTemplate;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('admin can create a maintenance template (blank items dropped, freqs kept)', function () {
    actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(MaintenanceTemplates::class)
        ->call('newTemplate')
        ->set('tName', 'Forklift service')
        ->set('tCategory', 'Forklift')
        ->set('tItems', [
            ['label' => 'ປ່ຽນ ນ້ຳມັນ ເຄື່ອງ', 'freqs' => ['quarterly', 'annual']],
            ['label' => 'ກວດ ຢາງ', 'freqs' => []],
            ['label' => '', 'freqs' => ['monthly']],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $t = MaintenanceTemplate::first();
    expect($t->name)->toBe('Forklift service');
    expect($t->category)->toBe('Forklift');
    // ຂໍ້ ວ່າງ ຖືກ ຕັດ ອອກ · ເກັບ ເປັນ {label, freqs}; freqs ຄັດ ໃຫ້ ຢູ່ ໃນ ຮອບ ທີ່ ຮັບຮອງ
    expect($t->normalizedItems())->toBe([
        ['label' => 'ປ່ຽນ ນ້ຳມັນ ເຄື່ອງ', 'freqs' => ['quarterly', 'annual']],
        ['label' => 'ກວດ ຢາງ', 'freqs' => []],
    ]);
    expect($t->hasFrequencies())->toBeTrue();
});

test('editing a template updates it without creating a new one', function () {
    actingAs(User::factory()->create(['is_super_admin' => true]));
    $t = MaintenanceTemplate::create(['name' => 'Old', 'items' => [['label' => 'A', 'freqs' => []]], 'is_active' => true]);

    Livewire::test(MaintenanceTemplates::class)
        ->call('editTemplate', $t->id)
        ->assertSet('tName', 'Old')
        ->set('tName', 'New name')
        ->set('tActive', false)
        ->call('save')
        ->assertHasNoErrors();

    expect(MaintenanceTemplate::count())->toBe(1);
    expect($t->fresh()->name)->toBe('New name');
    expect($t->fresh()->is_active)->toBeFalse();
});

test('an admin can delete a maintenance template', function () {
    actingAs(User::factory()->create(['is_super_admin' => true]));
    $t = MaintenanceTemplate::create(['name' => 'Doomed', 'items' => [], 'is_active' => true]);

    Livewire::test(MaintenanceTemplates::class)
        ->call('delete', $t->id);

    expect(MaintenanceTemplate::find($t->id))->toBeNull();
});

test('a requester cannot access the maintenance template manager', function () {
    $u = User::factory()->create();
    $u->syncRoles(['requester']);
    actingAs($u);

    Livewire::test(MaintenanceTemplates::class)->assertForbidden();
});

test('a department-scoped admin cannot access the central template manager', function () {
    $u = User::factory()->create();
    $u->syncRoles(['department_admin']);
    actingAs($u);

    Livewire::test(MaintenanceTemplates::class)->assertForbidden();
});
