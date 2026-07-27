@php
    $statusMeta = fn ($s) => match ($s) {
        'draft' => ['DRAFT', 'bg-gray-100 text-gray-600'],
        'submitted' => ['SUBMITTED', 'bg-blue-50 text-blue-700'],
        'accepted' => ['ACCEPTED', 'bg-cyan-50 text-cyan-700'],
        'stored' => ['STORED', 'bg-emerald-50 text-emerald-700'],
        'needs_fix' => ['NEEDS FIX', 'bg-amber-50 text-amber-700'],
        'claimed' => ['CLAIMED', 'bg-emerald-100 text-emerald-800'],
        'cancelled' => ['CANCELLED', 'bg-gray-100 text-gray-400'],
        default => [strtoupper($s), 'bg-gray-100 text-gray-600'],
    };
@endphp

<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8">
        {{-- frozen header group: toolbar + chips freeze together --}}
        <div class="sticky top-16 z-30 bg-gray-100">
        {{-- toolbar --}}
        <div class="flex flex-col gap-2 py-3 lg:flex-row lg:items-center lg:justify-between lg:gap-3 lg:flex-nowrap">
            <div class="flex flex-wrap items-center gap-2 min-w-0">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ DP/ຊື່ເຈົ້າຂອງ…" class="w-44 rounded-md border-gray-300 shadow-sm text-sm" />
                <select wire:model.live="statusFilter" class="w-36 rounded-md border-gray-300 text-sm">
                    <option value="">All Statuses</option>
                    <option value="draft">draft</option><option value="submitted">submitted</option>
                    <option value="accepted">accepted</option><option value="stored">stored</option>
                    <option value="needs_fix">needs_fix</option><option value="claimed">claimed</option><option value="cancelled">cancelled</option>
                </select>
                <select wire:model.live="typeFilter" class="w-32 rounded-md border-gray-300 text-sm">
                    <option value="">All Types</option>
                    <option value="walk_in">Walk-in</option><option value="pre_request">Pre-request</option>
                </select>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if ($canManageDeleted)<button wire:click="toggleDeleted" class="text-sm rounded-md px-2.5 py-2 min-h-[40px] border whitespace-nowrap {{ $showDeleted ? 'bg-red-600 text-white border-red-600' : 'text-red-700 bg-red-50 border-red-200 hover:bg-red-100' }}">🗑 {{ $showDeleted ? 'ກັບຄືນ' : 'Deleted' }}</button>@endif
                @can('deposit.create')<a href="{{ route('deposit.create') }}" wire:navigate class="text-sm text-white bg-indigo-600 rounded-md px-2.5 py-2 min-h-[40px] inline-flex items-center hover:bg-indigo-700 whitespace-nowrap">+ Deposit</a>@endcan
            </div>
        </div>

        @include('partials._status-chips', ['chips' => $chips, 'current' => $statusFilter, 'trailing' => number_format($records->total()).' records'])
        </div>{{-- /frozen header group --}}

        @if (session('ok'))<div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 mb-2">{{ session('ok') }}</div>@endif

        {{-- Desktop table --}}
        <div class="hidden md:block bg-white border border-gray-100 rounded-lg overflow-auto max-h-[calc(100vh-15rem)]">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 bg-gray-50 text-gray-700 border-b border-gray-200 shadow-sm">
                    <tr>
                        <th class="text-left font-semibold px-4 py-2 whitespace-nowrap">ໄອດີ <span class="text-gray-400">(DP No.)</span></th>
                        <th class="text-left font-semibold px-4 py-2">ເຈົ້າຂອງ <span class="text-gray-400">(Owner)</span></th>
                        <th class="text-left font-semibold px-4 py-2 w-full">ເຄື່ອງຝາກ <span class="text-gray-400">(Items)</span></th>
                        <th class="text-left font-semibold px-4 py-2">ບ່ອນເກັບ <span class="text-gray-400">(Storage)</span></th>
                        <th class="text-left font-semibold px-4 py-2 whitespace-nowrap">ວັນທີຝາກ <span class="text-gray-400">(Date)</span></th>
                        <th class="text-left font-semibold px-4 py-2 whitespace-nowrap">ສະຖານະ <span class="text-gray-400">(Status)</span></th>
                        <th class="text-left font-semibold px-4 py-2 whitespace-nowrap">ລາຍລະອຽດ <span class="text-gray-400">(Actions)</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($records as $r)
                        @php [$lbl, $cls] = $statusMeta($r->status); $first = $r->items->first(); $ph = $first?->photos->first(); @endphp
                        <tr wire:key="dp-{{ $r->id }}" class="hover:bg-gray-50">
                            <td class="px-4 py-2 align-top whitespace-nowrap"><a href="{{ route('deposit.show', $r) }}" wire:navigate class="font-mono text-sm text-indigo-600 hover:underline">{{ $r->request_number }}</a></td>
                            <td class="px-4 py-2 align-top"><div class="font-semibold text-gray-800">{{ $r->owner_name }}</div><div class="text-xs text-gray-400">{{ $r->unit?->name ?? $r->owner_email }}</div></td>
                            <td class="px-4 py-2 align-top w-full">
                                <div class="flex gap-2">
                                    @if ($ph)<img src="{{ $ph->url }}" alt="" class="w-10 h-10 rounded-lg object-cover border border-gray-200 shrink-0" />
                                    @else<div class="w-10 h-10 rounded-lg bg-gray-100 border border-gray-200 shrink-0 flex items-center justify-center text-gray-300 text-xs">📦</div>@endif
                                    <div class="min-w-0">
                                        <div class="font-medium text-gray-800 truncate max-w-xs">{{ $first?->item_name ?? '—' }}@if ($r->items->count() > 1) <span class="text-gray-400 text-xs">+{{ $r->items->count() - 1 }}</span>@endif</div>
                                        <div class="text-xs text-gray-400">Qty: {{ $r->items->sum('qty') }}@if ($r->item_category) · {{ Str::limit($r->item_category, 24) }}@endif</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-2 align-top text-xs text-gray-600">{{ collect([$r->storage_location, $r->storage_shelf_label])->filter()->implode(' / ') ?: '—' }}</td>
                            <td class="px-4 py-2 align-top text-xs whitespace-nowrap">
                                <div class="text-gray-700 font-medium">{{ $r->deposit_date?->format('M d, Y') }}</div>
                                <div class="text-gray-400 mt-1">{{ $r->request_type === 'pre_request' ? 'PRE-REQUEST' : 'WALK-IN' }}</div>
                            </td>
                            <td class="px-4 py-2 align-top whitespace-nowrap"><span class="inline-flex items-center gap-1 text-xs font-medium rounded-full px-2.5 py-1 {{ $cls }}">{{ $lbl }}</span></td>
                            <td class="px-4 py-2 align-top whitespace-nowrap">
                                @if ($showDeleted)
                                    <button wire:click="restore({{ $r->id }})" wire:confirm="ກູ້ຄືນລາຍການນີ້?" class="text-xs text-emerald-700 border border-emerald-300 rounded-md px-3 py-1.5 hover:bg-emerald-50 inline-block">↩ ກູ້ຄືນ</button>
                                    @if ($r->deleted_reason)<div class="text-xs text-gray-400 mt-1 max-w-[12rem] truncate" title="{{ $r->deleted_reason }}">{{ $r->deleted_reason }}</div>@endif
                                @else
                                    <a href="{{ route('deposit.show', $r) }}" wire:navigate class="text-xs text-gray-700 border border-gray-300 rounded-md px-3 py-1.5 hover:bg-gray-50 inline-block">View Details</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">ຍັງບໍ່ມີລາຍການຝາກ</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="md:hidden space-y-2">
            @forelse ($records as $r)
                @php [$lbl, $cls] = $statusMeta($r->status); $first = $r->items->first(); $ph = $first?->photos->first(); $tag = $showDeleted ? 'div' : 'a'; @endphp
                <{{ $tag }} @if (! $showDeleted) href="{{ route('deposit.show', $r) }}" wire:navigate @endif wire:key="mdp-{{ $r->id }}" class="block bg-white border border-gray-100 rounded-lg p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex gap-2 min-w-0">
                            @if ($ph)<img src="{{ $ph->url }}" alt="" class="w-10 h-10 rounded-lg object-cover border border-gray-200 shrink-0" />@endif
                            <div class="min-w-0"><div class="font-mono text-xs text-indigo-600">{{ $r->request_number }}</div><div class="font-semibold text-gray-800">{{ $r->owner_name }}</div><div class="text-xs text-gray-500 truncate">{{ $first?->item_name }} · Qty {{ $r->items->sum('qty') }}</div></div>
                        </div>
                        <span class="text-xs font-medium rounded-full px-2 py-0.5 {{ $cls }} shrink-0">{{ $lbl }}</span>
                    </div>
                    <div class="text-xs text-gray-400 mt-1">{{ $r->deposit_date?->format('M d, Y') }} · {{ collect([$r->storage_location, $r->storage_shelf_label])->filter()->implode(' / ') ?: 'ຍັງບໍ່ກຳນົດບ່ອນເກັບ' }}</div>
                    @if ($showDeleted)
                        <div class="flex items-center justify-between gap-2 mt-2">
                            @if ($r->deleted_reason)<span class="text-xs text-gray-400 truncate">{{ $r->deleted_reason }}</span>@else<span></span>@endif
                            <button wire:click="restore({{ $r->id }})" wire:confirm="ກູ້ຄືນລາຍການນີ້?" class="text-xs text-emerald-700 border border-emerald-300 rounded-md px-3 py-1.5 hover:bg-emerald-50 shrink-0">↩ ກູ້ຄືນ</button>
                        </div>
                    @endif
                </{{ $tag }}>
            @empty
                <div class="text-center text-gray-400 py-6">ຍັງບໍ່ມີລາຍການຝາກ</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $records->links() }}</div>
    </div>
</div>
