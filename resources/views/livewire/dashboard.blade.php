@php
    // [card bg, left-accent border, number text] — light, distinct per data block.
    $tones = [
        'indigo' => ['bg-indigo-50/70', 'border-l-indigo-400', 'text-indigo-700'],
        'sky' => ['bg-sky-50/70', 'border-l-sky-400', 'text-sky-700'],
        'emerald' => ['bg-emerald-50/70', 'border-l-emerald-400', 'text-emerald-700'],
        'violet' => ['bg-violet-50/70', 'border-l-violet-400', 'text-violet-700'],
        'amber' => ['bg-amber-50/70', 'border-l-amber-400', 'text-amber-700'],
        'gray' => ['bg-slate-50', 'border-l-slate-300', 'text-slate-700'],
    ];
    $kindMeta = [
        'borrow' => ['🔖', 'bg-indigo-100 text-indigo-700'],
        'request' => ['📄', 'bg-sky-100 text-sky-700'],
        'deposit' => ['📦', 'bg-emerald-100 text-emerald-700'],
        'da' => ['⚠', 'bg-amber-100 text-amber-700'],
        'oga' => ['🚚', 'bg-teal-100 text-teal-700'],
    ];
    $widgetLabels = ['kpi' => 'ບັດສະຫຼຸບ (KPI)', 'queue' => 'ສິ່ງທີ່ຕ້ອງເຮັດ', 'activity' => 'ກິດຈະກຳລ່າສຸດ', 'charts' => 'ກຣາຟ (charts)'];
    $stamp = now()->format('d/m/Y H:i');
@endphp

