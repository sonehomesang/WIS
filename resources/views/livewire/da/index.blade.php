@php
    $statusMeta = fn ($s) => match ($s) {
        'draft' => ['DRAFT', 'bg-gray-100 text-gray-600'],
        'submitted' => ['SUBMITTED', 'bg-blue-50 text-blue-700'],
        'purchasing_review' => ['PURCHASING', 'bg-violet-50 text-violet-700'],
        'pending_approval' => ['PENDING APPROVAL', 'bg-amber-50 text-amber-700'],
        'resolved' => ['RESOLVED', 'bg-emerald-100 text-emerald-800'],
        'cancelled' => ['CANCELLED', 'bg-gray-100 text-gray-400'],
        default => [strtoupper($s), 'bg-gray-100 text-gray-600'],
    };
    $typeLabel = ['incorrect_supplied' => 'ສົ່ງຜິດ', 'oversupplied' => 'ເກີນ', 'undersupplied' => 'ຂາດ', 'damaged' => 'ເສຍ', 'no_paperwork' => 'ບໍ່ມີເອກະສານ', 'other' => 'ອື່ນ'];
@endphp

<div class="pb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- frozen header group: toolbar + chips freeze together --}}
        <div class="sticky top-16 z-30 bg-gray-100">
        {{-- toolbar --}}
        <div class="flex flex-col gap-2 py-3 lg:flex-row lg:items-center lg:justify-between lg:gap-3 lg:flex-nowrap">
            <div class="flex flex-wrap items-center gap-2 min-w-0">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ DA/PO/supplier…" class="w-52 rounded-md border-gray-300 shadow-sm text-sm" />
                <select wire:model.live="statusFilter" class="w-44 rounded-md border-gray-300 text-sm">
                    <option value="">All Statuses</option>
                    @foreach (['draft', 'submitted', 'purchasing_review', 'pending_approval', 'resolved', 'cancelled'] as $st)<option value="{{ $st }}">{{ $st }}</option>@endforeach
                </select>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if ($canManageDeleted)<button wire:click="toggleDeleted" class="text-sm rounded-md px-2.5 py-2 min-h-[40px] border whitespace-nowrap {{ $showDeleted ? 'bg-red-600 text-white border-red-600' : 'text-red-700 bg-red-50 border-red-200 hover:bg-red-100' }}">🗑 {{ $showDeleted ? 'ກັບຄືນ' : 'Deleted' }}</button>@endif
                @can('da.create')<a href="{{ route('da.create') }}" wire:navigate class="text-sm text-white bg-indigo-600 rounded-md px-2.5 py-2 min-h-[40px] inline-flex items-center hover:bg-indigo-700 whitespace-nowrap">+ DA</a>@endcan
            </div>
        </div>

        @include('partials._status-chips', ['chips' => $chips, 'current' => $statusFilter, 'trailing' => number_format($records->total()).' records'])
        </div>{{-- /frozen header group --}}

        @if (session('ok'))<div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 mb-2">{{ session('ok') }}</div>@endif

        <div class="hidden md:block bg-white border border-gray-100 rounded-lg overflow-auto max-h-[calc(100vh-15rem)]">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 bg-gray-50 text-gray-700 border-b border-gray-200 shadow-sm">
                    <tr>
                        <th class="text-left font-semibold px-4 py-2 whitespace-nowrap">ໄອດີ <span class="text-gray-400">(DA No.)</span></th>
                        <th class="text-left font-semibold px-4 py-2 whitespace-nowrap">PO</th>
                        <th class="text-left font-semibold px-4 py-2">Supplier</th>
                        <th class="text-left font-semibold px-4 py-2 w-full">ປະເພດຄວາມຜິດ</th>
                        <th class="text-left font-semibold px-4 py-2 whitespace-nowrap">ວັນທີ</th>
                        <th class="text-left font-semibold px-4 py-2 whitespace-nowrap">ສະຖານະ</th>
                        <th class="text-left font-semibold px-4 py-2 whitespace-nowrap">ລາຍລະອຽດ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($records as $r)
                        @php [$lbl, $cls] = $statusMeta($r->status); @endphp
                        <tr wire:key="da-{{ $r->id }}" class="hover:bg-gray-50">
                            <td class="px-4 py-2 align-top whitespace-nowrap"><a href="{{ route('da.show', $r) }}" wire:navigate class="font-mono text-sm text-indigo-600 hover:underline">{{ $r->da_number }}</a></td>
                            <td class="px-4 py-2 align-top text-gray-700 whitespace-nowrap">{{ $r->po_number ?? '—' }}</td>
                            <td class="px-4 py-2 align-top text-xs text-gray-600">{{ $r->supplier_name ?? $r->supplier?->name ?? '—' }}</td>
                            <td class="px-4 py-2 align-top text-xs text-gray-600 w-full">{{ collect($r->discrepancy_types ?? [])->map(fn ($t) => $typeLabel[$t] ?? $t)->implode(', ') ?: '—' }}</td>
                            <td class="px-4 py-2 align-top text-xs text-gray-500 whitespace-nowrap">{{ $r->date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-2 align-top whitespace-nowrap"><span class="inline-flex items-center gap-1 text-xs font-medium rounded-full px-2.5 py-1 {{ $cls }}">{{ $lbl }}</span>@if ($r->next_step === 'oga')<div class="text-xs text-sky-600 mt-1">→ OGA</div>@endif</td>
                            <td class="px-4 py-2 align-top whitespace-nowrap">
                                @if ($showDeleted)
                                    <button wire:click="restore({{ $r->id }})" wire:confirm="ກູ້ຄືນ?" class="text-xs text-emerald-700 border border-emerald-300 rounded-md px-3 py-1.5 hover:bg-emerald-50">↩ ກູ້ຄືນ</button>
                                @else
                                    <a href="{{ route('da.show', $r) }}" wire:navigate class="text-xs text-gray-700 border border-gray-300 rounded-md px-3 py-1.5 hover:bg-gray-50 inline-block">View Details</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">ຍັງບໍ່ມີ DA</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="md:hidden space-y-2">
            @forelse ($records as $r)
                @php [$lbl, $cls] = $statusMeta($r->status); $tag = $showDeleted ? 'div' : 'a'; @endphp
                <{{ $tag }} @if (! $showDeleted) href="{{ route('da.show', $r) }}" wire:navigate @endif wire:key="mda-{{ $r->id }}" class="block bg-white border border-gray-100 rounded-lg p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-mono text-xs text-indigo-600">{{ $r->da_number }}</div>
                            <div class="font-semibold text-gray-800">{{ $r->supplier_name ?? '—' }}</div>
                            <div class="text-xs text-gray-500">PO {{ $r->po_number ?? '—' }} · {{ $r->date?->format('d/m/Y') }}</div>
                        </div>
                        <span class="text-xs font-medium rounded-full px-2 py-0.5 {{ $cls }} shrink-0">{{ $lbl }}</span>
                    </div>
                    @if ($showDeleted)<div class="mt-2 text-right"><button wire:click="restore({{ $r->id }})" wire:confirm="ກູ້ຄືນ?" class="text-xs text-emerald-700 border border-emerald-300 rounded-md px-3 py-1.5">↩ ກູ້ຄືນ</button></div>@endif
                </{{ $tag }}>
            @empty
                <div class="text-center text-gray-400 py-6">ຍັງບໍ່ມີ DA</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $records->links() }}</div>
    </div>
</div>
