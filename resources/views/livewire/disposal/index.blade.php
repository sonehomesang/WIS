@php
    $badge = fn ($s) => match ($s) {
        'draft' => 'bg-gray-100 text-gray-600',
        'committee_review', 'technical_review', 'manager_review', 'executive_review' => 'bg-amber-50 text-amber-700',
        'approved' => 'bg-sky-50 text-sky-700',
        'disposed' => 'bg-emerald-100 text-emerald-800',
        'rejected' => 'bg-red-50 text-red-700',
        'cancelled' => 'bg-gray-100 text-gray-400',
        default => 'bg-gray-100 text-gray-600',
    };
@endphp

<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="sticky top-16 z-30 bg-gray-100">
            <div class="flex flex-col gap-2 py-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">ຈຳໜ່າຍ ເຄື່ອງ <span class="text-gray-400 text-sm font-normal">· Disposal</span></h2>
                    <p class="text-sm text-gray-400">{{ number_format($records->total()) }} ໃບ</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ DS/ຫົວຂໍ້/ຜູ້ເຮັດ…" class="w-44 rounded-md border-gray-300 text-sm" />
                    <select wire:model.live="statusFilter" class="w-36 rounded-md border-gray-300 text-sm">
                        <option value="">ທຸກ ສະຖານະ</option>
                        @foreach ($statusLabels as $k => $lbl)<option value="{{ $k }}">{{ $lbl }}</option>@endforeach
                    </select>
                    <a href="{{ route('disposal.summary') }}" wire:navigate class="text-sm text-gray-700 border border-gray-300 rounded-md px-2.5 py-2 min-h-[40px] inline-flex items-center hover:bg-gray-50 whitespace-nowrap">📊 ລິສ ລວມ</a>
                    @if ($canManageDeleted)<button wire:click="toggleDeleted" class="text-sm rounded-md px-2.5 py-2 min-h-[40px] border whitespace-nowrap {{ $showDeleted ? 'bg-red-600 text-white border-red-600' : 'text-red-700 bg-red-50 border-red-200 hover:bg-red-100' }}">🗑 {{ $showDeleted ? 'ກັບຄືນ' : 'Deleted' }}</button>@endif
                    @can('disposal.create')<a href="{{ route('disposal.create') }}" wire:navigate class="text-sm text-white bg-indigo-600 rounded-md px-3 py-2 min-h-[40px] inline-flex items-center hover:bg-indigo-700 whitespace-nowrap">+ ຈຳໜ່າຍ</a>@endcan
                </div>
            </div>
        </div>

        @if (session('ok'))<div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 mb-2">{{ session('ok') }}</div>@endif

        <div class="bg-white border border-gray-100 rounded-lg overflow-auto max-h-[calc(100vh-14rem)]">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 bg-gray-50 text-gray-700 border-b border-gray-200 shadow-sm">
                    <tr>
                        <th class="text-left font-semibold px-4 py-2 whitespace-nowrap">ໄອດີ (DS)</th>
                        <th class="text-left font-semibold px-4 py-2 w-full">ຫົວຂໍ້</th>
                        <th class="text-left font-semibold px-4 py-2 whitespace-nowrap">ຈຳນວນ</th>
                        <th class="text-left font-semibold px-4 py-2 whitespace-nowrap">ຜູ້ ເຮັດລິສ</th>
                        <th class="text-left font-semibold px-4 py-2 whitespace-nowrap">ວັນທີ</th>
                        <th class="text-left font-semibold px-4 py-2 whitespace-nowrap">ສະຖານະ</th>
                        <th class="text-left font-semibold px-4 py-2 whitespace-nowrap">ຈັດການ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($records as $r)
                        <tr wire:key="ds-{{ $r->id }}" class="hover:bg-gray-50">
                            <td class="px-4 py-2 align-top whitespace-nowrap"><a href="{{ route('disposal.show', $r) }}" wire:navigate class="font-mono text-indigo-600 hover:underline">{{ $r->request_number }}</a></td>
                            <td class="px-4 py-2 align-top w-full">{{ $r->title ?: '—' }}@if ($r->department)<span class="text-xs text-gray-400"> · {{ $r->department->name }}</span>@endif</td>
                            <td class="px-4 py-2 align-top whitespace-nowrap text-gray-600">{{ $r->items_count }} ລາຍການ</td>
                            <td class="px-4 py-2 align-top whitespace-nowrap text-gray-600">{{ $r->prepared_by_name ?? '—' }}</td>
                            <td class="px-4 py-2 align-top whitespace-nowrap text-gray-500 text-xs">{{ $r->created_at?->format('d/m/Y') }}</td>
                            <td class="px-4 py-2 align-top whitespace-nowrap"><span class="inline-flex text-xs font-medium rounded-full px-2.5 py-1 {{ $badge($r->status) }}">{{ $statusLabels[$r->status] ?? $r->status }}</span></td>
                            <td class="px-4 py-2 align-top whitespace-nowrap">
                                @if ($showDeleted)
                                    <button wire:click="restore({{ $r->id }})" class="text-xs text-emerald-700 border border-emerald-300 rounded-md px-3 py-1.5 hover:bg-emerald-50">↩ ກູ້ຄືນ</button>
                                    @if ($r->deleted_reason)<div class="text-xs text-gray-400 mt-1 max-w-[12rem] truncate" title="{{ $r->deleted_reason }}">{{ $r->deleted_reason }}</div>@endif
                                @else
                                    <a href="{{ route('disposal.show', $r) }}" wire:navigate class="text-xs text-gray-700 border border-gray-300 rounded-md px-3 py-1.5 hover:bg-gray-50 inline-block">ເບິ່ງ</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">{{ $showDeleted ? 'ບໍ່ ມີ ໃບ ຈຳໜ່າຍ ທີ່ ຖືກ ລຶບ' : 'ຍັງ ບໍ່ ມີ ໃບ ຈຳໜ່າຍ — ກົດ + ຈຳໜ່າຍ' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials._delete-modal', ['title' => 'ລຶບ ໃບ ຈຳໜ່າຍ ນີ້?', 'subtitle' => $this->deletingRecord?->request_number])

        <div class="mt-4">{{ $records->links() }}</div>
    </div>
</div>
