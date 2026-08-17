<?php
use App\Livewire\Deposit\Create;
use App\Models\DepositItemPhoto;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('3-slot deposit photos persist with correct slot from camera and gallery', function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('public');
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(Create::class)
        ->set('items.0.item_name', 'X')->set('items.0.qty', 1)
        // overall: 1 from camera
        ->set('camUpload.0.overall', [UploadedFile::fake()->image('ov.jpg')])
        // id: 1 from gallery
        ->set('galUpload.0.id', [UploadedFile::fake()->image('id.jpg')])
        // damage: camera + gallery accumulate
        ->set('camUpload.0.damage', [UploadedFile::fake()->image('d1.jpg')])
        ->set('galUpload.0.damage', [UploadedFile::fake()->image('d2.jpg')])
        ->call('save', false);

    expect(DepositItemPhoto::count())->toBe(4);
    expect(DepositItemPhoto::where('slot', 'overall')->count())->toBe(1);
    expect(DepositItemPhoto::where('slot', 'id')->count())->toBe(1);
    expect(DepositItemPhoto::where('slot', 'damage')->count())->toBe(2);
    expect(DepositItemPhoto::whereNull('slot')->count())->toBe(0);
    // ທຸກ ຮູບ kind='deposit'
    expect(DepositItemPhoto::where('kind', 'deposit')->count())->toBe(4);
});

test('remove one slot photo before save drops only that file', function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('public');
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(Create::class)
        ->set('items.0.item_name', 'X')->set('items.0.qty', 1)
        ->set('camUpload.0.damage', [UploadedFile::fake()->image('d1.jpg')])
        ->set('galUpload.0.damage', [UploadedFile::fake()->image('d2.jpg')])
        ->call('removePhoto', 0, 'damage', 0)
        ->call('save', false);

    expect(DepositItemPhoto::where('slot', 'damage')->count())->toBe(1);
});
