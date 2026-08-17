<?php
use App\Livewire\Deposit\Show;
use App\Models\DepositItemPhoto;
use App\Models\User;
use App\Services\DepositService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('public');
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
});

function makeRecord(): App\Models\DepositRecord {
    return app(DepositService::class)->createDraft([
        'request_type' => 'legacy', 'deposit_date' => now()->toDateString(),
        'items' => [['item_name' => 'Old pump', 'qty' => 1]],
    ], auth()->user());
}

test('admin edit adds 3-slot deposit photos to an existing record with correct slots', function () {
    $rec = makeRecord();
    $it = $rec->items()->first();

    Livewire::test(Show::class, ['record' => $rec])
        ->call('openEdit')
        ->set("edCam.{$it->id}.overall", [UploadedFile::fake()->image('o.jpg')])
        ->set("edGal.{$it->id}.id", [UploadedFile::fake()->image('i.jpg')])
        ->set("edCam.{$it->id}.damage", [UploadedFile::fake()->image('d1.jpg')])
        ->set("edGal.{$it->id}.damage", [UploadedFile::fake()->image('d2.jpg')])
        ->call('saveEdit');

    expect(DepositItemPhoto::where('kind', 'deposit')->count())->toBe(4);
    expect(DepositItemPhoto::where('slot', 'overall')->count())->toBe(1);
    expect(DepositItemPhoto::where('slot', 'id')->count())->toBe(1);
    expect(DepositItemPhoto::where('slot', 'damage')->count())->toBe(2);
    expect(DepositItemPhoto::whereNull('slot')->count())->toBe(0);
});

test('a non-admin without deposit.edit cannot add edit photos', function () {
    $rec = makeRecord();
    $it = $rec->items()->first();
    // plain viewer (owner) — can view but not edit
    $viewer = User::factory()->create();
    $viewer->givePermissionTo('deposit.view');
    $rec->update(['owner_user_id' => $viewer->id]);
    $this->actingAs($viewer);

    Livewire::test(Show::class, ['record' => $rec])
        ->call('openEdit')
        ->assertForbidden();
});

test('removeEditPhoto drops a pending slot photo before save', function () {
    $rec = makeRecord();
    $it = $rec->items()->first();

    Livewire::test(Show::class, ['record' => $rec])
        ->call('openEdit')
        ->set("edCam.{$it->id}.damage", [UploadedFile::fake()->image('d1.jpg')])
        ->set("edGal.{$it->id}.damage", [UploadedFile::fake()->image('d2.jpg')])
        ->call('removeEditPhoto', $it->id, 'damage', 0)
        ->call('saveEdit');

    expect(DepositItemPhoto::where('slot', 'damage')->count())->toBe(1);
});
