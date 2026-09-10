<div class="pb-8">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

        <div x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)" x-show="show" style="display:none"
             class="fixed bottom-4 right-4 z-50 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 shadow-lg">ບັນທຶກແລ້ວ ✓</div>

        {{-- Header --}}
        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-sky-100 bg-gradient-to-b from-sky-100 to-sky-50 flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-sky-500 to-sky-700 text-white grid place-items-center shadow text-xl">📊</div>
                <div class="flex-1">
                    <h1 class="text-base font-bold text-gray-800">ສະຫຼຸບຜົນຄວາມເພິ່ງພໍໃຈ · Satisfaction Results</h1>
                    <p class="text-xs text-gray-500">Warehouse &amp; Import-Export · ຄຳຕອບ {{ $total }} ຄັ້ງ</p>
                </div>
                <a href="{{ route('survey') }}" target="_blank"
                   class="h-9 px-3 rounded-lg bg-white border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50 inline-flex items-center gap-1">🔗 ລິ້ງແບບສອບຖາມ</a>
            </div>

            {{-- Filters --}}
            <div class="p-4 grid md:grid-cols-6 gap-3 bg-gray-50/60">
                <div>
                    <label class="text-[11px] font-semibold text-gray-500">ຄວາມຖີ່</label>
                    <select wire:model.live="frequency" class="mt-1 w-full h-9 rounded-lg border-gray-300 text-sm">
                        <option value="">ທັງໝົດ</option>
                        <option value="daily">Daily</option><option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option><option value="occasionally">Occasionally</option>
                    </select>
                </div>
                <div>
                    <label class="text-[11px] font-semibold text-gray-500">ໜ່ວຍງານ</label>
                    <select wire:model.live="unit_id" class="mt-1 w-full h-9 rounded-lg border-gray-300 text-sm">
                        <option value="">ທັງໝົດ</option>
                        @foreach ($units as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[11px] font-semibold text-gray-500">ພະແນກ</label>
                    <select wire:model.live="department_id" class="mt-1 w-full h-9 rounded-lg border-gray-300 text-sm">
                        <option value="">ທັງໝົດ</option>
                        @foreach ($departments as $dept)<option value="{{ $dept->id }}">{{ $dept->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[11px] font-semibold text-gray-500">ດ້ານບໍລິການ</label>
                    <select wire:model.live="service" class="mt-1 w-full h-9 rounded-lg border-gray-300 text-sm">
                        <option value="all">ທັງໝົດ</option><option value="wh">Warehouse</option><option value="ie">Import-Export</option>
                    </select>
                </div>
                <div>
                    <label class="text-[11px] font-semibold text-gray-500">ຈາກວັນທີ</label>
                    <input type="date" wire:model.live="from" class="mt-1 w-full h-9 rounded-lg border-gray-300 text-sm">
                </div>
                <div>
                    <label class="text-[11px] font-semibold text-gray-500">ຫາວັນທີ</label>
                    <div class="flex gap-1">
                        <input type="date" wire:model.live="to" class="mt-1 w-full h-9 rounded-lg border-gray-300 text-sm">
                        <button wire:click="resetFilters" class="mt-1 h-9 px-2 rounded-lg border border-gray-300 text-xs text-gray-500 hover:bg-gray-100" title="ລ້າງ">✕</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Campaign config (admin): active window + identity toggle --}}
        @canany(['survey.edit', 'reports.view'])
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-4">
                <div class="flex flex-wrap items-end gap-4">
                    <label class="inline-flex items-center gap-2 text-sm {{ $campaignActive ? 'text-emerald-700 font-semibold' : 'text-gray-600' }}">
                        <input type="checkbox" wire:model="campaignActive" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        ເປີດ ແຄມເປນ (ເດັ້ງ popup + ຮັບ ຄຳຕອບ)
                    </label>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500">ວັນ ເລີ່ມ</label>
                        <input type="date" wire:model="startDate" class="mt-1 h-9 rounded-lg border-gray-300 text-sm">
                        @error('startDate')<p class="text-[11px] text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500">ວັນ ຈົບ</label>
                        <input type="date" wire:model="endDate" class="mt-1 h-9 rounded-lg border-gray-300 text-sm">
                        @error('endDate')<p class="text-[11px] text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-600" title="ສະແດງ ຊື່ + ພະແນກ ຜູ້ຕອບ ໃນ ຄຳເຫັນ (ຖ້າ ປິດ = ບໍ່ ລະບຸ ຕົວ)">
                        <input type="checkbox" wire:model="identify" class="rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                        ສະແດງ ຊື່ + ພະແນກ ຜູ້ຕອບ
                    </label>
                    <button wire:click="saveCampaign" class="ml-auto h-9 px-5 rounded-lg bg-sky-600 text-white text-sm font-semibold hover:bg-sky-700">ບັນທຶກ</button>
                </div>
                <p class="text-[11px] text-gray-400 mt-2">💡 ຊ່ວງ ວັນທີ ຄຸມ ທັງ popup (ຄົນ login) ແລະ ລິ້ງ ສາທາລະນະ /survey. ວ່າງ = ບໍ່ ຈຳກັດ ດ້ານ ນັ້ນ.</p>
            </div>
        @endcanany

        @if ($total === 0)
            <div class="bg-white border border-gray-100 rounded-2xl p-10 text-center text-gray-400 text-sm">ຍັງບໍ່ມີຄຳຕອບ ຕາມເງື່ອນໄຂນີ້.</div>
        @else
            {{-- KPI tiles --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-4"><p class="text-xs text-gray-500">ຄຳຕອບທັງໝົດ</p><p class="text-2xl font-bold text-gray-800">{{ $total }}</p></div>
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-4"><p class="text-xs text-gray-500">ໂດຍລວມ Warehouse</p><p class="text-2xl font-bold text-sky-600">{{ $overallWh ?? '—' }} <span class="text-sm text-gray-400">/5</span></p></div>
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-4"><p class="text-xs text-gray-500">ໂດຍລວມ Import-Export</p><p class="text-2xl font-bold text-emerald-600">{{ $overallIe ?? '—' }} <span class="text-sm text-gray-400">/5</span></p></div>
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-4"><p class="text-xs text-gray-500">ຄະແນນສະເລ່ຍລວມ</p><p class="text-2xl font-bold text-amber-600">{{ $grandMean ?? '—' }} <span class="text-sm text-gray-400">/5</span></p></div>
            </div>

            {{-- style maps for the Insights panel (rendered after the trend) --}}
            @php
                $bandBadge = [
                    'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                    'green' => 'bg-green-50 text-green-700 ring-green-200',
                    'amber' => 'bg-amber-50 text-amber-700 ring-amber-200',
                    'orange' => 'bg-orange-50 text-orange-700 ring-orange-200',
                    'red' => 'bg-red-50 text-red-700 ring-red-200',
                    'gray' => 'bg-gray-100 text-gray-600 ring-gray-200',
                ];
                $recoStyle = [
                    'critical' => ['🔴', 'bg-red-50 border-red-200 text-red-800'],
                    'priority' => ['⚠️', 'bg-orange-50 border-orange-200 text-orange-800'],
                    'watch'    => ['👀', 'bg-amber-50 border-amber-200 text-amber-800'],
                    'strength' => ['💪', 'bg-green-50 border-green-200 text-green-800'],
                    'good'     => ['✅', 'bg-emerald-50 border-emerald-200 text-emerald-800'],
                    'info'     => ['ℹ️', 'bg-gray-50 border-gray-200 text-gray-700'],
                ];
            @endphp
            {{-- Participation / response rate --}}
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                    <h3 class="font-bold text-gray-800 text-sm">👥 ອັດຕາການຕອບ · Participation</h3>
                    <div class="text-xs text-gray-500 flex items-center gap-1.5 flex-wrap">
                        ຄຳຕອບ <b class="text-gray-800">{{ $total }}</b> / ພະນັກງານ
                        @if (! $unit_id)
                            <input type="number" min="0" wire:model.blur="targetStaff" placeholder="{{ $totalStaff }}"
                                   class="w-16 h-7 rounded-md border-gray-300 text-xs text-center" title="ຕັ້ງຈຳນວນພະນັກງານເປົ້າໝາຍ ({{ $totalStaff }} = auto ຈາກລະບົບ)">
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $manualTarget ? 'bg-amber-50 text-amber-700' : 'bg-gray-100 text-gray-500' }}">{{ $manualTarget ? 'ເປົ້າ ຕັ້ງເອງ' : 'auto '.$totalStaff }}</span>
                        @else
                            <b class="text-gray-800">{{ $totalStaff }}</b>
                        @endif
                        = <b class="text-sky-600 text-sm">{{ $responseRate ?? '—' }}%</b>
                    </div>
                </div>
                <div class="h-2.5 rounded-full bg-gray-200 overflow-hidden mb-4">
                    <span class="block h-full rounded-full bg-sky-500" style="width:{{ min($responseRate ?? 0, 100) }}%"></span>
                </div>
                <div class="grid md:grid-cols-2 gap-5">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 mb-2">ແບ່ງຕາມ ໜ່ວຍງານ (ຕອບ/ພະນັກງານ)</p>
                        <div class="space-y-1.5 max-h-56 overflow-y-auto">
                            @php $unitIds = collect($staffByUnit->keys())->merge($respByUnit->keys())->filter()->unique(); @endphp
                            @forelse ($unitIds as $uid)
                                @php $rc = (int) ($respByUnit[$uid] ?? 0); $sc = (int) ($staffByUnit[$uid] ?? 0); $rate = $sc ? round($rc / $sc * 100) : null; @endphp
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="w-28 truncate text-gray-600">{{ $unitNames[$uid] ?? 'ບໍ່ລະບຸ' }}</span>
                                    <div class="flex-1 h-2 rounded-full bg-gray-200 overflow-hidden"><span class="block h-full rounded-full bg-sky-400" style="width:{{ min($rate ?? 0, 100) }}%"></span></div>
                                    <span class="w-24 text-right text-gray-500">{{ $rc }}/{{ $sc ?: '—' }} · {{ $rate !== null ? $rate.'%' : '—' }}</span>
                                </div>
                            @empty
                                <p class="text-xs text-gray-400">—</p>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 mb-2">ແບ່ງຕາມ ຄວາມຖີ່ຕິດຕໍ່ (ສັດສ່ວນຜູ້ຕອບ)</p>
                        <div class="space-y-1.5">
                            @foreach (['daily' => 'ປະຈຳວັນ', 'weekly' => 'ປະຈຳອາທິດ', 'monthly' => 'ປະຈຳເດືອນ', 'occasionally' => 'ບາງຄັ້ງຄາວ'] as $fk => $flabel)
                                @php $fc = (int) ($respByFreq[$fk] ?? 0); $fpct = $total ? round($fc / $total * 100) : 0; @endphp
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="w-24 text-gray-600">{{ $flabel }}</span>
                                    <div class="flex-1 h-2 rounded-full bg-gray-200 overflow-hidden"><span class="block h-full rounded-full bg-indigo-400" style="width:{{ $fpct }}%"></span></div>
                                    <span class="w-16 text-right text-gray-500">{{ $fc }} · {{ $fpct }}%</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <p class="text-[11px] text-gray-400 mt-3">ອັດຕາ = ຄຳຕອບ ÷ ພະນັກງານ active. *ຕອບບໍ່ລະບຸຕົວ/ຕອບຊ້ຳ ອາດເຮັດໃຫ້ >100% — ບັງຄັບ login ຖ້າຢາກນັບເປັນຄົນເອກະລັກ.</p>
            </div>

            @php
                $labels = [
                    'wh_receiving' => 'ຮັບສິນຄ້າ', 'wh_condition' => 'ສະພາບສິນຄ້າ', 'wh_storage' => 'ຈັດເກັບ/ວາງ',
                    'ie_customs' => 'ເຄລຍພາສີ', 'ie_communication' => 'ສື່ສານສະຖານະ', 'ie_urgent' => 'ສິນຄ້າດ່ວນ',
                ];
                $barColor = fn ($v) => $v === null ? '#cbd5e1' : ($v >= 4 ? '#22c55e' : ($v >= 3 ? '#eab308' : '#f97316'));
                $avgBar = function ($field) use ($avgOf, $labels, $barColor) {
                    $v = $avgOf($field);
                    $pct = $v ? $v / 5 * 100 : 0;
                    return '<div><div class="flex justify-between text-sm"><span class="text-gray-600">'.$labels[$field].'</span><b class="text-gray-800">'.($v ?? '—').'</b></div>'
                        .'<div class="h-2 rounded-full bg-gray-200 mt-1 overflow-hidden"><span class="block h-full rounded-full" style="width:'.$pct.'%;background:'.$barColor($v).'"></span></div></div>';
                };
            @endphp

            {{-- per-question averages --}}
            <div class="grid md:grid-cols-2 gap-4">
                @if ($service !== 'ie')
                    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5">
                        <h3 class="font-bold text-gray-700 text-sm mb-3">ຄັງສິນຄ້າ · Warehouse <span class="text-xs text-gray-400">(ສະເລ່ຍ {{ $whMean ?? '—' }})</span></h3>
                        <div class="space-y-3">
                            @foreach (\App\Models\SurveyResponse::WH_FIELDS as $f){!! $avgBar($f) !!}@endforeach
                        </div>
                    </div>
                @endif
                @if ($service !== 'wh')
                    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5">
                        <h3 class="font-bold text-gray-700 text-sm mb-3">ນຳເຂົ້າ-ສົ່ງອອກ · Import-Export <span class="text-xs text-gray-400">(ສະເລ່ຍ {{ $ieMean ?? '—' }})</span></h3>
                        <div class="space-y-3">
                            @foreach (\App\Models\SurveyResponse::IE_FIELDS as $f){!! $avgBar($f) !!}@endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- distribution + trend --}}
            <div class="grid md:grid-cols-2 gap-4">
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5">
                    <h3 class="font-bold text-gray-700 text-sm mb-3">ການແຈກຈາຍຄະແນນ (1–5)</h3>
                    @php $distTot = array_sum($dist); $dc = [1=>'#ef4444',2=>'#f97316',3=>'#eab308',4=>'#84cc16',5=>'#22c55e']; @endphp
                    <div class="space-y-2">
                        @for ($i = 5; $i >= 1; $i--)
                            @php $c = $dist[$i]; $pct = $distTot ? round($c / $distTot * 100) : 0; @endphp
                            <div class="flex items-center gap-2 text-xs">
                                <span class="w-4 font-bold" style="color:{{ $dc[$i] }}">{{ $i }}</span>
                                <div class="flex-1 h-2.5 rounded-full bg-gray-200 overflow-hidden"><span class="block h-full rounded-full" style="width:{{ $pct }}%;background:{{ $dc[$i] }}"></span></div>
                                <span class="w-20 text-right text-gray-500">{{ $c }} ({{ $pct }}%)</span>
                            </div>
                        @endfor
                    </div>
                </div>

                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5">
                    <h3 class="font-bold text-gray-700 text-sm mb-3">ແນວໂນ້ມ ຄະແນນສະເລ່ຍ (6 ເດືອນ)</h3>
                    @php
                        $pts = fn ($key) => $trend->map(function ($t, $i) use ($key) {
                            if ($t[$key] === null) return null;
                            $x = 12 + $i * 58; $y = 112 - (($t[$key] - 1) / 4) * 92;
                            return round($x, 1).','.round($y, 1);
                        })->filter()->implode(' ');
                    @endphp
                    <svg viewBox="0 0 320 132" class="w-full h-40">
                        @for ($g = 1; $g <= 5; $g++)<line x1="8" x2="312" y1="{{ 112 - (($g-1)/4)*92 }}" y2="{{ 112 - (($g-1)/4)*92 }}" stroke="#f1f5f9" stroke-width="1"/>@endfor
                        <polyline fill="none" stroke="#0284c7" stroke-width="2.5" points="{{ $pts('wh') }}"/>
                        <polyline fill="none" stroke="#059669" stroke-width="2.5" points="{{ $pts('ie') }}"/>
                        <g fill="#64748b" font-size="9">@foreach ($trend as $i => $t)<text x="{{ 4 + $i * 58 }}" y="126">{{ $t['label'] }}</text>@endforeach</g>
                    </svg>
                    <div class="flex gap-4 text-xs mt-1"><span class="text-sky-600">● Warehouse</span><span class="text-emerald-600">● Import-Export</span></div>
                </div>
            </div>

            {{-- Insights & Recommendations (auto, PDCA) — after the results & trend --}}
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h3 class="font-bold text-gray-800 text-sm">🎯 ບົດວິເຄາະ &amp; ຄຳແນະນຳ · Insights &amp; Recommendations</h3>
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full ring-1 {{ $bandBadge[$insights['verdict'][1]] }}">ໂດຍລວມ {{ $grandMean ?? '—' }}/5 · {{ $insights['verdict'][0] }}</span>
                        <span class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full ring-1 {{ $bandBadge[$insights['t2bBand'][1]] }}">ພໍໃຈ 4–5: {{ $t2b ?? '—' }}% · {{ $insights['t2bBand'][0] }}</span>
                        <span class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full ring-1 {{ $b2b !== null && $b2b > 10 ? 'bg-red-50 text-red-700 ring-red-200' : 'bg-gray-100 text-gray-600 ring-gray-200' }}">ບໍ່ພໍໃຈ 1–2: {{ $b2b ?? '—' }}%</span>
                    </div>
                </div>
                <div class="space-y-2">
                    @forelse ($insights['recos'] as $r)
                        <div class="flex items-start gap-2 border rounded-lg px-3 py-2 text-sm {{ $recoStyle[$r['level']][1] }}">
                            <span class="shrink-0">{{ $recoStyle[$r['level']][0] }}</span><span>{{ $r['text'] }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">ຍັງບໍ່ມີຄຳແນະນຳ (ຂໍ້ມູນບໍ່ພຽງພໍ).</p>
                    @endforelse
                </div>
                <p class="text-[11px] text-gray-400 mt-3">ເກນອ້າງອີງ: ສະເລ່ຍ ≥4.0 = ດີ · ພໍໃຈ(T2B) ≥80% = ດີ · ບໍ່ພໍໃຈ(B2B) ≤10% · ວົງຈອນ PDCA (ວາງແຜນ → ເຮັດ → ກວດ → ປັບປຸງ)</p>
            </div>

            {{-- comments --}}
            <div class="grid md:grid-cols-2 gap-4">
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5">
                    <h3 class="font-bold text-emerald-700 text-sm mb-2">👍 ເຮັດໄດ້ດີ ({{ $comments->whereNotNull('doing_well')->count() }})</h3>
                    <ul class="text-sm text-gray-600 space-y-2 max-h-72 overflow-y-auto">
                        @forelse ($comments->whereNotNull('doing_well') as $c)
                            <li class="border-b border-gray-100 pb-2">“{{ $c->doing_well }}” <span class="text-[11px] text-gray-400">— @if ($identify && $c->user){{ $c->user->display_name }} · @endif{{ ($identify ? $c->department?->name : null) ?? $c->unit?->name ?? '—' }} · {{ ucfirst($c->frequency ?? '') }}</span></li>
                        @empty <li class="text-gray-400 text-xs">ຍັງບໍ່ມີ.</li> @endforelse
                    </ul>
                </div>
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5">
                    <h3 class="font-bold text-rose-600 text-sm mb-2">🔧 ຄວນປັບປຸງ ({{ $comments->whereNotNull('improve')->count() }})</h3>
                    <ul class="text-sm text-gray-600 space-y-2 max-h-72 overflow-y-auto">
                        @forelse ($comments->whereNotNull('improve') as $c)
                            <li class="border-b border-gray-100 pb-2">“{{ $c->improve }}” <span class="text-[11px] text-gray-400">— @if ($identify && $c->user){{ $c->user->display_name }} · @endif{{ ($identify ? $c->department?->name : null) ?? $c->unit?->name ?? '—' }} · {{ ucfirst($c->frequency ?? '') }}</span></li>
                        @empty <li class="text-gray-400 text-xs">ຍັງບໍ່ມີ.</li> @endforelse
                    </ul>
                </div>
            </div>
        @endif
    </div>
</div>
