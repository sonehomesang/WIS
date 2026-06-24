<?php

use App\Livewire\Borrow\Show;
use App\Models\User;
use App\Services\BorrowService;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function aDraft(User $actor)
{
    return app(BorrowService::class)->createDraft([
        'borrow_type' => 'new_inventory', 'borrow_date' => now()->toDateString(),
        'period_days' => 5, 'items' => [['item_name' => 'Drill', 'qty' => 1]],
    ], $actor);
}

test('admin can edit record fields and it records history', function () {
    $admin = User::factory()->create(['is_super_admin' => true, 'display_name' => 'Adm']);
    $this->actingAs($admin);
    $r = aDraft($admin);

    Livewire::test(Show::class, ['record' => $r])
        ->call('openEdit')
        ->set('ef.borrower_name', 'Edited Name')
        ->set('ef.admin_notes', 'checked ok')
        ->set('ei.'.$r->items->first()->id.'.item_name', 'Drill X')
        ->call('saveEdit')
        ->assertHasNoErrors();

    $r->refresh();
    expect($r->borrower_name)->toBe('Edited Name');
    expect($r->admin_notes)->toBe('checked ok');
    expect($r->items->first()->item_name)->toBe('Drill X');
    expect($r->history()->where('action', 'edit')->exists())->toBeTrue();
});

test('viewer without edit permission cannot open edit', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $r = aDraft($admin);

    $viewer = User::factory()->create(['is_super_admin' => false]);
    $viewer->givePermissionTo('borrow.view');
    $r->forceFill(['borrower_user_id' => $viewer->id])->save();  // a party (can view own) but no edit right
    $this->actingAs($viewer);

    Livewire::test(Show::class, ['record' => $r->refresh()])->call('openEdit')->assertForbidden();
});
