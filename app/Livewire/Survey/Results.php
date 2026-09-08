<?php

namespace App\Livewire\Survey;

use App\Models\Setting;
use App\Models\SurveyResponse;
use App\Models\Unit;
use App\Models\User;
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

    /** Manager-set target staff count (survey population). Null = auto (active users). */
    public ?int $targetStaff = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('reports.view'), 403);
        $this->targetStaff = Setting::get('survey')['target_staff'] ?? null;
    }

    /** Persist the manual target as it is edited (blank/0 = back to auto). */
    public function updatedTargetStaff(): void
    {
        abort_unless(auth()->user()->can('reports.view'), 403);
        $this->targetStaff = $this->targetStaff && $this->targetStaff > 0 ? (int) $this->targetStaff : null;
        Setting::put('survey', array_merge(Setting::get('survey'), ['target_staff' => $this->targetStaff]), auth()->id());
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

        // ── insights & recommendations (auto) ──
        $totalRatings = array_sum($dist);
        $t2b = $totalRatings ? (int) round(($dist[4] + $dist[5]) / $totalRatings * 100) : null;
        $b2b = $totalRatings ? (int) round(($dist[1] + $dist[2]) / $totalRatings * 100) : null;

        // per-question means (the 6 actionable section questions in scope)
        $qFields = match ($this->service) {
            'wh' => SurveyResponse::WH_FIELDS,
            'ie' => SurveyResponse::IE_FIELDS,
            default => [...SurveyResponse::WH_FIELDS, ...SurveyResponse::IE_FIELDS],
        };
        $qMeans = collect($qFields)->mapWithKeys(fn ($f) => [$f => $avgOf($f)])->filter(fn ($v) => $v !== null);

        $grandMean = $sectionMean(SurveyResponse::RATING_FIELDS);
        $insights = $this->buildInsights($grandMean, $t2b, $b2b, $qMeans, $sectionMean(SurveyResponse::WH_FIELDS), $sectionMean(SurveyResponse::IE_FIELDS), $total);

        // ── participation / response rate ──
        // denominator = active staff (respecting the unit filter); numerator = responses.
        $staffQ = User::where('status', 'active')
            ->when($this->unit_id, fn ($q, $v) => $q->where('unit_id', $v));
        $totalStaff = (clone $staffQ)->count();
        $staffByUnit = (clone $staffQ)->whereNotNull('unit_id')
            ->groupBy('unit_id')->selectRaw('unit_id, count(*) as c')->pluck('c', 'unit_id');
        $respByUnit = (clone $this->base())
            ->groupBy('unit_id')->selectRaw('unit_id, count(*) as c')->pluck('c', 'unit_id');
        $respByFreq = (clone $this->base())
            ->groupBy('frequency')->selectRaw('frequency, count(*) as c')->pluck('c', 'frequency');
        // overall denominator: manual target (whole-org) unless a unit filter is active
        $manualTarget = ! $this->unit_id && $this->targetStaff && $this->targetStaff > 0;
        $denominator = $manualTarget ? $this->targetStaff : $totalStaff;
        $responseRate = $denominator ? (int) round($total / $denominator * 100) : null;
        $units = Unit::orderBy('name')->get(['id', 'name']);
        $unitNames = $units->pluck('name', 'id');

        // recent comments
        $comments = (clone $this->base())
            ->where(fn ($q) => $q->whereNotNull('doing_well')->orWhereNotNull('improve'))
            ->with('unit:id,name')->latest()->limit(20)->get();

        return view('livewire.survey.results', [
            'units' => $units,
            'total' => $total,
            'avgOf' => $avgOf,
            'whMean' => $sectionMean(SurveyResponse::WH_FIELDS),
            'ieMean' => $sectionMean(SurveyResponse::IE_FIELDS),
            'overallWh' => $avgOf('overall_wh'),
            'overallIe' => $avgOf('overall_ie'),
            'grandMean' => $grandMean,
            'dist' => $dist,
            'trend' => $trend,
            'comments' => $comments,
            't2b' => $t2b,
            'b2b' => $b2b,
            'insights' => $insights,
            'totalStaff' => $totalStaff,
            'denominator' => $denominator,
            'manualTarget' => $manualTarget,
            'responseRate' => $responseRate,
            'staffByUnit' => $staffByUnit,
            'respByUnit' => $respByUnit,
            'respByFreq' => $respByFreq,
            'unitNames' => $unitNames,
        ]);
    }

    /** Score band for a 1–5 mean: [label, tailwind color name]. */
    public static function band(?float $m): array
    {
        return match (true) {
            $m === null => ['—', 'gray'],
            $m >= 4.5 => ['ດີເລີດ · Excellent', 'emerald'],
            $m >= 4.0 => ['ດີ · Good', 'green'],
            $m >= 3.5 => ['ພໍໃຊ້ · Fair', 'amber'],
            $m >= 3.0 => ['ຕ້ອງປັບປຸງ · Needs improvement', 'orange'],
            default => ['ວິກິດ · Critical', 'red'],
        };
    }

    /**
     * Auto interpretation + prioritized recommendations, in the spirit of PDCA
     * continuous improvement.
     *
     * @return array{verdict:array,recos:array<int,array{level:string,text:string}>}
     */
    private function buildInsights(?float $grand, ?int $t2b, ?int $b2b, $qMeans, ?float $whMean, ?float $ieMean, int $total): array
    {
        $L = SurveyResponse::LABELS;
        $recos = [];

        // 1. weaknesses — any question < 3.5, worst first (priority actions)
        $weak = $qMeans->filter(fn ($v) => $v < 3.5)->sort();
        foreach ($weak as $f => $m) {
            $lvl = $m < 3.0 ? 'critical' : 'priority';
            $recos[] = ['level' => $lvl, 'text' => "ຈຸດອ່ອນ: {$L[$f]} ({$m}/5) — ຫາສາເຫດຮາກ (5-Why) ແລ້ວວາງແຜນແກ້ໄຂ".($m < 3.0 ? ' ດ່ວນ' : '')];
        }
        // watch zone 3.5–3.99 (only if no critical/priority already flagged them)
        foreach ($qMeans->filter(fn ($v) => $v >= 3.5 && $v < 4.0)->sort() as $f => $m) {
            $recos[] = ['level' => 'watch', 'text' => "ເຝົ້າລະວັງ: {$L[$f]} ({$m}/5) — ໃກ້ເກນ, ຕິດຕາມ trend"];
        }

        // 2. strength — top question ≥ 4.0 → standardize (SOP)
        if ($qMeans->isNotEmpty()) {
            $topF = $qMeans->sortDesc()->keys()->first();
            $topV = $qMeans->get($topF);
            if ($topV >= 4.0) {
                $recos[] = ['level' => 'strength', 'text' => "ຈຸດແຂງ: {$L[$topF]} ({$topV}/5) — ຮັກສາໄວ້, ເຮັດ SOP ມາດຕະຖານ"];
            }
        }

        // 3. dissatisfaction alert (B2B)
        if ($b2b !== null && $b2b > 10) {
            $recos[] = ['level' => 'critical', 'text' => "ຄົນບໍ່ພໍໃຈ (1–2) = {$b2b}% (>10% ເກນ) — ຕ້ອງສືບສວນ ແລະ ແກ້ໄຂ"];
        }

        // 4. WH vs IE gap ≥ 0.5
        if ($whMean !== null && $ieMean !== null && abs($whMean - $ieMean) >= 0.5) {
            $lower = $whMean < $ieMean ? ['Warehouse', $whMean] : ['Import-Export', $ieMean];
            $diff = round(abs($whMean - $ieMean), 1);
            $recos[] = ['level' => 'watch', 'text' => "{$lower[0]} ({$lower[1]}) ຕ່ຳກວ່າ ອີກດ້ານ {$diff} ຄະແນນ — ໂຟກັດປັບປຸງ {$lower[0]}"];
        }

        // 5. sample-size caution
        if ($total > 0 && $total < 20) {
            $recos[] = ['level' => 'info', 'text' => "ຄຳຕອບ n={$total} ຍັງນ້ອຍ — ເກັບເພີ່ມ ເພື່ອຄວາມໜ້າເຊື່ອຖື (ແນະນຳ ≥30)"];
        }

        // 6. all good → sustain + raise target
        if ($grand !== null && $grand >= 4.0 && ($t2b ?? 0) >= 80 && $weak->isEmpty()) {
            $recos[] = ['level' => 'good', 'text' => 'ຜົນໂດຍລວມດີ — ຮັກສາມາດຕະຖານ, ຕັ້ງເປົ້າສູງຂຶ້ນ (≥4.5), ແບ່ງປັນ best practice ໃຫ້ທີມ'];
        }

        return [
            'verdict' => self::band($grand),
            't2bBand' => match (true) {
                $t2b === null => ['—', 'gray'],
                $t2b >= 85 => ['ດີເລີດ', 'emerald'],
                $t2b >= 80 => ['ດີ', 'green'],
                $t2b >= 70 => ['ພໍໃຊ້', 'amber'],
                default => ['ຕ້ອງແກ້', 'red'],
            },
            'recos' => $recos,
        ];
    }
}
