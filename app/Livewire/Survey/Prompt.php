<?php

namespace App\Livewire\Survey;

use App\Support\SurveyCampaign;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Small app-open popup shown to a logged-in user who has not yet answered the
 * currently-open survey campaign. Rendered in layouts.app (@auth). "Answered" is
 * decided server-side (SurveyCampaign); "later" is dismissed client-side for the
 * session, so it comes back next session until they answer or the campaign ends.
 */
class Prompt extends Component
{
    public function render(): View
    {
        return view('livewire.survey.prompt', [
            'show' => SurveyCampaign::shouldPrompt(auth()->user()),
            'campaignKey' => SurveyCampaign::key(),
        ]);
    }
}
