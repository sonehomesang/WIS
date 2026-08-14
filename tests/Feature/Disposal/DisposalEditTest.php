<?php

use App\Livewire\Disposal\Show;
use App\Models\User;
use App\Services\DisposalService;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
});

function makeDisposalDraft(): App\Models\DisposalRecord
{
    return app(DisposalService::class)->createDraft([
        'title' => 'Old title',
        'items' => [
            ['source_type' => 'new', 'item_name' => 'Broken drill', 'qty' => 1, 'reason' => 'ໝົດ ອາຍຸ ໃຊ້ງານ'],
            ['source_type' => 'new', 'item_name' => 'Rusty saw', 'qty' => 2],
        ],
    ], auth()->user());
}

test('an in-progress record can be edited: item fields change and a row is removed', function () {
    $rec = makeDisposalDraft();
    $keepId = $rec->items()->orderBy('id')->first()->id;

    Livewire::test(Show::class, ['record' => $rec])
        ->call('openEdit')
        ->assertSet('editing', true)
        ->set('editTitle', 'Updated title')
        ->set('ef.0.item_name', 'Fixed drill name')
        ->set('ef.0.qty', 5)
        ->call('removeEditItem', 1)      // drop "Rusty saw"
        ->call('saveEdit')
        ->assertHasNoErrors()
        ->assertSet('editing', false);

    $rec->refresh()->load('items');
    expect($rec->title)->toBe('Updated title');
    expect($rec->items)->toHaveCount(1);
    expect($rec->items->first()->id)->toBe($keepId);
    expect($rec->items->first()->item_name)->toBe('Fixed drill name');
    expect($rec->items->first()->qty)->toBe(5);
});

test('editing can add a brand-new item created directly at disposal', function () {
    $rec = makeDisposalDraft();

    Livewire::test(Show::class, ['record' => $rec])
        ->call('openEdit')
        ->call('addEditItem')
        ->set('ef.2.item_name', 'Scrapped cable reel')
        ->set('ef.2.qty', 3)
        ->call('saveEdit')
        ->assertHasNoErrors();

    $rec->refresh()->load('items');
    expect($rec->items)->toHaveCount(3);
    expect($rec->items->pluck('item_name'))->toContain('Scrapped cable reel');
});

test('saveEdit requires an item name', function () {
    $rec = makeDisposalDraft();

    Livewire::test(Show::class, ['record' => $rec])
        ->call('openEdit')
        ->set('ef.0.item_name', '')
        ->call('saveEdit')
        ->assertHasErrors('ef.0.item_name');
});

test('a disposed record is locked — cannot be edited', function () {
    $rec = makeDisposalDraft();
    $rec->forceFill(['status' => 'disposed'])->save();

    $c = Livewire::test(Show::class, ['record' => $rec]);
    expect($c->instance()->canEditRecord())->toBeFalse();

    $c->call('openEdit')->assertStatus(403);
});

test('a cancelled record is locked — cannot be edited', function () {
    $rec = makeDisposalDraft();
    $rec->forceFill(['status' => 'cancelled'])->save();

    expect(Livewire::test(Show::class, ['record' => $rec])->instance()->canEditRecord())->toBeFalse();
});
