@php
    $badge = fn ($s) => match ($s) {
        'active' => 'bg-green-50 text-green-700',
        'repair' => 'bg-amber-50 text-amber-700',
        'retired' => 'bg-gray-100 text-gray-500',
        default => 'bg-gray-100 text-gray-600',
    };
    $statusLabel = ['active' => 'ໃຊ້ງານ', 'repair' => 'ຊ່ອມແປງ', 'retired' => 'ຢຸດໃຊ້'];
@endphp

<div class="pb-6" x-data="{ tab: 'register' }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)" x-show="show" style="display:none"
             class="fixed bottom-4 right-4 z-50 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 shadow-lg">ບັນທຶກແລ້ວ ✓</div>

        {{-- Tabs --}}
        <div class="flex gap-1 border-b border-gray-200 mt-3">
            <button @click="tab='register'" :class="tab==='register' ? 'border-sky-600 text-sky-700 font-medium' : 'border-transparent text-gray-500'" class="px-4 py-2 text-sm border-b-2 -mb-px">ທະບຽນ ເຄື່ອງ</button>
            <button @click="tab='inspection'" :class="tab==='inspection' ? 'border-sky-600 text-sky-700 font-medium' : 'border-transparent text-gray-500'" class="px-4 py-2 text-sm border-b-2 -mb-px">ການ ກວດກາ</button>
            <button @click="tab='usage'" :class="tab==='usage' ? 'border-sky-600 text-sky-700 font-medium' : 'border-transparent text-gray-500'" class="px-4 py-2 text-sm border-b-2 -mb-px">ການ ນຳໃຊ້</button>
            <button @click="tab='maintenance'" :class="tab==='maintenance' ? 'border-sky-600 text-sky-700 font-medium' : 'border-transparent text-gray-500'" class="px-4 py-2 text-sm border-b-2 -mb-px">ບຳລຸງຮັກສາ</button>
        </div>

        {{-- ═══ TAB 1: Register ═══ --}}
        <div x-show="tab==='register'">
            {{-- toolbar --}}
            <div class="flex flex-wrap items-center gap-2 py-3">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ ຊື່/ລະຫັດ/serial…"
                       class="rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm w-56" />
                <select wire:model.live="categoryFilter" class="rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">ທຸກ ປະເພດ</option>
                    @foreach ($categories as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                </select>
                <select wire:model.live="statusFilter" class="rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">ທຸກ ສະຖານະ</option>
                    <option value="active">ໃຊ້ງານ</option>
                    <option value="repair">ຊ່ອມແປງ</option>
                    <option value="retired">ຢຸດໃຊ້</option>
                </select>
                <div class="flex-1"></div>
                <span class="text-xs text-gray-400">ທັງໝົດ {{ $items->total() }} ລາຍການ</span>
                @can('equipment.create')
                    <button wire:click="newItem" class="text-sm text-white bg-sky-600 rounded-md px-3 py-2 min-h-[40px] hover:bg-sky-700 whitespace-nowrap">+ ເພີ່ມ ເຄື່ອງ</button>
                @endcan
            </div>

            {{-- Desktop table --}}
            <div class="hidden md:block bg-white border border-gray-100 rounded-lg overflow-auto max-h-[calc(100vh-16rem)]">
                <table class="w-full text-sm table-fixed">
                    <thead class="sticky top-0 z-10 bg-gray-50 text-gray-600 text-xs border-b border-gray-200 shadow-sm">
                        <tr>
                            <th class="text-left font-semibold px-3 py-2 w-24">ລະຫັດເຄື່ອງ</th>
                            <th class="text-left font-semibold px-3 py-2 w-28">ທະບຽນຊັບສິນ</th>
                            <th class="text-left font-semibold px-3 py-2">ຊື່ ເຄື່ອງ</th>
                            <th class="text-left font-semibold px-3 py-2 w-28">ປະເພດ</th>
                            <th class="text-left font-semibold px-3 py-2 w-36">ຍີ່ຫໍ້ / ຮຸ່ນ</th>
                            <th class="text-left font-semibold px-3 py-2 w-28">Serial</th>
                            <th class="text-left font-semibold px-3 py-2 w-28">ສະຖານທີ່</th>
                            <th class="text-left font-semibold px-3 py-2 w-28">ຜູ້ຮັບຜິດຊອບ</th>
                            <th class="text-left font-semibold px-3 py-2 w-24">ສະຖານະ</th>
                            <th class="px-3 py-2 w-20"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($items as $e)
                            <tr wire:key="eq-{{ $e->id }}">
                                <td class="px-3 py-2 text-gray-500 whitespace-nowrap">{{ $e->asset_code }}</td>
                                <td class="px-3 py-2 text-gray-600 truncate">{{ $e->fixed_asset_no ?? '—' }}</td>
                                <td class="px-3 py-2 font-medium text-gray-800 truncate">{{ $e->name }}</td>
                                <td class="px-3 py-2 truncate">{{ $e->category ?? '—' }}</td>
                                <td class="px-3 py-2 truncate">{{ $e->brand_model ?? '—' }}</td>
                                <td class="px-3 py-2 text-gray-500 truncate">{{ $e->serial_no ?? '—' }}</td>
                                <td class="px-3 py-2 truncate">{{ $e->location ?? '—' }}</td>
                                <td class="px-3 py-2 truncate">{{ $e->responsible_name ?? '—' }}</td>
                                <td class="px-3 py-2 whitespace-nowrap"><span class="text-xs rounded px-2 py-0.5 {{ $badge($e->status) }}">{{ $statusLabel[$e->status] ?? $e->status }}</span></td>
                                <td class="px-3 py-2 text-right whitespace-nowrap text-gray-500">
                                    @can('equipment.edit')
                                        <button wire:click="editItem({{ $e->id }})" class="hover:text-gray-800 p-1" title="ແກ້ໄຂ">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /></svg>
                                        </button>
                                    @endcan
                                    @can('equipment.delete')
                                        <button wire:click="delete({{ $e->id }})" wire:confirm="ລຶບ ເຄື່ອງ ນີ້?" class="hover:text-red-600 p-1" title="ລຶບ">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="px-3 py-6 text-center text-gray-400">ຍັງບໍ່ມີ ເຄື່ອງ — ກົດ "+ ເພີ່ມ ເຄື່ອງ"</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile cards --}}
            <div class="md:hidden space-y-2">
                @forelse ($items as $e)
                    <div wire:key="m-{{ $e->id }}" class="bg-white border border-gray-100 rounded-lg p-3">
                        <div class="flex items-center justify-between">
                            <div class="font-medium text-gray-800">{{ $e->name }}</div>
                            <span class="text-xs rounded px-2 py-0.5 {{ $badge($e->status) }}">{{ $statusLabel[$e->status] ?? $e->status }}</span>
                        </div>
                        <div class="text-xs text-gray-400">{{ $e->asset_code }}@if ($e->fixed_asset_no) · FA: {{ $e->fixed_asset_no }}@endif · {{ $e->category ?? '—' }} · {{ $e->brand_model ?? '—' }}</div>
                        <div class="text-xs text-gray-600 mt-1">{{ $e->location ?? '—' }} · {{ $e->responsible_name ?? '—' }}</div>
                        <div class="flex gap-2 mt-2">
                            @can('equipment.edit')<button wire:click="editItem({{ $e->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px]">ແກ້ໄຂ</button>@endcan
                            @can('equipment.delete')<button wire:click="delete({{ $e->id }})" wire:confirm="ລຶບ ເຄື່ອງ ນີ້?" class="text-xs border rounded px-2 py-1 min-h-[36px] text-red-600">ລຶບ</button>@endcan
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-400 py-6">ຍັງບໍ່ມີ ເຄື່ອງ</div>
                @endforelse
            </div>

            <div class="mt-4">{{ $items->links() }}</div>
        </div>

        {{-- ═══ TAB 2-4: placeholders (ຈະ ອອກແບບ ລາຍລະອຽດ ຕໍ່) ═══ --}}
        <div x-show="tab==='inspection'" x-cloak class="mt-4 bg-white border border-gray-100 rounded-lg p-5">
            <h3 class="font-medium text-gray-800 mb-1">ການ ກວດກາ (Inspection)</h3>
            <p class="text-sm text-gray-500">ບັນທຶກ ການ ກວດ ສະພາບ ເຄື່ອງ ແຕ່ ລະ ຄັ້ງ + ກຳນົດ ຄັ້ງ ຕໍ່ໄປ.</p>
            <div class="mt-3 text-xs text-sky-700 bg-sky-50 border border-sky-100 rounded-md px-3 py-2">📋 ໂຄງ ໄວ້ ກ່ອນ — ຈະ ອອກແບບ ລາຍລະອຽດ ແທັບ ນີ້ ຕໍ່ໄປ.</div>
        </div>
        <div x-show="tab==='usage'" x-cloak class="mt-4 bg-white border border-gray-100 rounded-lg p-5">
            <h3 class="font-medium text-gray-800 mb-1">ການ ນຳໃຊ້ (Usage)</h3>
            <p class="text-sm text-gray-500">ບັນທຶກ ການ ນຳ ເຄື່ອງ ໄປ ໃຊ້ — ໃຜ · ເມື່ອໃດ · ຈຸດປະສົງ · ຊົ່ວໂມງ ໃຊ້.</p>
            <div class="mt-3 text-xs text-sky-700 bg-sky-50 border border-sky-100 rounded-md px-3 py-2">📋 ໂຄງ ໄວ້ ກ່ອນ — ຈະ ອອກແບບ ລາຍລະອຽດ ແທັບ ນີ້ ຕໍ່ໄປ.</div>
        </div>
        <div x-show="tab==='maintenance'" x-cloak class="mt-4 bg-white border border-gray-100 rounded-lg p-5">
            <h3 class="font-medium text-gray-800 mb-1">ບຳລຸງຮັກສາ (Maintenance)</h3>
            <p class="text-sm text-gray-500">ບັນທຶກ ການ ບຳລຸງ/ຊ່ອມແປງ + ຄ່າ ໃຊ້ຈ່າຍ + ກຳນົດ service ຄັ້ງ ໜ້າ.</p>
            <div class="mt-3 text-xs text-sky-700 bg-sky-50 border border-sky-100 rounded-md px-3 py-2">📋 ໂຄງ ໄວ້ ກ່ອນ — ຈະ ອອກແບບ ລາຍລະອຽດ ແທັບ ນີ້ ຕໍ່ໄປ.</div>
        </div>
    </div>

    {{-- Create / Edit modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="eq-modal">
            <div class="bg-white w-full md:max-w-lg rounded-t-lg md:rounded-lg p-5 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-800">{{ $editingId ? 'ແກ້ໄຂ ເຄື່ອງ' : 'ເພີ່ມ ເຄື່ອງ ໃໝ່' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-700 p-1" aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ຊື່ ເຄື່ອງ <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                        @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ທະບຽນຊັບສິນ (Fixed Asset)</label>
                        <input type="text" wire:model="fixed_asset_no" placeholder="ເລກ ຈາກ ບັນຊີ ຊັບສິນ" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ປະເພດ</label>
                        <input type="text" wire:model="category" placeholder="Generator · Vehicle · Power tool…" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ຍີ່ຫໍ້ / ຮຸ່ນ</label>
                        <input type="text" wire:model="brand_model" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Serial</label>
                        <input type="text" wire:model="serial_no" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ສະຖານທີ່</label>
                        <input type="text" wire:model="location" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ຜູ້ຮັບຜິດຊອບ</label>
                        <input type="text" wire:model="responsible_name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ສະຖານະ</label>
                        <select wire:model="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm">
                            <option value="active">ໃຊ້ງານ</option>
                            <option value="repair">ຊ່ອມແປງ</option>
                            <option value="retired">ຢຸດໃຊ້</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ວັນທີ ຊື້/ຮັບ</label>
                        <input type="date" wire:model="purchase_date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ໝາຍເຫດ</label>
                        <textarea wire:model="notes" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button wire:click="$set('showModal', false)" class="text-sm text-gray-700 border border-gray-300 rounded-md px-4 py-2 min-h-[40px] hover:bg-gray-50">ຍົກເລີກ</button>
                    <button wire:click="save" class="text-sm text-white bg-sky-600 rounded-md px-4 py-2 min-h-[40px] hover:bg-sky-700">ບັນທຶກ</button>
                </div>
            </div>
        </div>
    @endif
</div>
