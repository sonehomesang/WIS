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
