<?php

use App\Livewire\Inventory\Index;
use App\Models\InventoryItem;
use App\Models\InventoryItemPhoto;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('public');
});

test('primaryPhoto eager-loads one photo per item across a page (audit M6)', function () {
    $a = InventoryItem::create(['slug' => 'ma', 'name' => 'A', 'quantity' => 1]);
    $b = InventoryItem::create(['slug' => 'mb', 'name' => 'B', 'quantity' => 1]);
    $a->photos()->create(['path' => 'inventory/a.jpg', 'sort_order' => 1]);
    $b->photos()->create(['path' => 'inventory/b.jpg', 'sort_order' => 1]);

    // eager-load ຮ່ວມ ກັນ (ຄື list) — bug ເກົ່າ hasMany()->limit(1) ຈະ ໃຫ້ ຮູບ ແຖວ ດຽວ
    $items = InventoryItem::with('primaryPhoto')->whereIn('id', [$a->id, $b->id])->get();

    expect($items->firstWhere('id', $a->id)->primaryPhoto?->path)->toBe('inventory/a.jpg')
        ->and($items->firstWhere('id', $b->id)->primaryPhoto?->path)->toBe('inventory/b.jpg');
});

test('super admin can create an item with photos', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(Index::class)
        ->call('newItem')
        ->set('name', 'Drill with photo')
        ->set('quantity', 1)
        ->set('newPhotos', [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')])
        ->call('save')
        ->assertHasNoErrors();

    $item = InventoryItem::where('name', 'Drill with photo')->first();
    expect($item->photos)->toHaveCount(2);
    Storage::disk('public')->assertExists($item->photos->first()->path);
});

test('save surfaces a Lao validation warning listing the missing required field', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(Index::class)
        ->call('newItem')
        ->set('name', '')                              // required, now empty
        ->call('save')
        ->assertHasErrors(['name' => 'required'])
        ->assertSee('ຍັງບັນທຶກບໍ່ໄດ້')                    // yellow warning box heading
        ->assertSee('ຊ່ອງ ຊື່ ຈຳເປັນຕ້ອງໃສ່.');           // Lao message from lang/lo/validation.php
});

test('removing a photo deletes the file and record', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
    $item = InventoryItem::create(['slug' => 'x', 'name' => 'X', 'quantity' => 1]);
    $path = UploadedFile::fake()->image('p.jpg')->store('inventory/'.$item->id, 'public');
    $photo = $item->photos()->create(['path' => $path, 'sort_order' => 1]);

    Livewire::test(Index::class)
        ->call('editItem', $item->id)
        ->call('removePhoto', $photo->id);

    expect(InventoryItemPhoto::find($photo->id))->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('rejects more than the photo limit', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    $tooMany = collect(range(1, Index::MAX_PHOTOS + 1))
        ->map(fn ($i) => UploadedFile::fake()->image("p{$i}.jpg"))->all();

    Livewire::test(Index::class)
        ->call('newItem')
        ->set('name', 'Too many photos')
        ->set('quantity', 1)
        ->set('newPhotos', $tooMany)
        ->call('save')
        ->assertHasErrors('newPhotos');

    expect(InventoryItem::where('name', 'Too many photos')->exists())->toBeFalse();
});

test('rejects non-image upload', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(Index::class)
        ->call('newItem')
        ->set('name', 'Bad file')
        ->set('quantity', 1)
        ->set('newPhotos', [UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')])
        ->call('save')
        ->assertHasErrors('newPhotos.*');
});
