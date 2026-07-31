<?php

use App\Livewire\AreaInspection\Index;
use App\Models\AreaInspection;
use App\Models\AreaInspectionTemplate;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
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
