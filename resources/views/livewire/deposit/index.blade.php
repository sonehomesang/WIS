@php
    $statusMeta = fn ($s) => match ($s) {
        'draft' => ['DRAFT', 'bg-gray-100 text-gray-600'],
        'submitted' => ['SUBMITTED', 'bg-blue-50 text-blue-700 ring-1 ring-blue-200'],
        'accepted' => ['ACCEPTED', 'bg-cyan-50 text-cyan-700 ring-1 ring-cyan-200'],
        'stored' => ['STORED', 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'],
        'needs_fix' => ['NEEDS FIX', 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'],
        'claimed' => ['CLAIMED', 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200'],
        'cancelled' => ['CANCELLED', 'bg-gray-100 text-gray-400'],
        default => [strtoupper($s), 'bg-gray-100 text-gray-600'],
    };
@endphp

<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8">
        {{-- frozen header group: toolbar + chips freeze together --}}
        <div class="sticky top-16 z-30 bg-gray-100/95 backdrop-blur">
            <div class="flex flex-col gap-3 py-3 lg:flex-row lg:items-center lg:justify-between lg:gap-3">
                <div class="flex items-center gap-3 shrink-0">
                    <span class="w-11 h-11 rounded-xl bg-gradient-to-br from-sky-500 to-cyan-500 text-white flex items-center justify-center text-xl shadow-sm">📦</span>
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">ຝາກ ເຄື່ອງ <span class="text-gray-400 text-sm font-normal">· Deposit</span></h2>
                        <p class="text-sm text-gray-400">{{ number_format($records->total()) }} ໃບ</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔎</span>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ DP/ຊື່ເຈົ້າຂອງ…" class="w-48 pl-8 rounded-lg border-gray-300 text-sm" />
                    </div>
                    <select wire:model.live="statusFilter" class="w-32 rounded-lg border-gray-300 text-sm">
                        <option value="">All Statuses</option>
                        <option value="draft">draft</option><option value="submitted">submitted</option>
                        <option value="accepted">accepted</option><option value="stored">stored</option>
                        <option value="needs_fix">needs_fix</option><option value="claimed">claimed</option><option value="cancelled">cancelled</option>
                    </select>
                    <select wire:model.live="typeFilter" class="w-28 rounded-lg border-gray-300 text-sm">
                        <option value="">All Types</option>
                        <option value="walk_in">Walk-in</option><option value="pre_request">Pre-request</option>
                    </select>
                    @if ($canManageDeleted)<button wire:click="toggleDeleted" class="text-sm rounded-lg px-3 py-2 min-h-[40px] border transition whitespace-nowrap {{ $showDeleted ? 'bg-rose-600 text-white border-rose-600' : 'text-rose-700 bg-rose-50 border-rose-200 hover:bg-rose-100' }}">🗑 {{ $showDeleted ? 'ກັບຄືນ' : 'Deleted' }}</button>@endif
                    @can('deposit.create')<a href="{{ route('deposit.create') }}" wire:navigate class="text-sm font-medium text-white bg-indigo-600 rounded-lg px-3.5 py-2 min-h-[40px] inline-flex items-center hover:bg-indigo-700 transition shadow-sm whitespace-nowrap">+ Deposit</a>@endcan
                </div>
            </div>

            @include('partials._status-chips', ['chips' => $chips, 'current' => $statusFilter, 'trailing' => number_format($records->total()).' records'])
        </div>{{-- /frozen header group --}}

        @if (session('ok'))<div class="text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-2.5 mb-3">{{ session('ok') }}</div>@endif

        {{-- Desktop table --}}
        <div class="hidden md:block bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-auto max-h-[calc(100vh-16rem)]">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 z-10 bg-slate-50 text-slate-500 border-b border-gray-200">
                        <tr class="text-[11px] uppercase tracking-wide">
                            <th class="text-left font-semibold px-4 py-2.5 whitespace-nowrap">ໄອດີ (DP)</th>
                            <th class="text-left font-semibold px-4 py-2.5">ເຈົ້າຂອງ</th>
                            <th class="text-left font-semibold px-4 py-2.5 min-w-[13rem]">ເຄື່ອງຝາກ</th>
                            <th class="text-center font-semibold px-4 py-2.5 whitespace-nowrap">ຈຳນວນ</th>
                            <th class="text-left font-semibold px-4 py-2.5 whitespace-nowrap">ລະຫັດເຄື່ອງ</th>
                            <th class="text-left font-semibold px-4 py-2.5 whitespace-nowrap">ຊັບສິນ</th>
                            <th class="text-left font-semibold px-4 py-2.5 w-56 min-w-[12rem] max-w-[16rem]">ບ່ອນເກັບ</th>
                            <th class="text-left font-semibold px-4 py-2.5 whitespace-nowrap">ວັນທີຝາກ</th>
                            <th class="text-left font-semibold px-4 py-2.5 whitespace-nowrap">ສະຖານະພາບ</th>
                            <th class="text-left font-semibold px-4 py-2.5 whitespace-nowrap">ສະຖານະ</th>
                            <th class="text-right font-semibold px-4 py-2.5 whitespace-nowrap">ຈັດການ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($records as $r)
                            @php [$lbl, $cls] = $statusMeta($r->status); $first = $r->items->first(); $ph = $first?->photos->first(); @endphp
                            <tr wire:key="dp-{{ $r->id }}" class="hover:bg-sky-50/40 transition">
                                <td class="px-4 py-2.5 align-top whitespace-nowrap"><a href="{{ route('deposit.show', $r) }}" wire:navigate class="font-mono text-sm font-medium text-indigo-600 hover:underline">{{ $r->request_number }}</a></td>
                                <td class="px-4 py-2.5 align-top"><div class="font-semibold text-gray-800">{{ $r->owner_name }}</div><div class="text-xs text-gray-400">{{ $r->unit?->name ?? $r->owner_email }}</div></td>
                                <td class="px-4 py-2.5 align-top min-w-[13rem]">
                                    <div class="flex gap-2.5">
                                        @if ($ph)<img src="{{ $ph->url }}" alt="" class="w-10 h-10 rounded-lg object-cover border border-gray-200 shrink-0" />
                                        @else<div class="w-10 h-10 rounded-lg bg-gray-50 border border-gray-200 shrink-0 flex items-center justify-center text-gray-300 text-lg">📦</div>@endif
                                        <div class="min-w-0">
                                            <div class="font-medium text-gray-800 truncate max-w-xs">{{ $first?->item_name ?? '—' }}@if ($r->items->count() > 1) <span class="text-gray-400 text-xs font-normal">+{{ $r->items->count() - 1 }}</span>@endif</div>
                                            @if ($r->item_category)<div class="text-xs text-gray-400">{{ Str::limit($r->item_category, 30) }}</div>@endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-2.5 align-top text-center whitespace-nowrap">
                                    <span class="font-semibold text-gray-800 tabular-nums">{{ $r->items->sum('qty') }}</span>
                                    @if ($first?->unit)<span class="text-xs text-gray-400"> {{ $first->unit }}</span>@endif
                                    @if ($r->items->count() > 1)<div class="text-[11px] text-gray-400">{{ $r->items->count() }} ລາຍການ</div>@endif
                                </td>
                                <td class="px-4 py-2.5 align-top text-xs whitespace-nowrap">@if ($first?->asset_code)<span class="font-mono bg-gray-50 text-gray-600 border border-gray-200 rounded px-1.5 py-0.5">{{ $first->asset_code }}</span>@if ($r->items->count() > 1)<span class="text-gray-300"> …</span>@endif @else<span class="text-gray-300">—</span>@endif</td>
                                <td class="px-4 py-2.5 align-top text-xs whitespace-nowrap font-mono {{ $first?->fixed_asset_no ? 'text-gray-600' : 'text-gray-300' }}">{{ $first?->fixed_asset_no ?: '—' }}</td>
                                <td class="px-4 py-2.5 align-top text-xs text-gray-600 w-56 min-w-[12rem] max-w-[16rem]">
                                    @php $storage = collect([$r->storage_location, $r->storage_shelf_label])->filter()->implode(' / '); @endphp
                                    <span class="block whitespace-normal break-words line-clamp-3" title="{{ $storage }}">{{ $storage ?: '—' }}</span>
                                </td>
                                <td class="px-4 py-2.5 align-top text-xs whitespace-nowrap">
                                    <div class="text-gray-700 font-medium">{{ $r->deposit_date?->format('M d, Y') }}</div>
                                    <div class="text-[10px] uppercase tracking-wide text-gray-400 mt-0.5">{{ $r->request_type === 'pre_request' ? 'PRE-REQUEST' : 'WALK-IN' }}</div>
                                </td>
                                <td class="px-4 py-2.5 align-top whitespace-nowrap">
                                    @php $conds = $r->items->pluck('condition_status')->filter(fn ($c) => $c && $c !== 'in_service')->unique(); @endphp
                                    @forelse ($conds as $cs)
                                        <span class="inline-block text-xs rounded-full px-2 py-0.5 {{ \App\Support\ConditionStatus::badge($cs) }}">{{ \App\Support\ConditionStatus::shortLabel($cs) }}</span>
                                    @empty
                                        <span class="text-xs text-emerald-600">ໃຊ້ ດີ</span>
                                    @endforelse
                                </td>
                                <td class="px-4 py-2.5 align-top whitespace-nowrap"><span class="inline-flex items-center gap-1 text-xs font-semibold rounded-full px-2.5 py-1 {{ $cls }}">{{ $lbl }}</span></td>
                                <td class="px-4 py-2.5 align-top whitespace-nowrap text-right">
                                    @if ($showDeleted)
                                        <button wire:click="restore({{ $r->id }})" wire:confirm="ກູ້ຄືນລາຍການນີ້?" class="text-xs font-medium text-emerald-700 border border-emerald-200 rounded-lg px-3 py-1.5 hover:bg-emerald-50 transition inline-block">↩ ກູ້ຄືນ</button>
                                        @if ($r->deleted_reason)<div class="text-xs text-gray-400 mt-1 max-w-[12rem] truncate ml-auto" title="{{ $r->deleted_reason }}">{{ $r->deleted_reason }}</div>@endif
                                    @else
                                        @can('deposit.edit')<a href="{{ route('deposit.show', [$r, 'edit' => 1]) }}" wire:navigate class="text-xs font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-1.5 hover:bg-amber-100 transition inline-block mr-1">✏️ ແກ້ໄຂ</a>@endcan
                                        <a href="{{ route('deposit.show', $r) }}" wire:navigate class="text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg px-3 py-1.5 hover:bg-gray-50 transition inline-block">ເບິ່ງ</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="px-4 py-14 text-center text-gray-400"><div class="text-4xl mb-2">📦</div>ຍັງບໍ່ມີລາຍການຝາກ</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile cards --}}
        <div class="md:hidden space-y-2.5">
            @forelse ($records as $r)
                @php [$lbl, $cls] = $statusMeta($r->status); $first = $r->items->first(); $ph = $first?->photos->first(); $tag = $showDeleted ? 'div' : 'a'; @endphp
                <{{ $tag }} @if (! $showDeleted) href="{{ route('deposit.show', $r) }}" wire:navigate @endif wire:key="mdp-{{ $r->id }}" class="block bg-white border border-gray-200 rounded-xl shadow-sm p-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex gap-2.5 min-w-0">
                            @if ($ph)<img src="{{ $ph->url }}" alt="" class="w-10 h-10 rounded-lg object-cover border border-gray-200 shrink-0" />
                            @else<div class="w-10 h-10 rounded-lg bg-gray-50 border border-gray-200 shrink-0 flex items-center justify-center text-gray-300 text-lg">📦</div>@endif
                            <div class="min-w-0"><div class="font-mono text-xs text-indigo-600">{{ $r->request_number }}</div><div class="font-semibold text-gray-800">{{ $r->owner_name }}</div><div class="text-xs text-gray-500 truncate">{{ $first?->item_name }} · Qty {{ $r->items->sum('qty') }}</div>@if ($first?->asset_code || $first?->fixed_asset_no)<div class="text-[11px] text-gray-400 truncate">@if ($first->asset_code)ທ.ເຄື່ອງ: {{ $first->asset_code }}@endif@if ($first->fixed_asset_no) · ຊັບສິນ: {{ $first->fixed_asset_no }}@endif</div>@endif</div>
                        </div>
                        <span class="text-xs font-semibold rounded-full px-2 py-0.5 {{ $cls }} shrink-0">{{ $lbl }}</span>
                    </div>
                    <div class="text-xs text-gray-400 mt-2">{{ $r->deposit_date?->format('M d, Y') }} · {{ collect([$r->storage_location, $r->storage_shelf_label])->filter()->implode(' / ') ?: 'ຍັງບໍ່ກຳນົດບ່ອນເກັບ' }}</div>
                    @if ($showDeleted)
                        <div class="flex items-center justify-between gap-2 mt-2">
                            @if ($r->deleted_reason)<span class="text-xs text-gray-400 truncate">{{ $r->deleted_reason }}</span>@else<span></span>@endif
                            <button wire:click="restore({{ $r->id }})" wire:confirm="ກູ້ຄືນລາຍການນີ້?" class="text-xs font-medium text-emerald-700 border border-emerald-200 rounded-lg px-3 py-1.5 hover:bg-emerald-50 shrink-0">↩ ກູ້ຄືນ</button>
                        </div>
                    @endif
                </{{ $tag }}>
            @empty
                <div class="text-center text-gray-400 py-10"><div class="text-4xl mb-2">📦</div>ຍັງບໍ່ມີລາຍການຝາກ</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $records->links() }}</div>
    </div>
</div>