<div class="pb-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <div id="dash-capture" class="bg-gray-50 rounded-lg space-y-2.5">

            {{-- header bar --}}
            <div class="bg-white border border-gray-100 rounded-lg px-4 py-2.5 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="text-base font-semibold text-gray-800 truncate">ສະບາຍດີ, {{ auth()->user()->display_name }} 👋</h3>
                    <p class="text-xs text-gray-500 truncate">
                        {{ auth()->user()->getRoleNames()->implode(', ') ?: '—' }}
                        @if (auth()->user()->is_super_admin)· super_admin @endif
                        · {{ $stamp }}
                    </p>
                </div>
                <div class="flex items-center gap-1.5 shrink-0" data-noexport>
                    <button onclick="window.exportPdf('dash-capture','dashboard-{{ now()->format('Ymd-Hi') }}.pdf')" class="text-xs text-gray-600 border border-gray-200 rounded-md px-2.5 py-1.5 hover:bg-gray-50" title="ສົ່ງອອກ PDF">📄 PDF</button>
                    <button onclick="window.exportJpg('dash-capture','dashboard-{{ now()->format('Ymd-Hi') }}.jpg')" class="text-xs text-gray-600 border border-gray-200 rounded-md px-2.5 py-1.5 hover:bg-gray-50" title="ສົ່ງອອກ JPG">🖼 JPG</button>
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="text-xs text-gray-600 border border-gray-200 rounded-md px-2.5 py-1.5 hover:bg-gray-50">⚙</button>
                        <div x-show="open" x-cloak @click.outside="open = false" x-transition
                             class="absolute right-0 mt-2 w-52 bg-white border border-gray-200 rounded-lg shadow-lg z-30 p-2">
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

            {{-- KPI cards --}}
            @if (($prefs['kpi'] ?? true) && count($cards))
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                    @foreach ($cards as $c)
                        @php [$cBg, $cBorder, $cNum] = $tones[$c['tone']] ?? $tones['gray']; @endphp
                        <a href="{{ route($c['route']) }}" wire:navigate class="block border border-gray-100 border-l-4 {{ $cBorder }} {{ $cBg }} rounded-lg px-3 py-2 hover:shadow-sm transition">
                            <div class="flex items-center justify-between gap-1">
                                <span class="text-xs font-medium text-gray-600 truncate">{{ $c['label'] }}</span>
                                <span class="text-[10px] {{ $cNum }}">›</span>
                            </div>
                            <div class="mt-0.5 flex items-baseline gap-1">
                                <span class="text-2xl font-bold {{ $cNum }} leading-none">{{ number_format($c['big']) }}</span>
                                <span class="text-[10px] text-gray-400">{{ $c['big_label'] }}</span>
                            </div>
                            <div class="text-[11px] truncate {{ $c['alert'] ? 'text-red-600 font-medium' : 'text-gray-400' }}">{{ $c['sub'] }}</div>
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Action queue + Recent activity --}}
            @if (($prefs['queue'] ?? true) || ($prefs['activity'] ?? true))
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-2.5">
                    @if ($prefs['queue'] ?? true)
                        <div class="bg-white border border-gray-100 border-t-2 border-t-emerald-300 rounded-lg p-3">
                            <h2 class="text-sm font-semibold text-emerald-800 mb-1.5">✅ ສິ່ງທີ່ຕ້ອງເຮັດ</h2>
                            @forelse ($actionRows as $row)
                                <a href="{{ route($row['route']) }}" wire:navigate class="flex items-center justify-between gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-50 transition group">
                                    <span class="flex-1 text-sm text-gray-700 truncate">{{ $row['label'] }}</span>
                                    <span class="inline-flex items-center justify-center min-w-[1.5rem] h-6 px-2 rounded-full text-xs font-semibold tabular-nums {{ $row['alert'] ? 'bg-red-100 text-red-700' : 'bg-sky-100 text-sky-700' }}">{{ $row['count'] }}</span>
                                </a>
                            @empty
                                <div class="flex items-center justify-center gap-2 py-5 text-center"><span class="text-2xl">🎉</span><p class="text-sm text-gray-500">ບໍ່ມີວຽກຄ້າງ</p></div>
                            @endforelse
                        </div>
                    @endif

                    @if ($prefs['activity'] ?? true)
                        <div class="bg-white border border-gray-100 border-t-2 border-t-sky-300 rounded-lg p-3">
                            <h2 class="text-sm font-semibold text-sky-800 mb-1.5">🕑 ກິດຈະກຳລ່າສຸດ</h2>
                            @forelse ($activity as $a)
                                @php [$icon, $cls] = $kindMeta[$a['kind']] ?? ['•', 'bg-gray-100 text-gray-600']; @endphp
                                <a href="{{ route($a['route']) }}" wire:navigate class="flex items-center gap-2 py-1 border-b border-gray-50 last:border-0 hover:bg-gray-50 -mx-1 px-1 rounded transition">
                                    <span class="inline-flex w-6 h-6 items-center justify-center rounded text-xs shrink-0 {{ $cls }}">{{ $icon }}</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-800 truncate leading-tight"><span class="font-medium font-mono">{{ $a['number'] }}</span> · <span class="text-gray-600">{{ $a['status'] }}</span></p>
                                        <p class="text-[11px] text-gray-400 truncate leading-tight">{{ $a['actor'] }} · {{ $a['when']?->diffForHumans() ?? '—' }}</p>
                                    </div>
                                </a>
                            @empty
                                <div class="py-5 text-center text-sm text-gray-400">ບໍ່ມີ ກິດຈະກຳ</div>
                            @endforelse
                        </div>
                    @endif
                </div>
            @endif

            {{-- Charts (staff) --}}
            @if ($showCharts && $chart)
                <div wire:ignore x-data="dashCharts(@js($chart))" class="grid grid-cols-1 lg:grid-cols-2 gap-2.5">
                    <div class="bg-white border border-gray-100 border-t-2 border-t-violet-300 rounded-lg p-3">
                        <h2 class="text-sm font-semibold text-violet-800 mb-1">📊 ໃບເບີກ ຕາມສະຖານະ</h2>
                        <div class="h-40"><canvas x-ref="pie"></canvas></div>
                    </div>
                    <div class="bg-white border border-gray-100 border-t-2 border-t-amber-300 rounded-lg p-3">
                        <h2 class="text-sm font-semibold text-amber-800 mb-1">📈 ການເຄື່ອນໄຫວ 6 ເດືອນ</h2>
                        <div class="h-40"><canvas x-ref="bar"></canvas></div>
                    </div>
                </div>
            @endif

            @if (! ($prefs['kpi'] ?? true) && ! ($prefs['queue'] ?? true) && ! ($prefs['activity'] ?? true) && ! $showCharts)
                <div class="bg-white border border-gray-100 rounded-lg p-6 text-center text-gray-400">ທຸກ widget ຖືກປິດ — ກົດ ⚙ ເພື່ອເປີດຄືນ.</div>
            @endif
        </div>
    </div>
</div>
