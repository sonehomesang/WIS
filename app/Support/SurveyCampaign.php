<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Campaign state for the satisfaction survey — admin sets active + a start/end
 * window on the results dashboard. Drives the app-open popup and gates the
 * public form. All config lives in Setting('survey').
 */
class SurveyCampaign
{
    /** @return array{active:bool,start_date:?string,end_date:?string,identify:bool,target_staff:?int} */
    public static function settings(): array
    {
        $s = Setting::get('survey', []) ?: [];

        return [
            'active' => (bool) ($s['active'] ?? false),
            'start_date' => $s['start_date'] ?? null,
            'end_date' => $s['end_date'] ?? null,
            'identify' => (bool) ($s['identify_respondents'] ?? false),
            'target_staff' => $s['target_staff'] ?? null,
        ];
    }

    /** Is the survey switched on AND is today inside the (optional) date window? */
    public static function isOpen(): bool
    {
        $s = self::settings();
        if (! $s['active']) {
            return false;
        }
        $today = Carbon::today();
        if ($s['start_date'] && $today->lt(Carbon::parse($s['start_date']))) {
            return false;
        }
        if ($s['end_date'] && $today->gt(Carbon::parse($s['end_date']))) {
            return false;
        }

        return true;
    }

    /** Show the respondent's name + department in the results? */
    public static function identifyRespondents(): bool
    {
        return self::settings()['identify'];
    }

    /** Has this user already answered the current campaign (since its start)? */
    public static function userAnswered(User $user): bool
    {
        $start = self::settings()['start_date'];

        return SurveyResponse::where('user_id', $user->id)
            ->when($start, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->exists();
    }

    /** Should this user be prompted with the popup right now? */
    public static function shouldPrompt(?User $user): bool
    {
        return (bool) $user && self::isOpen() && ! self::userAnswered($user);
    }

    /** A token that changes when the campaign window changes — for per-campaign client dismissal. */
    public static function key(): string
    {
        $s = self::settings();

        return md5(($s['start_date'] ?? '').'|'.($s['end_date'] ?? ''));
    }
}
