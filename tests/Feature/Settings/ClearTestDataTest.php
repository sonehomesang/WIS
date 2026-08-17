<?php

use App\Livewire\Settings\ClearTestData;
use App\Models\Notification;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function aNotification(User $u): Notification
{
    return Notification::create(['user_id' => $u->id, 'type' => 'info', 'title' => 'test', 'message' => 'x']);
}

test('super admin clears a selected transaction group and keeps users', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    aNotification($admin);
    expect(Notification::count())->toBe(1);

    Livewire::test(ClearTestData::class)
        ->set('selected', ['notifications'])
        ->set('confirm', 'CLEAR')
        ->call('clear')
        ->assertHasNoErrors();

    expect(Notification::count())->toBe(0);       // cleared
    expect(User::count())->toBeGreaterThan(0);    // users untouched
});

test('the confirm word must be exactly CLEAR — otherwise nothing is deleted', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    aNotification($admin);

    Livewire::test(ClearTestData::class)
        ->set('selected', ['notifications'])
        ->set('confirm', 'clear')     // wrong case
        ->call('clear')
        ->assertHasErrors('confirm');

    expect(Notification::count())->toBe(1);       // still there
});

test('at least one group must be selected', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(ClearTestData::class)
        ->set('selected', [])
        ->set('confirm', 'CLEAR')
        ->call('clear')
        ->assertHasErrors('selected');
});

test('a non-super-admin cannot open the tool', function () {
    $u = User::factory()->create(['is_super_admin' => false]);
    $u->syncRoles(['admin']);
    $this->actingAs($u);
    Livewire::test(ClearTestData::class)->assertForbidden();
});
