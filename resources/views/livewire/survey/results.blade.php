<div class="pb-8">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

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
            <div class="p-4 grid md:grid-cols-5 gap-3 bg-gray-50/60">
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

            {{-- comments --}}
            <div class="grid md:grid-cols-2 gap-4">
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5">
                    <h3 class="font-bold text-emerald-700 text-sm mb-2">👍 ເຮັດໄດ້ດີ ({{ $comments->whereNotNull('doing_well')->count() }})</h3>
                    <ul class="text-sm text-gray-600 space-y-2 max-h-72 overflow-y-auto">
                        @forelse ($comments->whereNotNull('doing_well') as $c)
                            <li class="border-b border-gray-100 pb-2">“{{ $c->doing_well }}” <span class="text-[11px] text-gray-400">— {{ $c->unit?->name ?? '—' }} · {{ ucfirst($c->frequency ?? '') }}</span></li>
                        @empty <li class="text-gray-400 text-xs">ຍັງບໍ່ມີ.</li> @endforelse
                    </ul>
                </div>
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5">
                    <h3 class="font-bold text-rose-600 text-sm mb-2">🔧 ຄວນປັບປຸງ ({{ $comments->whereNotNull('improve')->count() }})</h3>
                    <ul class="text-sm text-gray-600 space-y-2 max-h-72 overflow-y-auto">
                        @forelse ($comments->whereNotNull('improve') as $c)
                            <li class="border-b border-gray-100 pb-2">“{{ $c->improve }}” <span class="text-[11px] text-gray-400">— {{ $c->unit?->name ?? '—' }} · {{ ucfirst($c->frequency ?? '') }}</span></li>
                        @empty <li class="text-gray-400 text-xs">ຍັງບໍ່ມີ.</li> @endforelse
                    </ul>
                </div>
            </div>
        @endif
    </div>
</div>
