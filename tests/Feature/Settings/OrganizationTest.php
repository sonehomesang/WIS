<?php

use App\Livewire\Settings\Organization;
use App\Models\Department;
use App\Models\Unit;
use App\Models\User;
use Livewire\Livewire;

function superAdminUser(): User
{
    return User::factory()->create(['is_super_admin' => true]);
}

test('super admin can create a unit', function () {
    $this->actingAs(superAdminUser());

    Livewire::test(Organization::class)
        ->call('newUnit')
        ->set('name', 'Engineering')
        ->call('save')
        ->assertHasNoErrors();

    expect(Unit::where('name', 'Engineering')->exists())->toBeTrue();
});

test('super admin can add a department to a unit', function () {
    $this->actingAs(superAdminUser());
    $unit = Unit::create(['slug' => 'engineering', 'name' => 'Engineering', 'is_active' => true]);

    Livewire::test(Organization::class)
        ->call('selectUnit', $unit->id)
        ->call('newDepartment')
        ->set('name', 'Civil')
        ->call('save')
        ->assertHasNoErrors();

    expect(Department::where('name', 'Civil')->where('unit_id', $unit->id)->exists())->toBeTrue();
});

test('unit name is required', function () {
    $this->actingAs(superAdminUser());

    Livewire::test(Organization::class)
        ->call('newUnit')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

test('non-permitted user cannot open organization', function () {
    $user = User::factory()->create(['is_super_admin' => false]);
    $this->actingAs($user);

    Livewire::test(Organization::class)->assertForbidden();
});

test('duplicate unit name is rejected', function () {
    $this->actingAs(superAdminUser());
    Unit::create(['slug' => 'dup', 'name' => 'Dup', 'is_active' => true]);

    Livewire::test(Organization::class)
        ->call('newUnit')->set('name', 'Dup')->call('save')
        ->assertHasErrors(['name']);
});

test('toggle disables a unit', function () {
    $this->actingAs(superAdminUser());
    $u = Unit::create(['slug' => 'eng', 'name' => 'Engineering', 'is_active' => true]);

    Livewire::test(Organization::class)->call('toggleUnit', $u->id);

    expect($u->fresh()->is_active)->toBeFalse();
});

test('cannot delete a unit that has departments', function () {
    $this->actingAs(superAdminUser());
    $u = Unit::create(['slug' => 'eng', 'name' => 'Engineering', 'is_active' => true]);
    Department::create(['unit_id' => $u->id, 'slug' => 'civ', 'name' => 'Civil', 'is_active' => true]);

    Livewire::test(Organization::class)->call('openDelete', 'unit', $u->id)->assertHasErrors('row');

    expect(Unit::find($u->id))->not->toBeNull();
});

test('deleting an empty unit needs a reason, records who, and can be restored', function () {
    $admin = superAdminUser();
    $this->actingAs($admin);
    $u = Unit::create(['slug' => 'empty', 'name' => 'Empty', 'is_active' => true]);

    // reason required
    Livewire::test(Organization::class)
        ->call('openDelete', 'unit', $u->id)
        ->call('deleteRecord')
        ->assertHasErrors(['deleteReason' => 'required']);
    expect(Unit::find($u->id))->not->toBeNull();

    // delete with reason → soft-deleted + metadata
    Livewire::test(Organization::class)
        ->call('openDelete', 'unit', $u->id)
        ->set('deleteReason', 'ບໍ່ ໃຊ້ ແລ້ວ')
        ->call('deleteRecord')
        ->assertHasNoErrors();

    expect(Unit::find($u->id))->toBeNull();
    $trashed = Unit::withTrashed()->find($u->id);
    expect($trashed->trashed())->toBeTrue();
    expect($trashed->deleted_reason)->toBe('ບໍ່ ໃຊ້ ແລ້ວ');
    expect($trashed->deleted_by)->toBe($admin->id);

    // Deleted Log toggle shows it, then restore clears the metadata
    Livewire::test(Organization::class)
        ->call('toggleDeletedLog', 'unit')
        ->assertViewHas('showDelUnits', true)
        ->assertSee('Empty')
        ->call('restoreRecord', 'unit', $u->id);
    expect(Unit::find($u->id))->not->toBeNull();
    expect(Unit::find($u->id)->deleted_reason)->toBeNull();
});

test('a department can be soft-deleted with a reason and restored', function () {
    $this->actingAs(superAdminUser());
    $u = Unit::create(['slug' => 'eng', 'name' => 'Engineering', 'is_active' => true]);
    $d = Department::create(['unit_id' => $u->id, 'slug' => 'civ', 'name' => 'Civil', 'is_active' => true]);

    Livewire::test(Organization::class)
        ->call('selectUnit', $u->id)
        ->call('openDelete', 'department', $d->id)
        ->set('deleteReason', 'ຍ້າຍ ໄປ unit ໃໝ່')
        ->call('deleteRecord')
        ->assertHasNoErrors();

    expect(Department::find($d->id))->toBeNull();
    expect(Department::withTrashed()->find($d->id)->deleted_reason)->toBe('ຍ້າຍ ໄປ unit ໃໝ່');

    Livewire::test(Organization::class)->call('selectUnit', $u->id)->call('restoreRecord', 'department', $d->id);
    expect(Department::find($d->id))->not->toBeNull();
});
