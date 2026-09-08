<?php

namespace App\Livewire\Survey;

use App\Models\SurveyResponse;
use App\Models\Unit;
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

    public function mount(): void
    {
        if ($u = auth()->user()) {
            $this->unit_id = $u->unit_id;
        }
    }

    protected function rules(): array
    {
        $rating = ['nullable', 'integer', 'between:1,5'];

        return [
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
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
        $data = $this->validate();

        // require at least one rating so an empty form can't be submitted
        $hasRating = collect(SurveyResponse::RATING_FIELDS)->contains(fn ($f) => $this->{$f} !== null);
        if (! $hasRating) {
            $this->addError('overall_wh', 'ກະລຸນາ ໃຫ້ຄະແນນ ຢ່າງໜ້ອຍ 1 ຂໍ້.');

            return;
        }

        $data['user_id'] = auth()->id();
        // logged-in users always use their own unit
        if (auth()->check()) {
            $data['unit_id'] = auth()->user()->unit_id;
        }

        SurveyResponse::create($data);
        $this->done = true;
    }

    public function render(): View
    {
        return view('livewire.survey.form', [
            'units' => Unit::orderBy('name')->get(['id', 'name']),
            'isGuest' => ! auth()->check(),
        ]);
    }
}
