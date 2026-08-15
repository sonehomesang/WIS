<?php

use App\Livewire\Disposal\Show;
use App\Models\User;
use App\Notifications\DisposalEndorsementRequest;
use App\Services\DisposalService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->svc = app(DisposalService::class);
    $this->admin = User::factory()->create(['is_super_admin' => true]);
});

function reviewRecord(DisposalService $svc, User $admin, User $endorser, string $role = 'committee')
{
    $r = $svc->createDraft(['items' => [['source_type' => 'new', 'item_name' => 'X', 'qty' => 1]]], $admin);
    $svc->assignEndorsers($r, [$role => ['user_id' => $endorser->id]], $admin);
    $svc->transition($r, 'submit', $admin);

    return $r->refresh();
}

test('assigning endorsers emails the newly-assigned user a link', function () {
    Notification::fake();
    $endorser = User::factory()->create();
    $r = reviewRecord($this->svc, $this->admin, $endorser);

    $this->svc->notifyPendingEndorsers($r);

    Notification::assertSentTo($endorser, DisposalEndorsementRequest::class);
});

test('only the assigned user may endorse their row — a different user is forbidden', function () {
    $endorser = User::factory()->create();
    $r = reviewRecord($this->svc, $this->admin, $endorser);

    // the assigned user can
    expect($r->fresh()->status)->toBe('in_review');
    $r = $this->svc->endorse($r, 'committee', $endorser, ['recommendation' => 'ຂາຍ ເສດ', 'comment' => 'ok']);

    // sole endorser signed → approved
    expect($r->status)->toBe('approved');
    $s = $r->signoffs()->where('role_key', 'committee')->first();
    expect($s->signed_at)->not->toBeNull();
    expect($s->recommendation)->toBe('ຂາຍ ເສດ');
});

test('a non-assigned user cannot endorse (service guards it)', function () {
    $endorser = User::factory()->create();
    $stranger = User::factory()->create();
    $r = reviewRecord($this->svc, $this->admin, $endorser);

    expect(fn () => $this->svc->endorse($r, 'committee', $stranger, []))
        ->toThrow(Illuminate\Validation\ValidationException::class);
    expect($r->fresh()->status)->toBe('in_review');
});

test('the Show component only lets the assigned user see the endorse action', function () {
    $endorser = User::factory()->create();
    $endorser->givePermissionTo('disposal.view');
    $stranger = User::factory()->create(['is_super_admin' => true]);   // sees record but not assigned
    $r = reviewRecord($this->svc, $this->admin, $endorser);

    actingAs($endorser);
    expect(Livewire::test(Show::class, ['record' => $r])->instance()->canEndorse('committee'))->toBeTrue();

    // a broad-role viewer (can open the record) who is NOT the assignee → no endorse action
    $viewer = User::factory()->create();
    $viewer->syncRoles(['warehouse_staff']);
    actingAs($viewer);
    expect(Livewire::test(Show::class, ['record' => $r])->instance()->canEndorse('committee'))->toBeFalse();
});
