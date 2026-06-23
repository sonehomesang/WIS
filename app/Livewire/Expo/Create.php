<?php

namespace App\Livewire\Expo;

use App\Models\User;
use App\Services\ExpoService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    public string $title = '';

    public string $topic = '';

    public string $background = '';

    public string $venue = '';

    public string $city = '';

    public string $country = '';

    public string $address = '';

    public string $start_date;

    public string $end_date = '';

    public ?int $total_companies_at_expo = null;

    /** @var int[] */
    public array $attendee_ids = [];

    /** dropdown picker — ເລືອກເພີ່ມເທື່ອລະຄົນ */
    public $pickUser = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('expo.create'), 403);
        $this->start_date = Carbon::today()->toDateString();
    }

    public function updatedPickUser($value): void
    {
        $id = (int) $value;
        if ($id && ! in_array($id, $this->attendee_ids, true)) {
            $this->attendee_ids[] = $id;
        }
        $this->pickUser = '';
    }

    public function removeAttendee(int $id): void
    {
        $this->attendee_ids = array_values(array_filter($this->attendee_ids, fn ($x) => (int) $x !== $id));
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('expo.create'), 403);

        $this->validate([
            'title' => ['required', 'string', 'max:256'],
            'topic' => ['nullable', 'string', 'max:1000'],
            'venue' => ['nullable', 'string', 'max:256'],
            'city' => ['nullable', 'string', 'max:128'],
            'country' => ['nullable', 'string', 'max:128'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'total_companies_at_expo' => ['nullable', 'integer', 'min:0'],
            'attendee_ids' => ['array'],
            'attendee_ids.*' => ['exists:users,id'],
        ], [], ['title' => 'ຊື່ງານ']);

        $event = app(ExpoService::class)->create([
            'title' => $this->title,
            'topic' => $this->topic ?: null,
            'background' => $this->background ?: null,
            'venue' => $this->venue ?: null,
            'city' => $this->city ?: null,
            'country' => $this->country ?: null,
            'address' => $this->address ?: null,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date ?: null,
            'total_companies_at_expo' => $this->total_companies_at_expo,
            'attendee_ids' => $this->attendee_ids,
        ], auth()->user());

        session()->flash('ok', "ສ້າງ {$event->expo_number} ແລ້ວ");
        $this->redirectRoute('expo.show', $event, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.expo.create', [
            'users' => User::orderBy('display_name')->get(['id', 'display_name', 'email']),
        ]);
    }
}
