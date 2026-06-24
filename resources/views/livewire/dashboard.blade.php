@php
    $toneBg = [
        'sky' => 'bg-sky-100 text-sky-700', 'amber' => 'bg-amber-100 text-amber-700',
        'red' => 'bg-red-100 text-red-700', 'emerald' => 'bg-emerald-100 text-emerald-700',
        'violet' => 'bg-violet-100 text-violet-700', 'slate' => 'bg-slate-100 text-slate-600',
    ];
    $kpiIcon = [
        'borrow' => 'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
        'request' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
        'deposit' => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z',
        'bell' => 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0',
    ];
    $kindMeta = [
        'borrow' => ['🔖', 'bg-indigo-100 text-indigo-700'], 'request' => ['📄', 'bg-sky-100 text-sky-700'],
        'deposit' => ['📦', 'bg-emerald-100 text-emerald-700'], 'da' => ['⚠', 'bg-amber-100 text-amber-700'], 'oga' => ['🚚', 'bg-teal-100 text-teal-700'],
    ];
    $widgetLabels = ['kpi' => 'ບັດສະຫຼຸບ (KPI)', 'queue' => 'ສິ່ງທີ່ຕ້ອງເຮັດ', 'activity' => 'ກິດຈະກຳລ່າສຸດ', 'charts' => 'ກຣາຟ (charts)'];
    $chipTone = [
        'sky' => 'bg-sky-100 text-sky-700', 'amber' => 'bg-amber-100 text-amber-700', 'emerald' => 'bg-emerald-100 text-emerald-700',
        'red' => 'bg-red-100 text-red-700', 'violet' => 'bg-violet-100 text-violet-700', 'slate' => 'bg-slate-100 text-slate-600', 'gray' => 'bg-gray-100 text-gray-500',
    ];
    $daChips = [['draft', 'Draft', 'slate'], ['submitted', 'Submitted', 'amber'], ['purchasing_review', 'PCD Review', 'sky'], ['pending_approval', 'WH Approval', 'violet'], ['resolved', 'Resolved', 'emerald'], ['cancelled', 'Cancelled', 'gray']];
    $ogaChips = [['draft', 'Draft', 'slate'], ['dispatched', 'Dispatched', 'amber'], ['delivered', 'Delivered', 'emerald'], ['returned', 'Returned', 'red'], ['cancelled', 'Cancelled', 'gray']];
@endphp

