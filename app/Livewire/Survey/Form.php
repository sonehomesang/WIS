<?php

namespace App\Livewire\Survey;

use App\Models\Department;
use App\Models\SurveyResponse;
use App\Models\Unit;
use App\Support\SurveyCampaign;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Public customer-satisfaction survey (Warehouse & Import-Export).
 * Works for anonymous respondents (pick your unit) and logged-in WH users
 * (unit + user captured automatically). Ratings 1–5, all optional so people
 * rate only the services they use — but at least one rating is required.
 */
#[Layout('layouts.survey')]
class Form extends Component
{
    public ?int $unit_id = null;

    public ?int $department_id = null;

    public ?string $frequency = null;

    public ?int $wh_receiving = null;

    public ?int $wh_condition = null;

    public ?int $wh_storage = null;

    public ?int $ie_customs = null;

    public ?int $ie_communication = null;

    public ?int $ie_urgent = null;

    public ?int $overall_wh = null;

    public ?int $overall_ie = null;

    public ?string $doing_well = null;

    public ?string $improve = null;

    public bool $done = false;

    /** Campaign not open (inactive or outside the date window) → show a notice. */
    public bool $closed = false;

    public function mount(): void
    {
        $this->closed = ! SurveyCampaign::isOpen();
        if ($u = auth()->user()) {
            $this->unit_id = $u->unit_id;
            $this->department_id = $u->department_id;
        }
    }

    /** Guest changed unit → clear a now-mismatched department pick. */
    public function updatedUnitId(): void
    {
        if (! auth()->check()) {
            $this->department_id = null;
        }
    }

    protected function rules(): array
    {
        $rating = ['nullable', 'integer', 'between:1,5'];

        return [
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'frequency' => ['required', 'in:'.implode(',', SurveyResponse::FREQUENCIES)],
            'wh_receiving' => $rating, 'wh_condition' => $rating, 'wh_storage' => $rating,
            'ie_customs' => $rating, 'ie_communication' => $rating, 'ie_urgent' => $rating,
            'overall_wh' => $rating, 'overall_ie' => $rating,
            'doing_well' => ['nullable', 'string', 'max:2000'],
            'improve' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function messages(): array
    {
        return ['frequency.required' => 'ກະລຸນາ ເລືອກ ຄວາມຖີ່ ໃນການຕິດຕໍ່.'];
    }

    public function submit(): void
    {
        if ($this->closed || ! SurveyCampaign::isOpen()) {
            $this->closed = true;

            return;   // campaign closed — no submissions accepted
        }

        $data = $this->validate();

        // require at least one rating so an empty form can't be submitted
        $hasRating = collect(SurveyResponse::RATING_FIELDS)->contains(fn ($f) => $this->{$f} !== null);
        if (! $hasRating) {
            $this->addError('overall_wh', 'ກະລຸນາ ໃຫ້ຄະແນນ ຢ່າງໜ້ອຍ 1 ຂໍ້.');

            return;
        }

        $data['user_id'] = auth()->id();
        // logged-in users always use their own unit + department; guests may pick.
        if ($u = auth()->user()) {
            $data['unit_id'] = $u->unit_id;
            $data['department_id'] = $u->department_id;
        }

        SurveyResponse::create($data);
        $this->done = true;
    }

    public function render(): View
    {
        return view('livewire.survey.form', [
            'units' => Unit::orderBy('name')->get(['id', 'name']),
            'departments' => $this->unit_id
                ? Department::where('unit_id', $this->unit_id)->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                : collect(),
            'isGuest' => ! auth()->check(),
        ]);
    }
}
