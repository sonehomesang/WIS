@php
    $svgEdit = 'm16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z';
    $svgPower = 'M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9';
@endphp

<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @include('settings._tabs')

        <div class="flex items-start justify-between gap-3 flex-wrap">
            <p class="text-sm text-gray-500 max-w-2xl">ສະຖານະພາບ ເຄື່ອງ (condition) — <b>ຖານ ຂໍ້ມູນ ດຽວ</b> ທີ່ ໃຊ້ ຮ່ວມ ໃນ Inventory · Equipment · Deposit ແລະ ການ ດຶງ ໄປ Disposal. ຕິກ <b>“ດຶງ ໄປ ຈຳໜ່າຍ”</b> = ເຄື່ອງ ໃນ ສະຖານະ ນີ້ ຖືກ ດຶງ ເຂົ້າ ໃບ ຈຳໜ່າຍ ໄດ້.</p>
            @can('settings.edit')<button wire:click="newItem" class="text-sm text-white bg-sky-600 rounded-md px-3 py-2 min-h-[40px] hover:bg-sky-700 whitespace-nowrap shrink-0">+ ເພີ່ມ ສະຖານະ</button>@endcan
        </div>

        <div x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)" x-show="show" style="display:none"
             class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-1">ບັນທຶກແລ້ວ ✓</div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 border-b border-gray-200">
                        <tr class="text-[11px] uppercase tracking-wide text-left">
                            <th class="px-4 py-2.5 font-semibold">ລຳດັບ</th>
                            <th class="px-4 py-2.5 font-semibold">ຕົວຢ່າງ · Preview</th>
                            <th class="px-4 py-2.5 font-semibold">ລະຫັດ (key)</th>
                            <th class="px-4 py-2.5 font-semibold text-center">ດຶງ ໄປ ຈຳໜ່າຍ</th>
                            <th class="px-4 py-2.5 font-semibold text-center">ໃຊ້ ງານ</th>
                            <th class="px-4 py-2.5 font-semibold text-right">ຈັດການ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($items as $m)
                            <tr wire:key="cs-{{ $m->id }}" class="hover:bg-slate-50/50 {{ $m->is_active ? '' : 'opacity-60' }}">
                                <td class="px-4 py-2.5 text-gray-400 tabular-nums">{{ $m->sort_order }}</td>
                                <td class="px-4 py-2.5"><span class="inline-flex text-xs font-medium rounded-full px-2.5 py-1 {{ $colors[$m->color] ?? $colors['gray'] }}">{{ $m->label }}</span></td>
                                <td class="px-4 py-2.5 font-mono text-xs text-gray-500">{{ $m->key }}</td>
                                <td class="px-4 py-2.5 text-center">@if ($m->is_disposable)<span class="text-rose-600" title="ດຶງ ໄປ ຈຳໜ່າຍ ໄດ້">🗑 ແມ່ນ</span>@else<span class="text-gray-300">—</span>@endif</td>
                                <td class="px-4 py-2.5 text-center"><span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $m->is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-gray-100 text-gray-500 ring-1 ring-gray-200' }}">{{ $m->is_active ? 'active' : 'inactive' }}</span></td>
                                <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                    @can('settings.edit')
                                        <button wire:click="toggle({{ $m->id }})" class="p-1 {{ $m->is_active ? 'text-green-600 hover:text-gray-400' : 'text-gray-300 hover:text-green-600' }}" title="{{ $m->is_active ? 'ປິດ ໃຊ້ງານ' : 'ເປີດ ໃຊ້ງານ' }}"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgPower }}" /></svg></button>
                                        <button wire:click="editItem({{ $m->id }})" class="text-gray-400 hover:text-gray-700 p-1" aria-label="Edit"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgEdit }}" /></svg></button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">ຍັງ ບໍ່ ມີ ສະຖານະພາບ — ກົດ “+ ເພີ່ມ ສະຖານະ”</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="cs-modal">
            <div class="bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-sky-200 to-sky-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-cyan-500 text-white grid place-items-center text-lg shadow-sm shrink-0">✎</span>
                    <h3 class="text-base font-semibold text-gray-800">{{ $editingId ? 'ແກ້ໄຂ' : 'ເພີ່ມ' }} ສະຖານະພາບ ເຄື່ອງ</h3>
                    <button wire:click="$set('showModal', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">
                @include('partials._form-errors')

                {{-- live preview --}}
                <div class="rounded-lg bg-gray-50 border border-gray-100 px-3 py-2.5 text-center">
                    <span class="inline-flex text-sm font-medium rounded-full px-3 py-1 {{ $colors[$color] ?? $colors['gray'] }}">{{ $label ?: 'ຕົວຢ່າງ' }}</span>
                </div>

                <div>
                    <label class="block text-sm text-gray-600 mb-1">ຊື່ / Label <span class="text-red-500">*</span></label>
                    <input type="text" wire:model.live="label" placeholder="ເສື່ອມ ສະພາບ · Deteriorated" class="w-full rounded-md border-gray-300 text-sm" />
                    <p class="text-[11px] text-gray-400 mt-1">ຮູບແບບ “ລາວ · English” — ຄໍລັ້ມ ຈະ ສະແດງ ສ່ວນ ລາວ.</p>
                    @error('label')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                @unless ($editingId)
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ລະຫັດ (key) <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="key" placeholder="deteriorated" class="w-full rounded-md border-gray-300 text-sm font-mono" />
                        <p class="text-[11px] text-gray-400 mt-1">a-z, 0-9, _ ເທົ່ານັ້ນ · <b>ແກ້ ບໍ່ ໄດ້ ພາຍຫຼັງ</b> (ຂໍ້ມູນ ເຄື່ອງ ອ້າງ ອີງ ຄ່າ ນີ້).</p>
                        @error('key')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                @else
                    <div><label class="block text-sm text-gray-600 mb-1">ລະຫັດ (key)</label><input type="text" value="{{ $key }}" disabled class="w-full rounded-md border-gray-200 bg-gray-50 text-sm font-mono text-gray-500" /></div>
                @endunless

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ສີ · Colour</label>
                        <select wire:model.live="color" class="w-full rounded-md border-gray-300 text-sm">
                            @foreach ($colors as $cName => $cls)<option value="{{ $cName }}">{{ $cName }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ລຳດັບ · Sort</label>
                        <input type="number" min="0" wire:model="sort_order" class="w-full rounded-md border-gray-300 text-sm" />
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" wire:model="is_disposable" class="rounded border-gray-300 text-rose-600 focus:ring-rose-500" /> 🗑 ດຶງ ໄປ ຈຳໜ່າຍ ໄດ້ (disposable)</label>
                <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-sky-600 focus:ring-sky-500" /> ໃຊ້ ງານ (active)</label>
                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                    <button wire:click="$set('showModal', false)" class="text-sm text-gray-700 bg-white border border-gray-300 rounded-lg px-4 py-2 min-h-[40px] hover:bg-gray-50">ຍົກເລີກ</button>
                    <button wire:click="save" class="text-sm text-white bg-sky-600 rounded-lg px-4 py-2 min-h-[40px] hover:bg-sky-700 shadow-sm">ບັນທຶກ</button>
                </div>
            </div>
        </div>
    @endif
</div>
