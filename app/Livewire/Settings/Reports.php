<?php

namespace App\Livewire\Settings;

use App\Models\BorrowRecord;
use App\Models\DepositRecord;
use App\Models\DiscrepancyAdvice;
use App\Models\ExpoEvent;
use App\Models\MaterialRequest;
use App\Models\OutwardsGoodsAdvice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
class Reports extends Component
{
    public string $from = '';

    public string $to = '';

    /** label => model class. */
    public const MODULES = [
        'Borrow' => BorrowRecord::class,
        'Request' => MaterialRequest::class,
        'Deposit' => DepositRecord::class,
        'DA Claims' => DiscrepancyAdvice::class,
        'OGA' => OutwardsGoodsAdvice::class,
        'Expo' => ExpoEvent::class,
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('reports.view'), 403);
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
    }

    /** @return array<int,array{label:string,total:int,byStatus:array<string,int>}> */
    protected function summary(): array
    {
        $out = [];
        foreach (self::MODULES as $label => $model) {
            /** @var Builder $q */
            $q = $model::query()
                ->when($this->from !== '', fn ($w) => $w->whereDate('created_at', '>=', $this->from))
                ->when($this->to !== '', fn ($w) => $w->whereDate('created_at', '<=', $this->to));

            $byStatus = (clone $q)->select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status')->all();
            $out[] = ['label' => $label, 'total' => array_sum($byStatus), 'byStatus' => $byStatus];
        }

        return $out;
    }

    public function export(): StreamedResponse
    {
        abort_unless(auth()->user()->can('reports.view'), 403);
        $summary = $this->summary();
        $range = ($this->from ?: '—').' → '.($this->to ?: '—');

        return response()->streamDownload(function () use ($summary, $range) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Report range', $range]);
            fputcsv($out, []);
            fputcsv($out, ['Module', 'Status', 'Count']);
            foreach ($summary as $m) {
                if (! $m['byStatus']) {
                    fputcsv($out, [$m['label'], '(ບໍ່ມີ)', 0]);

                    continue;
                }
                foreach ($m['byStatus'] as $status => $count) {
                    fputcsv($out, [$m['label'], $status, $count]);
                }
                fputcsv($out, [$m['label'].' — TOTAL', '', $m['total']]);
            }
            fclose($out);
        }, 'report-'.now()->format('Ymd-Hi').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function render(): View
    {
        return view('livewire.settings.reports', ['summary' => $this->summary()]);
    }
}