<div class="pb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div id="dash-capture" class="space-y-4">

            {{-- header bar --}}
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="text-lg font-semibold text-gray-800 truncate">ສະບາຍດີ, {{ auth()->user()->display_name }}</h1>
                    <p class="text-sm text-gray-500 truncate">{{ auth()->user()->getRoleNames()->implode(', ') ?: '—' }}@if (auth()->user()->is_super_admin) · super_admin @endif · {{ now()->format('l, d M Y') }}</p>
                </div>
                <div class="flex items-center gap-1.5 shrink-0" data-noexport>
                    <button onclick="window.exportPdf('dash-capture','dashboard-{{ now()->format('Ymd-Hi') }}.pdf')" class="text-xs text-gray-600 border border-gray-200 rounded-md px-2.5 py-1.5 hover:bg-gray-50" title="ສົ່ງອອກ PDF">📄 PDF</button>
                    <button onclick="window.exportJpg('dash-capture','dashboard-{{ now()->format('Ymd-Hi') }}.jpg')" class="text-xs text-gray-600 border border-gray-200 rounded-md px-2.5 py-1.5 hover:bg-gray-50" title="ສົ່ງອອກ JPG">🖼 JPG</button>
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="text-xs text-gray-600 border border-gray-200 rounded-md px-2.5 py-1.5 hover:bg-gray-50">⚙</button>
                        <div x-show="open" x-cloak @click.outside="open = false" x-transition class="absolute right-0 mt-2 w-52 bg-white border border-gray-200 rounded-lg shadow-lg z-30 p-2">
                            <p class="text-[11px] text-gray-400 px-2 py-1">ສະແດງ widget</p>
                            @foreach ($widgetLabels as $key => $lbl)
                                @if ($key !== 'charts' || $showCharts || ($prefs['charts'] ?? true))
                                    <label class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-gray-50 cursor-pointer text-sm">
                                        <input type="checkbox" wire:click="toggle('{{ $key }}')" @checked($prefs[$key] ?? true) class="rounded border-gray-300 text-sky-600 focus:ring-sky-500" />
                                        <span class="text-gray-700">{{ $lbl }}</span>
                                    </label>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- KPI cards (icon-square, WIS style) --}}
            @if (($prefs['kpi'] ?? true) && count($kpis))
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    @foreach ($kpis as $c)
                        @php $tag = $c['route'] ? 'a' : 'div'; @endphp
                        <{{ $tag }} @if ($c['route']) href="{{ route($c['route']) }}" wire:navigate @endif class="flex items-start gap-3 p-4 rounded-xl bg-white border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                            <div class="p-2.5 rounded-lg {{ $toneBg[$c['tone']] ?? $toneBg['slate'] }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $kpiIcon[$c['key']] ?? $kpiIcon['bell'] }}" /></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-gray-500 truncate">{{ $c['label'] }}</p>
                                <p class="mt-0.5 text-2xl font-bold text-gray-900 tabular-nums">{{ number_format($c['value']) }}</p>
                                <p class="mt-0.5 text-xs text-gray-500 truncate">{{ $c['hint'] }}</p>
                            </div>
                        </{{ $tag }}>
                    @endforeach
                </div>
            @endif

            {{-- queue | activity | charts --}}
            @if (($prefs['queue'] ?? true) || ($prefs['activity'] ?? true) || $showCharts)
                <div class="grid grid-cols-1 {{ $showCharts ? 'lg:grid-cols-3' : 'lg:grid-cols-2' }} gap-4 lg:items-stretch">
                    {{-- ສິ່ງທີ່ຕ້ອງເຮັດ --}}
                    @if ($prefs['queue'] ?? true)
                        <div x-data="{ p: 0, size: 5, n: {{ count($actionRows) }} }" class="bg-white border border-gray-200 rounded-xl p-4 flex flex-col lg:h-[332px]">
                            <h2 class="text-base font-semibold text-gray-800 mb-3 shrink-0">✅ ສິ່ງທີ່ຕ້ອງເຮັດ</h2>
                            <div class="flex-1 min-h-0">
                                @forelse ($actionRows as $i => $row)
                                    <a href="{{ route($row['route']) }}" wire:navigate x-show="{{ $i }} >= p*size && {{ $i }} < (p+1)*size" class="flex items-center justify-between gap-2 px-2 py-2.5 rounded-lg hover:bg-gray-50 transition group">
                                        <span class="flex-1 text-sm text-gray-700 truncate">{{ $row['label'] }}</span>
                                        <span class="inline-flex items-center justify-center min-w-[1.75rem] h-7 px-2 rounded-full text-xs font-semibold tabular-nums {{ $row['alert'] ? 'bg-red-100 text-red-700' : 'bg-sky-100 text-sky-700' }}">{{ $row['count'] }}</span>
                                        <span class="text-gray-300 group-hover:text-gray-500">›</span>
                                    </a>
                                @empty
                                    <div class="flex flex-col items-center justify-center h-full py-8 text-center"><span class="text-3xl mb-1">🎉</span><p class="text-sm text-gray-500">ບໍ່ມີວຽກຄ້າງ</p></div>
                                @endforelse
                            </div>
                            @if (count($actionRows) > 5)
                                <div class="flex items-center justify-between pt-2 text-xs text-gray-500 shrink-0">
                                    <button @click="p = Math.max(0, p-1)" :disabled="p===0" class="px-2 py-1 rounded hover:bg-gray-100 disabled:opacity-40">‹ ກ່ອນ</button>
                                    <span x-text="(p+1)+' / '+Math.ceil(n/size)"></span>
                                    <button @click="if ((p+1)*size < n) p++" :disabled="(p+1)*size >= n" class="px-2 py-1 rounded hover:bg-gray-100 disabled:opacity-40">ຕໍ່ ›</button>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- ກິດຈະກຳລ່າສຸດ --}}
                    @if ($prefs['activity'] ?? true)
                        <div x-data="{ p: 0, size: 5, n: {{ count($activity) }} }" class="bg-white border border-gray-200 rounded-xl p-4 flex flex-col lg:h-[332px]">
                            <h2 class="text-base font-semibold text-gray-800 mb-3 shrink-0">🕑 ກິດຈະກຳລ່າສຸດ</h2>
                            <div class="flex-1 min-h-0">
                                @forelse ($activity as $i => $a)
                                    @php [$icon, $cls] = $kindMeta[$a['kind']] ?? ['•', 'bg-gray-100 text-gray-600']; @endphp
                                    <a href="{{ route($a['route']) }}" wire:navigate x-show="{{ $i }} >= p*size && {{ $i }} < (p+1)*size" class="flex items-center gap-3 py-2 hover:bg-gray-50 -mx-2 px-2 rounded transition">
                                        <span class="inline-flex w-7 h-7 items-center justify-center rounded-lg text-sm shrink-0 {{ $cls }}">{{ $icon }}</span>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-gray-800 truncate leading-tight"><span class="font-medium font-mono">{{ $a['number'] }}</span> · <span class="text-gray-600">{{ $a['status'] }}</span></p>
                                            <p class="text-xs text-gray-400 truncate leading-tight">{{ $a['actor'] }} · {{ $a['when']?->diffForHumans() ?? '—' }}</p>
                                        </div>
                                    </a>
                                @empty
                                    <div class="flex items-center justify-center h-full py-8 text-sm text-gray-400">ບໍ່ມີ ກິດຈະກຳ</div>
                                @endforelse
                            </div>
                            @if (count($activity) > 5)
                                <div class="flex items-center justify-between pt-2 text-xs text-gray-500 shrink-0">
                                    <button @click="p = Math.max(0, p-1)" :disabled="p===0" class="px-2 py-1 rounded hover:bg-gray-100 disabled:opacity-40">‹ ກ່ອນ</button>
                                    <span x-text="(p+1)+' / '+Math.ceil(n/size)"></span>
                                    <button @click="if ((p+1)*size < n) p++" :disabled="(p+1)*size >= n" class="px-2 py-1 rounded hover:bg-gray-100 disabled:opacity-40">ຕໍ່ ›</button>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- charts --}}
                    @if ($showCharts && $chart)
                        <div wire:ignore x-data="dashCharts(@js($chart))" class="bg-white border border-gray-200 rounded-xl p-4 flex flex-col gap-3 lg:h-[332px]">
                            <div>
                                <h2 class="text-sm font-semibold text-gray-800 mb-1">📊 ໃບເບີກ ຕາມສະຖານະ</h2>
                                <div class="h-28"><canvas x-ref="pie"></canvas></div>
                            </div>
                            <div class="border-t border-gray-100 pt-2">
                                <h2 class="text-sm font-semibold text-gray-800 mb-1">📈 ການເຄື່ອນໄຫວ 6 ເດືອນ</h2>
                                <div class="h-28"><canvas x-ref="bar"></canvas></div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- DA & OGA panel --}}
            @if ($daOga)
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-base font-semibold text-gray-800">DA &amp; OGA</h2>
                        <span class="text-xs text-gray-400">{{ $daOga['daTotal'] }} DA · {{ $daOga['ogaTotal'] }} OGA</span>
                    </div>
                    <div class="space-y-2.5">
                        @if ($daOga['canDa'])
                            <div class="flex items-center gap-3 flex-wrap">
                                <a href="{{ route('da') }}" wire:navigate class="text-sm font-semibold text-sky-700 shrink-0 min-w-[3rem]">⚠ DA</a>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @foreach ($daChips as [$st, $lbl, $tone])
                                        @php $cnt = $daOga['da'][$st] ?? 0; @endphp
                                        <a href="{{ route('da') }}" wire:navigate class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $cnt > 0 ? ($chipTone[$tone] ?? $chipTone['gray']) : 'bg-gray-50 text-gray-400' }}"><span>{{ $lbl }}</span><span class="tabular-nums font-semibold">{{ $cnt }}</span></a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if ($daOga['canOga'])
                            <div class="flex items-center gap-3 flex-wrap">
                                <a href="{{ route('oga') }}" wire:navigate class="text-sm font-semibold text-emerald-700 shrink-0 min-w-[3rem]">🚚 OGA</a>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @foreach ($ogaChips as [$st, $lbl, $tone])
                                        @php $cnt = $daOga['oga'][$st] ?? 0; @endphp
                                        <a href="{{ route('oga') }}" wire:navigate class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $cnt > 0 ? ($chipTone[$tone] ?? $chipTone['gray']) : 'bg-gray-50 text-gray-400' }}"><span>{{ $lbl }}</span><span class="tabular-nums font-semibold">{{ $cnt }}</span></a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if (! ($prefs['kpi'] ?? true) && ! ($prefs['queue'] ?? true) && ! ($prefs['activity'] ?? true) && ! $showCharts && ! $daOga)
                <div class="bg-white border border-gray-100 rounded-lg p-6 text-center text-gray-400">ທຸກ widget ຖືກປິດ — ກົດ ⚙ ເພື່ອເປີດຄືນ.</div>
            @endif
        </div>
    </div>
</div>
