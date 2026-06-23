<?php

namespace App\Livewire\Expo;

use App\Models\ExpoCompanyFile;
use App\Models\ExpoContact;
use App\Models\ExpoEvent;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Show extends Component
{
    use WithFileUploads;

    public ExpoEvent $record;

    public string $tab = 'companies';

    // ── company modal ──
    public bool $showCompany = false;

    public ?int $companyId = null;

    public array $cf = [];

    public string $companyFileKind = 'product';

    public array $companyFiles = [];

    // ── contact modal ──
    public bool $showContact = false;

    public ?int $contactId = null;

    public ?int $contactCompanyId = null;

    public array $kf = [];

    public $businessCard;

    // ── attendees opinions + overview ──
    public array $opinions = [];

    /** dropdown picker ໃນ tab ຜູ້ໄປ — ເພີ່ມເທື່ອລະຄົນ */
    public $pickUser = '';

    public string $feedback = '';

    public string $nextLocation = '';

    public string $nextProposal = '';

    // ── edit event meta ──
    public bool $showEvent = false;

    public array $ef = [];

    // ── delete ──
    public bool $showDelete = false;

    public string $deleteReason = '';

    public function mount(ExpoEvent $record): void
    {
        abort_unless(auth()->user()->can('expo.view'), 403);
        $this->record = $record;
        $this->feedback = $record->feedback ?? '';
        $this->nextLocation = $record->next_event_location ?? '';
        $this->nextProposal = $record->next_proposal ?? '';
        $this->opinions = $record->attendees()->pluck('opinion', 'id')->map(fn ($o) => $o ?? '')->all();
    }

    protected function canEdit(): bool
    {
        $u = auth()->user();

        return $u->is_super_admin || $u->can('expo.edit');
    }

    protected function guardEdit(): void
    {
        abort_unless($this->canEdit(), 403);
    }

    // ── edit event meta ──
    public function openEvent(): void
    {
        $this->guardEdit();
        $this->ef = [
            'title' => $this->record->title,
            'topic' => $this->record->topic,
            'background' => $this->record->background,
            'venue' => $this->record->venue,
            'city' => $this->record->city,
            'country' => $this->record->country,
            'address' => $this->record->address,
            'start_date' => $this->record->start_date?->toDateString(),
            'end_date' => $this->record->end_date?->toDateString(),
            'total_companies_at_expo' => $this->record->total_companies_at_expo,
        ];
        $this->resetErrorBag();
        $this->showEvent = true;
    }

    public function saveEvent(): void
    {
        $this->guardEdit();
        $this->validate([
            'ef.title' => ['required', 'string', 'max:256'],
            'ef.topic' => ['nullable', 'string', 'max:1000'],
            'ef.background' => ['nullable', 'string', 'max:1000'],
            'ef.venue' => ['nullable', 'string', 'max:256'],
            'ef.city' => ['nullable', 'string', 'max:128'],
            'ef.country' => ['nullable', 'string', 'max:128'],
            'ef.address' => ['nullable', 'string', 'max:512'],
            'ef.start_date' => ['required', 'date'],
            'ef.end_date' => ['nullable', 'date', 'after_or_equal:ef.start_date'],
            'ef.total_companies_at_expo' => ['nullable', 'integer', 'min:0'],
        ], [], ['ef.title' => 'ຊື່ງານ', 'ef.start_date' => 'ວັນທີເລີ່ມ']);

        $this->record->update([
            'title' => $this->ef['title'],
            'topic' => $this->ef['topic'] ?: null,
            'background' => $this->ef['background'] ?: null,
            'venue' => $this->ef['venue'] ?: null,
            'city' => $this->ef['city'] ?: null,
            'country' => $this->ef['country'] ?: null,
            'address' => $this->ef['address'] ?: null,
            'start_date' => $this->ef['start_date'],
            'end_date' => $this->ef['end_date'] ?: null,
            'total_companies_at_expo' => $this->ef['total_companies_at_expo'] !== '' ? $this->ef['total_companies_at_expo'] : null,
            'updated_by' => auth()->id(),
        ]);

        session()->flash('ok', '✓ ບັນທຶກ ຂໍ້ມູນງານ');
        $this->reset(['showEvent', 'ef']);
        $this->record->refresh();
    }

    // ── company ──
    public function openCompany(?int $id = null): void
    {
        $this->guardEdit();
        $this->reset(['cf', 'companyFiles', 'companyFileKind']);
        $this->companyFileKind = 'product';
        if ($id && $c = $this->record->companies()->find($id)) {
            $this->companyId = $c->id;
            $this->cf = $c->only(['name', 'country', 'address', 'website', 'email', 'phone', 'other_contacts', 'products', 'benefit', 'interest_level', 'score']);
        } else {
            $this->companyId = null;
            $this->cf = ['interest_level' => 'warm', 'score' => 3];
        }
        $this->resetErrorBag();
        $this->showCompany = true;
    }

    public function saveCompany(): void
    {
        $this->guardEdit();
        $this->validate([
            'cf.name' => ['required', 'string', 'max:256'],
            'cf.country' => ['nullable', 'string', 'max:128'],
            'cf.website' => ['nullable', 'string', 'max:256'],
            'cf.email' => ['nullable', 'string', 'max:256'],
            'cf.phone' => ['nullable', 'string', 'max:64'],
            'cf.products' => ['nullable', 'string', 'max:1000'],
            'cf.benefit' => ['nullable', 'string', 'max:1000'],
            'cf.interest_level' => ['required', 'in:hot,warm,cold'],
            'cf.score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'companyFiles.*' => ['image', 'max:5120'],
        ], [], ['cf.name' => 'ຊື່ບໍລິສັດ']);

        if ($this->companyId) {
            $c = $this->record->companies()->findOrFail($this->companyId);
            $c->update($this->cf);
        } else {
            $c = $this->record->companies()->create($this->cf + ['sort_order' => ($this->record->companies()->max('sort_order') ?? -1) + 1]);
        }

        foreach (array_values($this->companyFiles) as $i => $file) {
            $path = $file->store("expo/{$this->record->id}/{$c->id}", 'public');
            $c->files()->create(['kind' => $this->companyFileKind, 'path' => $path, 'sort_order' => $i]);
        }

        session()->flash('ok', '✓ ບັນທຶກ ບໍລິສັດ');
        $this->reset(['showCompany', 'cf', 'companyFiles', 'companyId']);
        $this->record->refresh();
    }

    public function deleteCompany(int $id): void
    {
        $this->guardEdit();
        $c = $this->record->companies()->with('files')->find($id);
        if ($c) {
            foreach ($c->files as $f) {
                Storage::disk('public')->delete($f->path);
            }
            $c->delete();
            $this->record->refresh();
        }
    }

    public function removeCompanyFile(int $fileId): void
    {
        $this->guardEdit();
        $f = ExpoCompanyFile::whereHas('company', fn ($q) => $q->where('event_id', $this->record->id))->find($fileId);
        if ($f) {
            Storage::disk('public')->delete($f->path);
            $f->delete();
            $this->record->refresh();
        }
    }

    // ── contact ──
    public function openContact(int $companyId, ?int $id = null): void
    {
        $this->guardEdit();
        $this->reset(['kf', 'businessCard']);
        $this->contactCompanyId = $companyId;
        if ($id && $k = ExpoContact::where('company_id', $companyId)->find($id)) {
            $this->contactId = $k->id;
            $this->kf = $k->only(['name', 'role', 'title', 'email', 'phone', 'app_contact', 'notes']);
        } else {
            $this->contactId = null;
            $this->kf = ['role' => 'direct_employee'];
        }
        $this->resetErrorBag();
        $this->showContact = true;
    }

    public function saveContact(): void
    {
        $this->guardEdit();
        $this->validate([
            'kf.name' => ['required', 'string', 'max:256'],
            'kf.role' => ['required', 'in:agent,representative,direct_employee,other'],
            'kf.title' => ['nullable', 'string', 'max:256'],
            'kf.email' => ['nullable', 'string', 'max:256'],
            'kf.phone' => ['nullable', 'string', 'max:64'],
            'kf.app_contact' => ['nullable', 'string', 'max:128'],
            'businessCard' => ['nullable', 'image', 'max:5120'],
        ], [], ['kf.name' => 'ຊື່ຜູ້ຕິດຕໍ່']);

        $company = $this->record->companies()->findOrFail($this->contactCompanyId);
        $attrs = $this->kf;
        if ($this->businessCard) {
            $attrs['business_card_path'] = $this->businessCard->store("expo/{$this->record->id}/{$company->id}/cards", 'public');
        }

        if ($this->contactId) {
            $company->contacts()->whereKey($this->contactId)->first()?->update($attrs);
        } else {
            $company->contacts()->create($attrs + ['sort_order' => ($company->contacts()->max('sort_order') ?? -1) + 1]);
        }

        session()->flash('ok', '✓ ບັນທຶກ ຜູ້ຕິດຕໍ່');
        $this->reset(['showContact', 'kf', 'businessCard', 'contactId']);
        $this->record->refresh();
    }

    public function deleteContact(int $id): void
    {
        $this->guardEdit();
        $k = ExpoContact::whereHas('company', fn ($q) => $q->where('event_id', $this->record->id))->find($id);
        if ($k) {
            if ($k->business_card_path) {
                Storage::disk('public')->delete($k->business_card_path);
            }
            $k->delete();
            $this->record->refresh();
        }
    }

    // ── attendees: ເພີ່ມ/ລຶບ ຜູ້ໄປ ──
    public function updatedPickUser($value): void
    {
        $this->guardEdit();
        $uid = (int) $value;
        $this->pickUser = '';
        if (! $uid) {
            return;
        }
        if (! $this->record->attendees()->where('user_id', $uid)->exists()) {
            $u = \App\Models\User::find($uid);
            $this->record->attendees()->create([
                'user_id' => $uid,
                'user_name' => $u?->display_name ?? $u?->email ?? 'user '.$uid,
            ]);
            $this->record->refresh();
            $this->syncOpinions();
        }
    }

    public function removeAttendee(int $attendeeId): void
    {
        $this->guardEdit();
        $this->record->attendees()->whereKey($attendeeId)->delete();
        $this->record->refresh();
        $this->syncOpinions();
    }

    protected function syncOpinions(): void
    {
        $this->opinions = $this->record->attendees()->pluck('opinion', 'id')->map(fn ($o) => $o ?? '')->all();
    }

    // ── attendees opinions ──
    public function saveOpinions(): void
    {
        $this->guardEdit();
        foreach ($this->opinions as $aid => $op) {
            $this->record->attendees()->whereKey($aid)->update(['opinion' => $op ?: null]);
        }
        session()->flash('ok', '✓ ບັນທຶກ ຄວາມຄິດເຫັນ');
        $this->record->refresh();
    }

    // ── overview / next ──
    public function saveOverview(): void
    {
        $this->guardEdit();
        $this->record->update([
            'feedback' => $this->feedback ?: null,
            'next_event_location' => $this->nextLocation ?: null,
            'next_proposal' => $this->nextProposal ?: null,
            'updated_by' => auth()->id(),
        ]);
        session()->flash('ok', '✓ ບັນທຶກ ພາບລວມ');
    }

    public function finalize(): void
    {
        $this->guardEdit();
        $this->record->update(['status' => 'finalized', 'updated_by' => auth()->id()]);
    }

    public function reopen(): void
    {
        $this->guardEdit();
        $this->record->update(['status' => 'draft', 'updated_by' => auth()->id()]);
    }

    // ── delete ──
    public function openDelete(): void
    {
        $this->guardEdit();
        $this->reset(['deleteReason']);
        $this->resetErrorBag();
        $this->showDelete = true;
    }

    public function deleteRecord()
    {
        $this->guardEdit();
        $this->validate(['deleteReason' => ['required', 'string', 'max:500']]);
        $this->record->forceFill(['deleted_reason' => $this->deleteReason, 'deleted_by' => auth()->id()])->save();
        $this->record->delete();
        session()->flash('ok', '✓ ລຶບ');

        return $this->redirect(route('expo'), navigate: true);
    }

    public function render(): View
    {
        $this->record->load(['attendees.user', 'companies.contacts', 'companies.files']);

        return view('livewire.expo.show', [
            'record' => $this->record,
            'editable' => $this->canEdit(),
            'availableUsers' => \App\Models\User::whereNotIn('id', $this->record->attendees->pluck('user_id'))
                ->orderBy('display_name')->get(['id', 'display_name', 'email']),
        ]);
    }
}
