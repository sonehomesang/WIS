<?php

use App\Imports\InventoryCsvImporter;
use App\Livewire\Inventory\Index;
use App\Models\InventoryItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function writeCsv(string $content): string
{
    $path = tempnam(sys_get_temp_dir(), 'inv').'.csv';
    file_put_contents($path, $content);

    return $path;
}

test('importer inserts valid rows, skips blank name, normalizes bad status', function () {
    $path = writeCsv("name,category,quantity,status\nDrill,Tools,5,available\n,Tools,3,available\nHammer,Tools,2,bad-status\n");

    $result = (new InventoryCsvImporter)->import($path, null);

    expect($result['imported'])->toBe(2);
    expect($result['skipped'])->toBe(1);
    expect(InventoryItem::where('name', 'Drill')->first()->quantity)->toBe(5);
    expect(InventoryItem::where('name', 'Hammer')->first()->status)->toBe('available');
    unlink($path);
});

test('importer dedups by provided slug across re-runs (idempotent)', function () {
    $path = writeCsv("slug,name,quantity\nwibt-1,Drill,5\n");

    (new InventoryCsvImporter)->import($path, null);
    $second = (new InventoryCsvImporter)->import($path, null);

    expect($second['imported'])->toBe(0);
    expect($second['skipped'])->toBe(1);
    expect(InventoryItem::where('slug', 'wibt-1')->count())->toBe(1);
    unlink($path);
});

test('importer auto-generates unique slugs for same-name rows', function () {
    $path = writeCsv("name,quantity\nDrill,5\nDrill,9\n");

    $result = (new InventoryCsvImporter)->import($path, null);

    expect($result['imported'])->toBe(2);
    expect(InventoryItem::where('name', 'Drill')->count())->toBe(2);
    unlink($path);
});

test('importer honors a provided slug column', function () {
    $path = writeCsv("slug,name,quantity\nwibt-123,Drill,5\n");

    (new InventoryCsvImporter)->import($path, null);

    expect(InventoryItem::where('slug', 'wibt-123')->exists())->toBeTrue();
    unlink($path);
});

test('a viewer without create permission cannot open import', function () {
    $user = User::factory()->create(['is_super_admin' => false]);
    $user->givePermissionTo('inventory.view'); // view ໄດ້ ແຕ່ບໍ່ມີ create
    $this->actingAs($user);

    Livewire::test(Index::class)->call('openImport')->assertForbidden();
});

test('super admin can import via livewire upload', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
    $file = UploadedFile::fake()->createWithContent('items.csv', "name,quantity\nWrench,4\n");

    Livewire::test(Index::class)
        ->call('openImport')
        ->set('csvFile', $file)
        ->call('importCsv')
        ->assertHasNoErrors();

    expect(InventoryItem::where('name', 'Wrench')->first()->quantity)->toBe(4);
});
