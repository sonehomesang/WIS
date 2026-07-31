<?php

use App\Livewire\AreaInspection\Index;
use App\Models\AreaInspection;
use App\Models\AreaInspectionTemplate;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('warehouse staff can record an area inspection with C/NC/NA from a template', function () {
    $u = User::factory()->create();
    $u->syncRoles(['warehouse_staff']);
    actingAs($u);

    $t = AreaInspectionTemplate::create([
        'name' => 'Chem', 'frequency' => 'monthly', 'is_active' => true,
        'items' => [['label' => 'Ventilation', 'requirement' => 'ok'], ['label' => 'Lighting', 'requirement' => 'ok']],
    ]);

    Livewire::test(Index::class)
        ->call('newInspection')
        ->set('fTemplateId', $t->id)                 // .live → ໂຫຼດ checklist ຈາກ ແມ່ແບບ
        ->set('fLocationLabel', 'Chemical Room 2')
        ->set('checklist.0.status', 'C')
        ->set('checklist.1.status', 'NC')
        ->set('checklist.1.observation', 'ໄຟ ດັບ')
        ->call('save')
        ->assertHasNoErrors();

    $r = AreaInspection::first();
    expect($r)->not->toBeNull();
    expect($r->location_label)->toBe('Chemical Room 2');
    expect($r->result)->toBe('has_nc');              // ມີ NC → has_nc
    expect($r->ncCount())->toBe(1);
    expect($r->checklist)->toHaveCount(2);
    expect($r->next_due_date)->not->toBeNull();
});

test('a non-permitted user cannot open area inspection', function () {
    $u = User::factory()->create();
    $u->syncRoles(['requester']);   // ບໍ່ ມີ area_inspection.view
    actingAs($u);
    Livewire::test(Index::class)->assertForbidden();
});

test('a manager can acknowledge an area inspection', function () {
    $admin = User::factory()->create(['is_super_admin' => true, 'display_name' => 'Boss']);
    actingAs($admin);
    $r = AreaInspection::create([
        'inspection_number' => 'AI2026-0001', 'location_label' => 'X', 'inspected_on' => now()->toDateString(),
        'frequency' => 'monthly', 'checklist' => [['label' => 'A', 'status' => 'C']], 'result' => 'compliant',
    ]);

    Livewire::test(Index::class)->call('acknowledge', $r->id);
    expect($r->fresh()->acknowledged_by_name)->toBe('Boss');
});

test('a manager can create, edit and deactivate an area checklist template in-app', function () {
    $u = User::factory()->create();
    $u->syncRoles(['warehouse_staff']);   // has area_inspection create/edit/activate/deactivate
    actingAs($u);

    Livewire::test(Index::class)
        ->set('tab', 'templates')
        ->call('newTemplate')
        ->set('tName', 'Warehouse Weekly')
        ->set('tFrequency', 'weekly')
        ->set('tItems.0.label', 'Fire Exit')
        ->set('tItems.0.requirement', 'Unlocked and marked')
        ->call('addTemplateItem')
        ->set('tItems.1.label', 'Housekeeping')
        ->call('saveTemplate')
        ->assertHasNoErrors();

    $t = AreaInspectionTemplate::where('name', 'Warehouse Weekly')->first();
    expect($t)->not->toBeNull()
        ->and($t->frequency)->toBe('weekly')
        ->and(count($t->normalizedItems()))->toBe(2);   // ຂໍ້ ວ່າງ ຖືກ ຄັດ ອອກ

    Livewire::test(Index::class)
        ->call('editTemplate', $t->id)
        ->set('tName', 'Warehouse Weekly v2')
        ->call('saveTemplate')
        ->assertHasNoErrors();
    expect($t->fresh()->name)->toBe('Warehouse Weekly v2');

    Livewire::test(Index::class)->call('toggleTemplateActive', $t->id);
    expect($t->fresh()->is_active)->toBeFalse();
});

