@php
    $statusMeta = fn ($s) => match ($s) {
        'draft' => ['DRAFT', 'bg-gray-100 text-gray-600'],
        'submitted' => ['SUBMITTED', 'bg-blue-50 text-blue-700'],
        'approved' => ['APPROVED', 'bg-sky-50 text-sky-700'],
        'validated' => ['VALIDATED', 'bg-cyan-50 text-cyan-700'],
        'dispatched' => ['DISPATCHED', 'bg-amber-50 text-amber-700'],
        'received' => ['RECEIVED', 'bg-emerald-50 text-emerald-700'],
        'completed' => ['COMPLETED', 'bg-emerald-100 text-emerald-800'],
        'rejected' => ['REJECTED', 'bg-red-50 text-red-700'],
        'cancelled' => ['CANCELLED', 'bg-gray-100 text-gray-400'],
        default => [strtoupper($s), 'bg-gray-100 text-gray-600'],
    };
@endphp

<div class="pb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="sticky top-16 z-20 bg-gray-100 flex flex-col gap-2 py-3 lg:flex-row lg:items-center lg:justify-between lg:gap-3 lg:flex-nowrap">
            <div class="flex flex-wrap items-center gap-2 min-w-0">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ MR/ຜູ້ເບີກ/ຈຸດປະສົງ…" class="w-52 rounded-md border-gray-300 shadow-sm text-sm" />
                <select wire:model.live="statusFilter" class="w-36 rounded-md border-gray-300 text-sm">
                    <option value="">All Statuses</option>
                    @foreach (['draft', 'submitted', 'approved', 'validated', 'dispatched', 'received', 'completed', 'rejected', 'cancelled'] as $st)
                        <option value="{{ $st }}">{{ $st }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if ($canManageDeleted)<button wire:click="toggleDeleted" class="text-sm rounded-md px-2.5 py-2 min-h-[40px] border whitespace-nowrap {{ $showDeleted ? 'bg-red-600 text-white border-red-600' : 'text-red-700 bg-red-50 border-red-200 hover:bg-red-100' }}">🗑 {{ $showDeleted ? 'ກັບคืน' : 'Deleted' }}</button>@endif
                @can('request.create')<a href="{{ route('request.create') }}" wire:navigate class="text-sm text-white bg-indigo-600 rounded-md px-2.5 py-2 min-h-[40px] inline-flex items-center hover:bg-indigo-700 whitespace-nowrap">+ Request</a>@endcan
            </div>
        </div>

        @include('partials._status-chips', ['chips' => $chips, 'current' => $statusFilter])

        <p class="text-sm text-gray-500 mb-2">{{ number_format($records->total()) }} records</p>
        @if (session('ok'))<div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 mb-2">{{ session('ok') }}</div>@endif

        {{-- Desktop --}}
        <div class="hidden md:block bg-white border border-gray-100 rounded-lg">
            <table class="w-full text-sm">
                <thead class="sticky top-[116px] z-10 bg-gray-100 text-gray-700 border-b border-gray-200 shadow-sm">
                    <tr>
                        <th class="text-left font-semibold px-4 py-3">ໄອດີ <span class="text-gray-400">(MR No.)</span></th>
                        <th class="text-left font-semibold px-4 py-3">ຜູ້ເບີກ <span class="text-gray-400">(Requester)</span></th>
                        <th class="text-left font-semibold px-4 py-3">ລາຍການ <span class="text-gray-400">(Items)</span></th>
                        <th class="text-left font-semibold px-4 py-3">ມູນຄ່າ <span class="text-gray-400">(Grand total)</span></th>
                        <th class="text-left font-semibold px-4 py-3">Supplier</th>
                        <th class="text-left font-semibold px-4 py-3">ສະຖานะ <span class="text-gray-400">(Status)</span></th>
                        <th class="text-left font-semibold px-4 py-3">ລາຍລະອຽດ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($records as $r)
                        @php [$lbl, $cls] = $statusMeta($r->status); @endphp
                        <tr wire:key="mr-{{ $r->id }}" class="hover:bg-gray-50">
                            <td class="px-4 py-3 align-top"><a href="{{ route('request.show', $r) }}" wire:navigate class="font-mono text-sm text-indigo-600 hover:underline">{{ $r->request_number }}</a></td>
                            <td class="px-4 py-3 align-top"><div class="font-semibold text-gray-800">{{ $r->requester_name }}</div><div class="text-xs text-gray-400">{{ $r->unit?->name ?? $r->requester_email }}</div></td>
                            <td class="px-4 py-3 align-top text-gray-600">{{ $r->items->count() }} ລາຍການ<div class="text-xs text-gray-400">Qty {{ $r->items->sum('quantity') }}</div></td>
                            <td class="px-4 py-3 align-top text-gray-700"><span class="font-medium">{{ number_format($r->grand_total, 2) }}</span> <span class="text-xs text-gray-400">{{ $r->currency }}</span>@if ($r->vat_enabled)<div class="text-xs text-gray-400">+VAT {{ rtrim(rtrim(number_format($r->vat_rate, 2), '0'), '.') }}%</div>@endif</td>
                            <td class="px-4 py-3 align-top text-xs text-gray-600">{{ $r->supplier?->name ?? '—' }}</td>
                            <td class="px-4 py-3 align-top"><span class="inline-flex items-center gap-1 text-xs font-medium rounded-full px-2.5 py-1 {{ $cls }}">{{ $lbl }}</span></td>
                            <td class="px-4 py-3 align-top">
                                @if ($showDeleted)
                                    <button wire:click="restore({{ $r->id }})" wire:confirm="ກູ້คืน?" class="text-xs text-emerald-700 border border-emerald-300 rounded-md px-3 py-1.5 hover:bg-emerald-50">↩ ກູ້คืน</button>
                                @else
                                    <a href="{{ route('request.show', $r) }}" wire:navigate class="text-xs text-gray-700 border border-gray-300 rounded-md px-3 py-1.5 hover:bg-gray-50 inline-block">View Details</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">ຍັງບໍ່ມີໃບເບີກ</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile --}}
        <div class="md:hidden space-y-2">
            @forelse ($records as $r)
                @php [$lbl, $cls] = $statusMeta($r->status); $tag = $showDeleted ? 'div' : 'a'; @endphp
                <{{ $tag }} @if (! $showDeleted) href="{{ route('request.show', $r) }}" wire:navigate @endif wire:key="mmr-{{ $r->id }}" class="block bg-white border border-gray-100 rounded-lg p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-mono text-xs text-indigo-600">{{ $r->request_number }}</div>
                            <div class="font-semibold text-gray-800">{{ $r->requester_name }}</div>
                            <div class="text-xs text-gray-500">{{ $r->items->count() }} ລາຍການ · {{ number_format($r->grand_total, 2) }} {{ $r->currency }}</div>
                        </div>
                        <span class="text-xs font-medium rounded-full px-2 py-0.5 {{ $cls }} shrink-0">{{ $lbl }}</span>
                    </div>
                    @if ($showDeleted)<div class="mt-2 text-right"><button wire:click="restore({{ $r->id }})" wire:confirm="ກູ້คืน?" class="text-xs text-emerald-700 border border-emerald-300 rounded-md px-3 py-1.5">↩ ກູ້คืน</button></div>@endif
                </{{ $tag }}>
            @empty
                <div class="text-center text-gray-400 py-6">ຍັງບໍ່ມີໃບເບີກ</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $records->links() }}</div>
    </div>
</div>
