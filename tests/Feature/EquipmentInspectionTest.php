<?php

use App\Livewire\Equipment\Index;
use App\Models\Equipment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->staff = User::factory()->create();
    $this->staff->syncRoles(['warehouse_staff']);
});

test('warehouse staff can record an inspection', function () {
    $e = Equipment::create(['asset_code' => 'EQ-01', 'name' => 'Generator', 'quantity' => 5]);

    actingAs($this->staff);
    Livewire::test(Index::class)
        ->call('newInspection')
        ->call('pickInspectionEquipment', $e->id)
        ->set('insInspector', 'ທ. ສົມສະໜຸກ')
        ->set('insResult', 'pass')
        ->call('saveInspection')
        ->assertHasNoErrors();

    expect($e->inspections()->count())->toBe(1);
    expect($e->inspections()->first()->result)->toBe('pass');
});

test('an inspection can update the equipment status breakdown', function () {
    $e = Equipment::create(['asset_code' => 'EQ-02', 'name' => 'Drills', 'quantity' => 10]);

    actingAs($this->staff);
    Livewire::test(Index::class)
        ->call('newInspection')
        ->call('pickInspectionEquipment', $e->id)
        ->set('insResult', 'follow_up')
        ->set('insUpdateStatus', true)
        ->set('insRepair', 3)
        ->set('insRetired', 1)
        ->call('saveInspection')
        ->assertHasNoErrors();

    expect($e->fresh()->statusBreakdown())->toBe(['active' => 6, 'repair' => 3, 'retired' => 1]);
});

test('multiple inspection evidence photos are stored', function () {
    Storage::fake('public');
    $e = Equipment::create(['asset_code' => 'EQ-03', 'name' => 'Pump', 'quantity' => 1]);

    actingAs($this->staff);
    Livewire::test(Index::class)
        ->call('newInspection')
        ->call('pickInspectionEquipment', $e->id)
        ->set('insPhotos', [
            UploadedFile::fake()->image('check1.jpg', 800, 600),
            UploadedFile::fake()->image('check2.jpg', 800, 600),
        ])
        ->call('saveInspection')
        ->assertHasNoErrors();

    $ins = $e->inspections()->first();
    expect($ins->photos)->toHaveCount(2);            // ເກັບ ຫຼາຍ ໃບ
    expect($ins->photo_path)->toBe($ins->photos[0]); // ຮູບ ທຳອິດ = list
    expect($ins->allPhotos())->toHaveCount(2);
    foreach ($ins->photos as $p) {
        Storage::disk('public')->assertExists($p);
    }
});
