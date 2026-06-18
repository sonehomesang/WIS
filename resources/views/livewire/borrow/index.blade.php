@php
    $statusBadge = fn ($s) => match ($s) {
        'draft' => 'bg-gray-100 text-gray-700',
        'acknowledged' => 'bg-blue-100 text-blue-700',
        'approved' => 'bg-sky-100 text-sky-700',
        'active' => 'bg-emerald-100 text-emerald-700',
        'overdue' => 'bg-red-100 text-red-700',
        'returned' => 'bg-gray-200 text-gray-700',
        'cancelled' => 'bg-gray-100 text-gray-500',
        default => 'bg-gray-100 text-gray-600',
    };
@endphp

<div class="pb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- toolbar (sticky ໃຕ້ global header) --}}
        <div class="sticky top-16 z-20 bg-gray-100 flex flex-col gap-2 py-3 sm:py-0 sm:h-[52px] sm:flex-row sm:items-center sm:justify-between sm:gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ BR No./ຊື່ຜູ້ຢືມ…" class="rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                <select wire:model.live="statusFilter" class="rounded-md border-gray-300 text-sm">
                    <option value="">ທຸກ status</option>
                    <option value="draft">draft</option>
                    <option value="acknowledged">acknowledged</option>
                    <option value="approved">approved</option>
                    <option value="active">active</option>
                    <option value="overdue">overdue</option>
                    <option value="returned">returned</option>
                    <option value="cancelled">cancelled</option>
                </select>
                <span class="text-xs text-gray-400 whitespace-nowrap">{{ number_format($records->total()) }} ລາຍການ</span>
            </div>
            <div class="flex items-center gap-2">
                @can('borrow.create')<a href="{{ route('borrow.create') }}" wire:navigate class="text-sm text-white bg-sky-600 rounded-md px-3 py-2 min-h-[40px] inline-flex items-center hover:bg-sky-700 whitespace-nowrap">+ ສ້າງຄຳຂໍຢືມ</a>@endcan
            </div>
        </div>

        {{-- Desktop table --}}
        <div class="hidden md:block bg-white border border-gray-100 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 text-gray-700 border-b border-gray-200">
                    <tr>
                        <th class="text-left font-semibold px-4 py-2.5">BR No.</th>
                        <th class="text-left font-semibold px-4 py-2.5">ຜູ້ຢືມ</th>
                        <th class="text-left font-semibold px-4 py-2.5">ລາຍການ</th>
                        <th class="text-left font-semibold px-4 py-2.5">ວັນທີຢືມ</th>
                        <th class="text-left font-semibold px-4 py-2.5">ມື້</th>
                        <th class="text-left font-semibold px-4 py-2.5">Status</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $r)
                        <tr wire:key="br-{{ $r->id }}" class="border-t border-gray-200 hover:bg-gray-50">
                            <td class="px-4 py-2.5 font-mono text-gray-600">{{ $r->request_number }}</td>
                            <td class="px-4 py-2.5"><div class="text-gray-800">{{ $r->borrower_name }}</div><div class="text-xs text-gray-400">{{ $r->borrower_email }}</div></td>
                            <td class="px-4 py-2.5 text-gray-600 text-xs">{{ $r->items->take(3)->pluck('item_name')->implode(', ') }}@if ($r->items->count() > 3) +{{ $r->items->count() - 3 }}@endif</td>
                            <td class="px-4 py-2.5 text-gray-600">{{ $r->borrow_date?->toDateString() }}</td>
                            <td class="px-4 py-2.5 text-gray-600">{{ $r->period_days }}</td>
                            <td class="px-4 py-2.5"><span class="text-xs px-2 py-0.5 rounded {{ $statusBadge($r->display_status) }}">{{ $r->display_status }}</span></td>
                            <td class="px-4 py-2.5 text-right"><a href="{{ route('borrow.show', $r) }}" wire:navigate class="text-sky-600 hover:text-sky-800 text-xs">ເບິ່ງ →</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">ຍັງບໍ່ມີຄຳຂໍຢືມ</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="md:hidden space-y-2">
            @forelse ($records as $r)
                <a href="{{ route('borrow.show', $r) }}" wire:navigate wire:key="mbr-{{ $r->id }}" class="block bg-white border border-gray-100 rounded-lg p-3">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-sm text-gray-700">{{ $r->request_number }}</span>
                        <span class="text-xs px-2 py-0.5 rounded {{ $statusBadge($r->display_status) }}">{{ $r->display_status }}</span>
                    </div>
                    <div class="text-sm text-gray-700 mt-1">{{ $r->borrower_name }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ $r->items->take(2)->pluck('item_name')->implode(', ') }} · {{ $r->borrow_date?->toDateString() }} · {{ $r->period_days }} ມື້</div>
                </a>
            @empty
                <div class="text-center text-gray-400 py-6">ຍັງບໍ່ມີຄຳຂໍຢືມ</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $records->links() }}</div>
    </div>
</div>
