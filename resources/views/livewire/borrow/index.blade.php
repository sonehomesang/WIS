@php
    $statusMeta = fn ($s) => match ($s) {
        'draft' => ['DRAFT', 'bg-gray-100 text-gray-600'],
        'acknowledged' => ['PENDING ACK', 'bg-blue-100 text-blue-700'],
        'approved' => ['APPROVED', 'bg-sky-100 text-sky-700'],
        'active' => ['IN USES', 'bg-indigo-100 text-indigo-700'],
        'overdue' => ['OVERDUE', 'bg-red-100 text-red-700'],
        'returned' => ['RETURNED', 'bg-emerald-100 text-emerald-700'],
        'cancelled' => ['CANCELLED', 'bg-gray-100 text-gray-400'],
        default => [strtoupper($s), 'bg-gray-100 text-gray-600'],
    };
    $typeLabel = fn ($t) => match ($t) {
        'new_inventory' => 'Inventory', 'tools_equipment' => 'Tools/Equip',
        'deposited_tools' => 'Deposited', 'others' => 'Others', default => $t,
    };
@endphp

<div class="pb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- frozen header group: toolbar + chips + count freeze together --}}
        <div class="sticky top-16 z-30 bg-gray-100">
        {{-- toolbar --}}
        <div class="flex flex-col gap-2 py-3 lg:flex-row lg:items-center lg:justify-between lg:gap-3 lg:flex-nowrap">
            <div class="flex flex-wrap items-center gap-2 min-w-0">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ BR/ຊື່ຜູ້ຢືມ…" class="w-40 rounded-md border-gray-300 shadow-sm text-sm" />
                <select wire:model.live="statusFilter" class="w-32 rounded-md border-gray-300 text-sm">
                    <option value="">All Statuses</option>
                    <option value="draft">draft</option><option value="acknowledged">acknowledged</option>
                    <option value="approved">approved</option><option value="active">active (in use)</option>
                    <option value="overdue">overdue</option><option value="returned">returned</option><option value="cancelled">cancelled</option>
                </select>
                <select wire:model.live="typeFilter" class="w-28 rounded-md border-gray-300 text-sm">
                    <option value="">All Types</option>
                    <option value="new_inventory">Inventory</option><option value="tools_equipment">Tools/Equip</option>
                    <option value="deposited_tools">Deposited</option><option value="others">Others</option>
                </select>
                <input type="date" wire:model.live="fromDate" class="w-36 rounded-md border-gray-300 text-sm" title="ຈາກວັນທີ" />
                <input type="date" wire:model.live="toDate" class="w-36 rounded-md border-gray-300 text-sm" title="ຫາວັນທີ" />
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if ($canDailyCheck)<button wire:click="runDailyCheck" wire:loading.attr="disabled" class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-md px-2.5 py-2 min-h-[40px] hover:bg-amber-100 whitespace-nowrap" title="ສົ່ງເຕືອນ ລາຍການ ໃກ້/ເກີນ ກຳນົດ">⏰ Daily Check</button>@endif
                @if ($canManageDeleted)<button wire:click="toggleDeleted" class="text-sm rounded-md px-2.5 py-2 min-h-[40px] border whitespace-nowrap {{ $showDeleted ? 'bg-red-600 text-white border-red-600' : 'text-red-700 bg-red-50 border-red-200 hover:bg-red-100' }}">🗑 {{ $showDeleted ? 'ກັບຄືນລາຍການປົກກະຕິ' : 'Deleted Log' }}</button>@endif
                @can('borrow.create')<a href="{{ route('borrow.create') }}" wire:navigate class="text-sm text-white bg-indigo-600 rounded-md px-2.5 py-2 min-h-[40px] inline-flex items-center hover:bg-indigo-700 whitespace-nowrap">+ Borrow Request</a>@endcan
            </div>
        </div>

        @include('partials._status-chips', ['chips' => $chips, 'current' => $statusFilter, 'trailing' => number_format($records->total()).' records'])
        </div>{{-- /frozen header group --}}

        @if (session('ok'))<div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 mb-2">{{ session('ok') }}</div>@endif

        {{-- Desktop table — internal scroll, frozen column header --}}
        <div class="hidden md:block bg-white border border-gray-100 rounded-lg overflow-auto max-h-[calc(100vh-15rem)]">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-200 shadow-sm">
                    <tr>
                        <th class="text-left font-semibold px-4 py-2 whitespace-nowrap">ໄອດີ <span class="text-gray-400">(BR No.)</span></th>
                        <th class="text-left font-semibold px-4 py-2 w-full">ຜູ້ຢືມ <span class="text-gray-400">(Borrower)</span></th>
                        <th class="text-left font-semibold px-4 py-2">ເຄື່ອງທີ່ຢືມ <span class="text-gray-400">(Items)</span></th>
                        <th class="text-left font-semibold px-4 py-2 whitespace-nowrap">ລະຫັດ <span class="text-gray-400">(Material ID)</span></th>
                        <th class="text-left font-semibold px-4 py-2 whitespace-nowrap">ວັນທີ <span class="text-gray-400">(Date)</span></th>
                        <th class="text-left font-semibold px-4 py-2 whitespace-nowrap">ສະຖານະ <span class="text-gray-400">(Status)</span></th>
                        <th class="text-left font-semibold px-4 py-2 whitespace-nowrap">ລາຍລະອຽດ <span class="text-gray-400">(Actions)</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($records as $r)
                        @php [$lbl, $cls] = $statusMeta($r->display_status); $first = $r->items->first(); $ph = $first?->photos->first() ?? $first?->inventoryItem?->primaryPhoto?->first(); $d = $r->days_left; @endphp
                        <tr wire:key="br-{{ $r->id }}" class="hover:bg-gray-50">
                            {{-- BR No. --}}
                            <td class="px-4 py-2 align-top whitespace-nowrap"><a href="{{ route('borrow.show', $r) }}" wire:navigate class="font-mono text-sm text-indigo-600 hover:underline">{{ $r->request_number }}</a></td>
                            {{-- borrower --}}
                            <td class="px-4 py-2 align-top w-full"><div class="font-semibold text-gray-800 truncate max-w-[220px]">{{ $r->borrower_name }}</div><div class="text-xs text-gray-400 truncate max-w-[220px]">{{ $r->unit?->name ?? $r->borrower_email }}</div></td>
                            {{-- items --}}
                            <td class="px-4 py-2 align-top">
                                <div class="flex gap-2">
                                    @if ($ph)<img src="{{ $ph->url }}" alt="" class="w-10 h-10 rounded-lg object-cover border border-gray-200 shrink-0" />
                                    @else<div class="w-10 h-10 rounded-lg bg-gray-100 border border-gray-200 shrink-0 flex items-center justify-center text-gray-300 text-xs">📦</div>@endif
                                    <div class="min-w-0">
                                        <div class="font-medium text-gray-800 truncate max-w-[150px]">{{ $first?->item_name ?? '—' }}@if ($r->items->count() > 1) <span class="text-gray-400 text-xs">+{{ $r->items->count() - 1 }}</span>@endif</div>
                                        <div class="text-xs text-gray-400">Qty: {{ $r->items->sum('qty') }}@if ($r->purpose) · {{ Str::limit($r->purpose, 30) }}@endif</div>
                                    </div>
                                </div>
                            </td>
                            {{-- material id chips --}}
                            <td class="px-4 py-2 align-top whitespace-nowrap">
                                @foreach ($r->items->take(2) as $bi)
                                    @if ($bi->inventoryItem)<span class="inline-block text-xs font-mono bg-gray-100 text-gray-600 rounded px-1.5 py-0.5 mb-0.5">{{ $bi->inventoryItem->slug }}</span>@endif
                                @endforeach
                            </td>
                            {{-- date --}}
                            <td class="px-4 py-2 align-top text-xs whitespace-nowrap">
                                <div class="text-gray-700 font-medium">{{ $r->borrow_date?->format('M d, Y') }}</div>
                                <div class="text-gray-400 mt-1">RETURN: {{ $r->planned_return_date?->format('M d, Y') }}</div>
                            </td>
                            {{-- status --}}
                            <td class="px-4 py-2 align-top whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 text-xs font-medium rounded-full px-2.5 py-1 {{ $cls }}">{{ $lbl }}</span>
                                @if ($d !== null)<div class="text-xs mt-1 {{ $d < 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $d < 0 ? 'Overdue '.abs($d).'d' : 'In '.$d.'d' }}</div>@endif
                            </td>
                            {{-- actions --}}
                            <td class="px-4 py-2 align-top whitespace-nowrap">
                                @if ($showDeleted)
                                    <button wire:click="restore({{ $r->id }})" wire:confirm="ກູ້ຄືນລາຍການນີ້?" class="text-xs text-emerald-700 border border-emerald-300 rounded-md px-3 py-1.5 hover:bg-emerald-50 inline-block">↩ ກູ້ຄືນ</button>
                                    @if ($r->deleted_reason)<div class="text-xs text-gray-400 mt-1 max-w-[12rem] truncate" title="{{ $r->deleted_reason }}">{{ $r->deleted_reason }}</div>@endif
                                @else
                                    <a href="{{ route('borrow.show', $r) }}" wire:navigate class="text-xs text-gray-700 border border-gray-300 rounded-md px-3 py-1.5 hover:bg-gray-50 inline-block">View Details</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">ຍັງບໍ່ມີຄຳຂໍຢືມ</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="md:hidden space-y-2">
            @forelse ($records as $r)
                @php [$lbl, $cls] = $statusMeta($r->display_status); $first = $r->items->first(); $ph = $first?->photos->first() ?? $first?->inventoryItem?->primaryPhoto?->first(); $tag = $showDeleted ? 'div' : 'a'; @endphp
                <{{ $tag }} @if (! $showDeleted) href="{{ route('borrow.show', $r) }}" wire:navigate @endif wire:key="mbr-{{ $r->id }}" class="block bg-white border border-gray-100 rounded-lg p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex gap-2 min-w-0">
                            @if ($ph)<img src="{{ $ph->url }}" alt="" class="w-10 h-10 rounded-lg object-cover border border-gray-200 shrink-0" />@endif
                            <div class="min-w-0"><div class="font-mono text-xs text-indigo-600">{{ $r->request_number }}</div><div class="font-semibold text-gray-800">{{ $r->borrower_name }}</div><div class="text-xs text-gray-500 truncate">{{ $first?->item_name }} · Qty {{ $r->items->sum('qty') }}</div></div>
                        </div>
                        <span class="text-xs font-medium rounded-full px-2 py-0.5 {{ $cls }} shrink-0">{{ $lbl }}</span>
                    </div>
                    <div class="text-xs text-gray-400 mt-1">{{ $r->borrow_date?->format('M d, Y') }} → {{ $r->planned_return_date?->format('M d, Y') }}</div>
                    @if ($showDeleted)
                        <div class="flex items-center justify-between gap-2 mt-2">
                            @if ($r->deleted_reason)<span class="text-xs text-gray-400 truncate">{{ $r->deleted_reason }}</span>@else<span></span>@endif
                            <button wire:click="restore({{ $r->id }})" wire:confirm="ກູ້ຄືນລາຍການນີ້?" class="text-xs text-emerald-700 border border-emerald-300 rounded-md px-3 py-1.5 hover:bg-emerald-50 shrink-0">↩ ກູ້ຄືນ</button>
                        </div>
                    @endif
                </{{ $tag }}>
            @empty
                <div class="text-center text-gray-400 py-6">ຍັງບໍ່ມີຄຳຂໍຢືມ</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $records->links() }}</div>
    </div>
</div>
