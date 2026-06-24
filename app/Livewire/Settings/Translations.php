<?php

namespace App\Livewire\Settings;

use App\Models\Translation;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Translations extends Component
{
    public string $search = '';

    public string $groupFilter = '';

    /** Only rows whose target differs from source (ie. real overrides). */
    public bool $changedOnly = false;

    public const CAP = 100;

    /** Inline save feedback (per tab). */
    public string $savedMsg = '';

    public bool $savedOk = true;

    /** @var array<int,array{id:?int,group:string,source:string,target:string,note:string,is_active:bool}> */
    public array $rep = [];

    /** @var array<int,array{id:?int,group:string,source:string,target:string,note:string,is_active:bool}> */
    public array $term = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('settings.view'), 403);
        $this->loadReplace();
        $this->loadTerms();
    }

    public function updatedSearch(): void
    {
        $this->loadReplace();
    }

    public function updatedGroupFilter(): void
    {
        $this->loadReplace();
    }

    public function updatedChangedOnly(): void
    {
        $this->loadReplace();
    }

    protected function loadReplace(): void
    {
        $rows = Translation::where('type', 'replace')
            ->when($this->groupFilter !== '', fn ($q) => $q->where('group', $this->groupFilter))
            ->when($this->changedOnly, fn ($q) => $q->whereColumn('target', '!=', 'source'))
            ->when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('source', 'like', "%{$this->search}%")
                ->orWhere('target', 'like', "%{$this->search}%")))
            ->orderBy('group')->orderBy('id')->limit(self::CAP)->get();

        $this->rep = $rows->map(fn ($t) => ['id' => $t->id, 'group' => $t->group, 'source' => $t->source, 'target' => $t->target ?? '', 'note' => $t->note ?? '', 'is_active' => $t->is_active])->all();
    }

    protected function loadTerms(): void
    {
        $this->term = Translation::where('type', 'term')->orderBy('id')->get()
            ->map(fn ($t) => ['id' => $t->id, 'group' => $t->group, 'source' => $t->source, 'target' => $t->target ?? '', 'note' => $t->note ?? '', 'is_active' => $t->is_active])->all();
    }

    public function addRep(): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        $this->rep[] = ['id' => null, 'group' => 'custom', 'source' => '', 'target' => '', 'note' => '', 'is_active' => true];
    }

    public function addTerm(): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        $this->term[] = ['id' => null, 'group' => 'custom', 'source' => '', 'target' => '', 'note' => '', 'is_active' => true];
    }

    public function save(string $type): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        $rows = $type === 'term' ? $this->term : $this->rep;
        $seen = [];
        $saved = 0;
        $skipped = 0;
        try {
            foreach ($rows as $r) {
                $source = trim($r['source'] ?? '');
                if ($source === '' || isset($seen[$source])) {
                    $skipped++;

                    continue;
                }
                $seen[$source] = true;
                Translation::updateOrCreate(
                    ['type' => $type, 'source' => $source],
                    [
                        'group' => $r['group'] ?: 'custom',
                        'target' => $r['target'] ?? '',
                        'note' => $r['note'] ?: null,
                        'is_active' => (bool) ($r['is_active'] ?? true),
                        'updated_by' => auth()->id(),
                    ]
                );
                $saved++;
            }
        } catch (\Throwable $e) {
            $this->savedOk = false;
            $this->savedMsg = '✗ ບັນທຶກ ບໍ່ສຳເລັດ: '.$e->getMessage();

            return;
        }

        $type === 'term' ? $this->loadTerms() : $this->loadReplace();
        $this->savedOk = true;
        $this->savedMsg = "✓ ບັນທຶກ {$saved} ຄຳ ສຳເລັດ".($skipped ? " · ຂ້າມ {$skipped} (ຫວ່າງ/ຊ້ຳ)" : '').' · '.now()->format('H:i:s');
        $this->dispatch('saved');
    }

    public function remove(string $type, int $i): void
    {
        abort_unless(auth()->user()->can('settings.edit'), 403);
        $rows = $type === 'term' ? $this->term : $this->rep;
        $row = $rows[$i] ?? null;
        if ($row && $row['id']) {
            Translation::whereKey($row['id'])->delete();
        }
        if ($type === 'term') {
            unset($this->term[$i]);
            $this->term = array_values($this->term);
        } else {
            unset($this->rep[$i]);
            $this->rep = array_values($this->rep);
        }
    }

    public function render(): View
    {
        $groups = Translation::where('type', 'replace')
            ->select('group', DB::raw('count(*) as c'))->groupBy('group')->orderBy('group')->get();

        return view('livewire.settings.translations', [
            'groups' => $groups,
            'totalReplace' => Translation::where('type', 'replace')->count(),
        ]);
    }
}
