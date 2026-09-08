<?php

namespace App\Livewire\Survey;

use App\Models\SurveyResponse;
use App\Models\Unit;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Customer-satisfaction results dashboard. Filter by frequency, date range,
 * respondent unit, and service area (Warehouse / Import-Export). Gated by
 * reports.view (managers). Aggregation stays DB-portable (AVG + groupBy, and
 * the trend is grouped in PHP) so it runs on both MySQL and the sqlite tests.
 */
#[Layout('layouts.app')]
class Results extends Component
{
    #[Url]
    public string $frequency = '';

    #[Url]
    public ?int $unit_id = null;

    #[Url]
    public string $service = 'all';   // all | wh | ie

    #[Url]
    public ?string $from = null;

    #[Url]
    public ?string $to = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('reports.view'), 403);
    }

    public function resetFilters(): void
    {
        $this->reset(['frequency', 'unit_id', 'service', 'from', 'to']);
        $this->service = 'all';
    }

    private function base()
    {
        return SurveyResponse::query()->filter([
            'frequency' => $this->frequency ?: null,
            'unit_id' => $this->unit_id,
            'from' => $this->from,
            'to' => $this->to,
        ]);
    }

    /** Rating columns in scope for the chosen service area. */
    private function fields(): array
    {
        return match ($this->service) {
            'wh' => [...SurveyResponse::WH_FIELDS, 'overall_wh'],
            'ie' => [...SurveyResponse::IE_FIELDS, 'overall_ie'],
            default => SurveyResponse::RATING_FIELDS,
        };
    }

    public function render(): View
    {
        $total = (clone $this->base())->count();

        // averages per rating column (one query)
        $selects = array_map(fn ($f) => "AVG($f) as $f", SurveyResponse::RATING_FIELDS);
        $avg = (clone $this->base())->selectRaw(implode(',', $selects))->first();
        $avgOf = fn ($f) => $avg && $avg->$f !== null ? round((float) $avg->$f, 1) : null;

        // section means
        $sectionMean = function (array $fs) use ($avg) {
            $vals = collect($fs)->map(fn ($f) => $avg?->$f)->filter(fn ($v) => $v !== null);

            return $vals->isEmpty() ? null : round((float) $vals->avg(), 1);
        };

        // distribution 1–5 across in-scope fields
        $dist = array_fill(1, 5, 0);
        foreach ($this->fields() as $f) {
            $rows = (clone $this->base())->whereNotNull($f)->groupBy($f)
                ->selectRaw("$f as v, count(*) as c")->pluck('c', 'v');
            foreach ($rows as $v => $c) {
                if ($v >= 1 && $v <= 5) {
                    $dist[$v] += (int) $c;
                }
            }
        }

        // 6-month trend, grouped in PHP (DB-portable)
        $since = now()->subMonths(5)->startOfMonth();
        $trendRows = (clone $this->base())->where('created_at', '>=', $since)
            ->get(['created_at', 'overall_wh', 'overall_ie']);
        $months = collect(range(0, 5))->map(fn ($i) => $since->copy()->addMonths($i)->format('Y-m'));
        $trend = $months->map(function ($ym) use ($trendRows) {
            $in = $trendRows->filter(fn ($r) => $r->created_at->format('Y-m') === $ym);
            $wh = $in->pluck('overall_wh')->filter(fn ($v) => $v !== null);
            $ie = $in->pluck('overall_ie')->filter(fn ($v) => $v !== null);

            return [
                'label' => Carbon::createFromFormat('Y-m', $ym)->format('M'),
                'wh' => $wh->isEmpty() ? null : round((float) $wh->avg(), 2),
                'ie' => $ie->isEmpty() ? null : round((float) $ie->avg(), 2),
            ];
        })->values();

        // recent comments
        $comments = (clone $this->base())
            ->where(fn ($q) => $q->whereNotNull('doing_well')->orWhereNotNull('improve'))
            ->with('unit:id,name')->latest()->limit(20)->get();

        return view('livewire.survey.results', [
            'units' => Unit::orderBy('name')->get(['id', 'name']),
            'total' => $total,
            'avgOf' => $avgOf,
            'whMean' => $sectionMean(SurveyResponse::WH_FIELDS),
            'ieMean' => $sectionMean(SurveyResponse::IE_FIELDS),
            'overallWh' => $avgOf('overall_wh'),
            'overallIe' => $avgOf('overall_ie'),
            'grandMean' => $sectionMean(SurveyResponse::RATING_FIELDS),
            'dist' => $dist,
            'trend' => $trend,
            'comments' => $comments,
        ]);
    }
}