test('evidence photo is kept only for NC items; overview photos are stored + stamped', function () {
    Storage::fake('public');
    $u = User::factory()->create();
    $u->syncRoles(['warehouse_staff']);
    actingAs($u);

    $t = AreaInspectionTemplate::create([
        'name' => 'X', 'frequency' => 'monthly', 'is_active' => true,
        'items' => [['label' => 'A', 'requirement' => ''], ['label' => 'B', 'requirement' => '']],
    ]);

    Livewire::test(Index::class)
        ->call('newInspection')
        ->set('fTemplateId', $t->id)
        ->set('fLocationLabel', 'Room X')
        ->set('checklist.0.status', 'C')
        ->set('checklist.1.status', 'NC')
        ->set('photos.0', UploadedFile::fake()->image('should-be-ignored.jpg'))   // C ຂໍ້ — ບໍ່ ເກັບ
        ->set('photos.1', UploadedFile::fake()->image('evidence.jpg'))            // NC ຂໍ້ — ເກັບ
        ->set('overviewPhotos', [UploadedFile::fake()->image('overview.jpg')])
        ->call('save')
        ->assertHasNoErrors();

    $r = AreaInspection::first();
    expect($r->checklist[0]['photo'])->toBeNull()          // C = ບໍ່ ມີ ຮູບ
        ->and($r->checklist[1]['photo'])->not->toBeNull()  // NC = ມີ ຮູບ ຫຼັກຖານ
        ->and($r->overview_photos)->toHaveCount(1);
    Storage::disk('public')->assertExists($r->checklist[1]['photo']);
    Storage::disk('public')->assertExists($r->overview_photos[0]);
});

test('newInspection clears overview photos so stale ones do not leak into a new record', function () {
    Storage::fake('public');
    $u = User::factory()->create();
    $u->syncRoles(['warehouse_staff']);
    actingAs($u);

    $t = AreaInspectionTemplate::create([
        'name' => 'X', 'frequency' => 'monthly', 'is_active' => true,
        'items' => [['label' => 'A', 'requirement' => '']],
    ]);

    Livewire::test(Index::class)
        ->call('newInspection')
        ->set('fTemplateId', $t->id)
        ->set('overviewPhotos', [UploadedFile::fake()->image('abandoned.jpg')])  // ຕິດ ຮູບ ແລ້ວ ຍົກເລີກ
        ->call('newInspection')                                                   // ເປີດ ໃໝ່ → ຕ້ອງ ລ້າງ
        ->set('fTemplateId', $t->id)
        ->set('fLocationLabel', 'Room Fresh')
        ->set('checklist.0.status', 'C')
        ->call('save')
        ->assertHasNoErrors();

    $r = AreaInspection::first();
    expect($r->location_label)->toBe('Room Fresh')
        ->and($r->overview_photos)->toHaveCount(0);   // ຮູບ ເກົ່າ ບໍ່ ຄ້າງ ມາ
});

test('overview photos are capped at 3 server-side', function () {
    Storage::fake('public');
    $u = User::factory()->create();
    $u->syncRoles(['warehouse_staff']);
    actingAs($u);

    $t = AreaInspectionTemplate::create([
        'name' => 'X', 'frequency' => 'monthly', 'is_active' => true,
        'items' => [['label' => 'A', 'requirement' => '']],
    ]);

    Livewire::test(Index::class)
        ->call('newInspection')
        ->set('fTemplateId', $t->id)
        ->set('fLocationLabel', 'Room X')
        ->set('checklist.0.status', 'C')
        ->set('overviewPhotos', [
            UploadedFile::fake()->image('1.jpg'), UploadedFile::fake()->image('2.jpg'),
            UploadedFile::fake()->image('3.jpg'), UploadedFile::fake()->image('4.jpg'),
        ])
        ->call('save')
        ->assertHasErrors(['overviewPhotos']);   // ເກີນ 3 → ບໍ່ ຜ່ານ validation

    expect(AreaInspection::count())->toBe(0);
});

test('the inspection PDF downloads for a permitted user, 403 for others', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $r = AreaInspection::create([
        'inspection_number' => 'AI2026-0009', 'location_label' => 'Room Z', 'inspected_on' => now()->toDateString(),
        'frequency' => 'monthly', 'inspectors' => ['Phouvanh'], 'result' => 'has_nc',
        'checklist' => [['label' => 'A', 'requirement' => 'x', 'status' => 'NC', 'observation' => 'fix', 'photo' => null]],
    ]);

    actingAs($admin);
    $this->get(route('area-inspection.pdf', $r))->assertOk();

    $other = User::factory()->create();
    $other->syncRoles(['requester']);   // ບໍ່ ມີ area_inspection.view
    actingAs($other);
    $this->get(route('area-inspection.pdf', $r))->assertForbidden();
});
