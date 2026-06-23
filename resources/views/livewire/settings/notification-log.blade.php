@php
    $dot = ['info' => 'bg-sky-400', 'success' => 'bg-emerald-400', 'warning' => 'bg-amber-400', 'error' => 'bg-red-400'];
@endphp
<div class="pb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @include('settings._tabs')

        <div class="flex flex-wrap items-center gap-2">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ ຫົວຂໍ້/ຂໍ້ຄວາມ/ຜູ້ຮັບ…" class="w-64 rounded-md border-gray-300 text-sm" />
            <select wire:model.live="typeFilter" class="rounded-md border-gray-300 text-sm">
                <option value="">ທຸກ ປະເພດ</option>
                <option value="info">info</option><option value="success">success</option><option value="warning">warning</option><option value="error">error</option>
            </select>
            <select wire:model.live="readFilter" class="rounded-md border-gray-300 text-sm">
                <option value="">ທັງໝົด</option><option value="unread">ຍັງບໍ່ອ່ານ</option><option value="read">ອ່ານແລ້ວ</option>
            </select>
            <button wire:click="export" class="ml-auto text-sm text-gray-700 border border-gray-200 rounded-md px-3 py-1.5 hover:bg-gray-50">⬇ Export CSV</button>
        </div>

        <div class="bg-white border border-gray-100 rounded-lg overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs">
                    <tr>
                        <th class="text-left px-3 py-2 font-medium">ຜູ້ຮັບ</th>
                        <th class="text-left px-3 py-2 font-medium">ປະເພດ</th>
                        <th class="text-left px-3 py-2 font-medium">ຫົວຂໍ້ / ຂໍ້ຄວາມ</th>
                        <th class="text-left px-3 py-2 font-medium">ອ່ານ</th>
                        <th class="text-left px-3 py-2 font-medium">ເວລາ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($rows as $r)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 text-gray-700 whitespace-nowrap">{{ $r->recipient ?? '—' }}</td>
                            <td class="px-3 py-2"><span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full {{ $dot[$r->type] ?? 'bg-gray-300' }}"></span>{{ $r->type }}</span></td>
                            <td class="px-3 py-2"><div class="text-gray-800">{{ $r->title }}</div>@if ($r->message)<div class="text-xs text-gray-400">{{ $r->message }}</div>@endif</td>
                            <td class="px-3 py-2 text-xs {{ $r->read_at ? 'text-gray-400' : 'text-amber-600 font-medium' }}">{{ $r->read_at ? '✓' : 'ใหม่' }}</td>
                            <td class="px-3 py-2 text-xs text-gray-400 whitespace-nowrap">{{ $r->created_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-8 text-center text-gray-400">ບໍ່ມີ ການແຈ້ງເຕືອນ</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $rows->links() }}</div>
    </div>
</div>
