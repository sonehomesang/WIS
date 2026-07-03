@php
    $badge = fn ($s) => match ($s) {
        'active' => 'bg-green-50 text-green-700',
        'repair' => 'bg-amber-50 text-amber-700',
        'retired' => 'bg-gray-100 text-gray-500',
        default => 'bg-gray-100 text-gray-600',
    };
    $statusLabel = ['active' => 'ໃຊ້ງານ', 'repair' => 'ຊ່ອມແປງ', 'retired' => 'ຢຸດໃຊ້'];
@endphp

<div class="pb-6" x-data="{ tab: 'register', bigImg: null }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)" x-show="show" style="display:none"
             class="fixed bottom-4 right-4 z-50 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 shadow-lg">ບັນທຶກແລ້ວ ✓</div>

        {{-- Tabs --}}
        <div class="flex gap-1 border-b border-gray-200 mt-3">
            <button @click="tab='register'" :class="tab==='register' ? 'border-sky-600 text-sky-700 font-medium' : 'border-transparent text-gray-500'" class="px-4 py-2 text-sm border-b-2 -mb-px">ທະບຽນ ເຄື່ອງ</button>
            <button @click="tab='inspection'" :class="tab==='inspection' ? 'border-sky-600 text-sky-700 font-medium' : 'border-transparent text-gray-500'" class="px-4 py-2 text-sm border-b-2 -mb-px">ການ ກວດກາ</button>
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
            <div class="hidden md:block bg-white border border-gray-100 rounded-lg overflow-x-hidden overflow-y-auto max-h-[calc(100vh-16rem)]">
                <table class="w-full text-sm table-fixed">
                    <thead class="sticky top-0 z-10 bg-gray-50 text-gray-600 text-xs border-b border-gray-200 shadow-sm">
                        <tr>
                            <th class="text-left font-semibold px-3 py-2 w-24">ລະຫັດເຄື່ອງ</th>
                            <th class="text-left font-semibold px-3 py-2 w-24">ທະບຽນຊັບສິນ</th>
                            <th class="text-left font-semibold px-3 py-2">ຊື່ ເຄື່ອງ / ລາຍລະອຽດ</th>
                            <th class="text-left font-semibold px-3 py-2 w-28">Owner (ພະແນກ)</th>
                            <th class="text-left font-semibold px-3 py-2 w-24">Loc-Bin</th>
                            <th class="text-left font-semibold px-3 py-2 w-24">ຮູບ</th>
                            <th class="text-left font-semibold px-3 py-2 w-16">ຈຳນວນ</th>
                            <th class="text-left font-semibold px-3 py-2 w-32">ສະຖານະ</th>
                            <th class="px-3 py-2 w-20"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($items as $e)
                            <tr wire:key="eq-{{ $e->id }}">
                                <td class="px-3 py-2 text-gray-500 truncate">{{ $e->asset_code }}</td>
                                <td class="px-3 py-2 text-gray-600 truncate">{{ $e->fixed_asset_no ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    <div class="font-medium text-gray-800 truncate">{{ $e->name }}</div>
                                    @php $meta = array_filter([$e->category, $e->brand_model, $e->serial_no, $e->location, $e->responsibleLabel()]); @endphp
                                    <div class="text-xs text-gray-400 truncate">{{ $meta ? implode(' · ', $meta) : '—' }}</div>
                                </td>
                                <td class="px-3 py-2 text-gray-600 truncate">{{ $e->department?->name ?? '—' }}</td>
                                <td class="px-3 py-2 text-gray-600 truncate">{{ $e->loc_bin ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    <div class="flex gap-0.5">
                                        @forelse ($e->photos->take(3) as $p)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($p->path) }}" @click="bigImg='{{ \Illuminate\Support\Facades\Storage::url($p->path) }}'"
                                                 class="w-8 h-8 rounded object-cover border border-gray-200 cursor-pointer hover:ring-2 hover:ring-sky-400" alt="ຮູບ" />
                                        @empty
                                            <span class="text-gray-300 text-xs">—</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">{{ $e->quantity }}<span class="text-xs text-gray-400"> {{ $e->unit?->name }}</span></td>
                                <td class="px-3 py-2">
                                    @php $bd = $e->statusBreakdown(); @endphp
                                    <div class="flex flex-wrap gap-0.5">
                                        @foreach (['active', 'repair', 'retired'] as $s)
                                            @if ($bd[$s] > 0)<span class="text-xs rounded px-1.5 py-0.5 {{ $badge($s) }}">{{ $bd[$s] }} {{ $statusLabel[$s] }}</span>@endif
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-3 py-2 pr-5 text-right whitespace-nowrap text-gray-500">
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
                            <tr><td colspan="9" class="px-3 py-6 text-center text-gray-400">ຍັງບໍ່ມີ ເຄື່ອງ — ກົດ "+ ເພີ່ມ ເຄື່ອງ"</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile cards --}}
            <div class="md:hidden space-y-2">
                @forelse ($items as $e)
                    <div wire:key="m-{{ $e->id }}" class="bg-white border border-gray-100 rounded-lg p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="font-medium text-gray-800">{{ $e->name }}</div>
                            <div class="text-right shrink-0">
                                @php $bd = $e->statusBreakdown(); @endphp
                                @foreach (['active', 'repair', 'retired'] as $s)
                                    @if ($bd[$s] > 0)<span class="text-[11px] rounded px-1.5 py-0.5 {{ $badge($s) }} ml-0.5 whitespace-nowrap">{{ $bd[$s] }} {{ $statusLabel[$s] }}</span>@endif
                                @endforeach
                            </div>
                        </div>
                        <div class="text-xs text-gray-400">{{ $e->asset_code }}@if ($e->fixed_asset_no) · FA: {{ $e->fixed_asset_no }}@endif · {{ $e->category ?? '—' }} · {{ $e->quantity }} {{ $e->unit?->name ?? '' }}</div>
                        <div class="text-xs text-gray-600 mt-1">Owner: {{ $e->department?->name ?? '—' }} · Loc-Bin: {{ $e->loc_bin ?? '—' }}</div>
                        <div class="text-xs text-gray-600">{{ $e->location ?? '—' }} · {{ $e->responsibleLabel() ?? '—' }}</div>
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

        {{-- ═══ TAB 2: ການ ກວດກາ ═══ --}}
        <div x-show="tab==='inspection'" x-cloak class="mt-4">
            <div class="flex items-center justify-between mb-2 gap-2">
                <span class="text-xs text-gray-400">ບັນທຶກ ການ ກວດ ສະພາບ ເຄື່ອງ · ຮູບ ຝັງ ວັນທີ+ເວລາ · ກຳນົດ ກວດ ຄັ້ງ ໜ້າ</span>
                <div class="flex items-center gap-2 shrink-0">
                    @can('equipment.edit')
                        @unless ($deptScoped)
                            <a href="{{ route('equipment.templates') }}" wire:navigate class="text-sm text-sky-700 border border-sky-200 rounded-md px-3 py-2 min-h-[40px] hover:bg-sky-50 whitespace-nowrap">ⓘ ແມ່ແບບ ກວດກາ</a>
                        @endunless
                        <button wire:click="newInspection" class="text-sm text-white bg-sky-600 rounded-md px-3 py-2 min-h-[40px] hover:bg-sky-700 whitespace-nowrap">+ ບັນທຶກ ການ ກວດກາ</button>
                    @endcan
                </div>
            </div>
            <div class="bg-white border border-gray-100 rounded-lg overflow-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-xs border-b border-gray-200">
                        <tr>
                            <th class="text-left px-3 py-2 font-semibold">ວັນທີ/ເວລາ</th>
                            <th class="text-left px-3 py-2 font-semibold">ເຄື່ອງ</th>
                            <th class="text-left px-3 py-2 font-semibold">ຜູ້ກວດ</th>
                            <th class="text-left px-3 py-2 font-semibold">ຜົນ</th>
                            <th class="text-left px-3 py-2 font-semibold">ກວດ ຄັ້ງ ໜ້າ</th>
                            <th class="text-left px-3 py-2 font-semibold">ຮູບ</th>
                            <th class="px-3 py-2 font-semibold w-20 text-right pr-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @php $rb = ['pass' => 'bg-green-50 text-green-700', 'fail' => 'bg-red-50 text-red-700', 'follow_up' => 'bg-amber-50 text-amber-700']; $rl = ['pass' => 'ຜ່ານ', 'fail' => 'ບໍ່ຜ່ານ', 'follow_up' => 'ຕ້ອງຕິດຕາມ']; @endphp
                        @forelse ($inspections as $ins)
                            <tr wire:key="ins-{{ $ins->id }}">
                                <td class="px-3 py-2 whitespace-nowrap">{{ $ins->inspected_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-3 py-2">{{ $ins->equipment?->asset_code }} · {{ $ins->equipment?->name }}</td>
                                <td class="px-3 py-2">{{ $ins->inspector_name ?? '—' }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <span class="text-xs rounded px-2 py-0.5 {{ $rb[$ins->result] ?? 'bg-gray-100 text-gray-600' }}">{{ $rl[$ins->result] ?? $ins->result }}</span>
                                    @if (! is_null($ins->score))
                                        <span class="text-xs text-gray-400 ml-1">{{ $ins->score }}%</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    @if ($ins->next_due_date)
                                        <span class="{{ $ins->next_due_date->isPast() ? 'text-red-600 font-medium' : ($ins->next_due_date->lte(now()->addDays(14)) ? 'text-amber-600' : 'text-gray-600') }}">{{ $ins->next_due_date->format('d/m/Y') }}</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    @if ($ins->photo_path)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($ins->photo_path) }}" @click="bigImg='{{ \Illuminate\Support\Facades\Storage::url($ins->photo_path) }}'" class="w-8 h-8 rounded object-cover border border-gray-200 cursor-pointer hover:ring-2 hover:ring-sky-400" alt="ຮູບ ກວດກາ" />
                                    @else
                                        <span class="text-gray-300 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 pr-4 text-right whitespace-nowrap text-gray-500">
                                    <button wire:click="viewInspection({{ $ins->id }})" class="hover:text-sky-700 p-1" title="ເບິ່ງ ລາຍລະອຽດ">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                    </button>
                                    @can('equipment.edit')
                                        <button wire:click="editInspection({{ $ins->id }})" class="hover:text-gray-800 p-1" title="ແກ້ໄຂ">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /></svg>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-3 py-6 text-center text-gray-400">ຍັງບໍ່ມີ ການ ກວດກາ — ກົດ "+ ບັນທຶກ ການ ກວດກາ"</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
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
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ລະຫັດເຄື່ອງ <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="asset_code" placeholder="ເຊັ່ນ EQ-0001" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                        @error('asset_code')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ຊື່ ເຄື່ອງ <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                        @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ທະບຽນຊັບສິນ (Fixed Asset)</label>
                        <input type="text" wire:model="fixed_asset_no" placeholder="ເລກ ຈາກ ບັນຊີ ຊັບສິນ" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-sm text-gray-600">ປະເພດ</label>
                            @if ($canManageCategories)
                                <a href="{{ route('equipment.categories') }}" wire:navigate class="text-xs text-sky-600 hover:underline">＋ ຈັດການ ປະເພດ</a>
                            @endif
                        </div>
                        <select wire:model="category" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm">
                            <option value="">—</option>
                            @foreach ($categoryOptions as $cat)<option value="{{ $cat }}">{{ $cat }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Owner (ພະແນກ ເຈົ້າຂອງ)</label>
                        <select wire:model="department_id" @disabled($deptScoped) class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm @if ($deptScoped) bg-gray-100 @endif">
                            <option value="">—</option>
                            @foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                        </select>
                        @if ($deptScoped)<p class="text-xs text-gray-400 mt-1">ລ໋ອກ ໄວ້ ຕາມ ພະແນກ ຂອງ ທ່ານ</p>@endif
                        @error('department_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
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
                        <label class="block text-sm text-gray-600 mb-1">Loc-Bin <span class="text-xs text-gray-400">(ບ່ອນ ວາງ/ຊັ້ນ)</span></label>
                        <input type="text" wire:model="loc_bin" placeholder="A-01-03 · WH-Rack-B2…" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                        @error('loc_bin')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ຜູ້ຮັບຜິດຊອບ <span class="text-xs text-gray-400">(ຈາກ ຜູ້ໃຊ້)</span></label>
                        <select wire:model="responsible_user_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm">
                            <option value="">—</option>
                            @foreach ($responsibleUsers as $ru)<option value="{{ $ru->id }}">{{ $ru->display_name }}</option>@endforeach
                        </select>
                        @if ($responsible_name && ! $responsible_user_id)
                            <p class="text-xs text-gray-400 mt-1">ຄ່າ ເກົ່າ: {{ $responsible_name }} — ເລືອກ ຜູ້ໃຊ້ ເພື່ອ ລິ້ງ</p>
                        @endif
                        @error('responsible_user_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ຈຳນວນ ລວມ <span class="text-red-500">*</span></label>
                        <input type="number" min="1" wire:model="quantity" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                        @error('quantity')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ຫົວໜ່ວຍ</label>
                        <select wire:model="unit_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm">
                            <option value="">—</option>
                            @foreach ($units as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ຈຳນວນ ຊ່ອມແປງ</label>
                        <input type="number" min="0" wire:model="qtyRepair" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ຈຳນວນ ຢຸດໃຊ້</label>
                        <input type="number" min="0" wire:model="qtyRetired" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                        @error('qtyRepair')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2 text-xs text-gray-500 bg-gray-50 border border-gray-100 rounded-md px-3 py-2">
                        ໃຊ້ງານ = ຈຳນວນ ລວມ − ຊ່ອມແປງ − ຢຸດໃຊ້. ເຄື່ອງ ໃໝ່ ປ່ອຍ ຊ່ອມ/ຢຸດ = 0 → <b>ໃຊ້ງານ ໝົດ</b>. ປັບ ຕໍ່ ຕອນ ກວດກາ.
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ວັນທີ ຊື້/ຮັບ</label>
                        <input type="date" wire:model="purchase_date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ໝາຍເຫດ</label>
                        <textarea wire:model="notes" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm"></textarea>
                    </div>

                    {{-- ຮູບ (ສູງສຸດ 3 · ກ້ອງ ຫຼື ແກເລີຣີ) --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ຮູບ <span class="text-xs text-gray-400">(ສູງສຸດ 3 · 📷 ຖ່າຍ ຫຼື ເລືອກ ຈາກ ແກເລີຣີ · ກົດ ຮູບ ເພື່ອ ເບິ່ງ ໃຫຍ່)</span></label>
                        @if (count($existingPhotos))
                            <div class="flex flex-wrap gap-2 mb-2">
                                @foreach ($existingPhotos as $p)
                                    <div class="relative">
                                        <img src="{{ $p['url'] }}" @click="bigImg='{{ $p['url'] }}'" class="w-16 h-16 rounded object-cover border border-gray-200 cursor-pointer" alt="ຮູບ" />
                                        <button type="button" wire:click="removePhoto({{ $p['id'] }})" wire:confirm="ລຶບ ຮູບ ນີ້?" class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-red-600 text-white text-xs leading-none flex items-center justify-center" aria-label="ລຶບ ຮູບ">×</button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if (count($newPhotos))
                            <div class="flex flex-wrap gap-2 mb-2">
                                @foreach ($newPhotos as $ph)
                                    <img src="{{ $ph->temporaryUrl() }}" class="w-16 h-16 rounded object-cover border border-sky-200" alt="ຮູບ ໃໝ່" />
                                @endforeach
                            </div>
                        @endif
                        <input type="file" wire:model="newPhotos" multiple accept="image/*"
                               class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-sky-50 file:text-sky-700 file:min-h-[40px]" />
                        <div wire:loading wire:target="newPhotos" class="text-xs text-gray-400 mt-1">ກຳລັງ ອັບໂຫຼດ…</div>
                        @error('newPhotos.*')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        @error('newPhotos')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button wire:click="$set('showModal', false)" class="text-sm text-gray-700 border border-gray-300 rounded-md px-4 py-2 min-h-[40px] hover:bg-gray-50">ຍົກເລີກ</button>
                    <button wire:click="save" class="text-sm text-white bg-sky-600 rounded-md px-4 py-2 min-h-[40px] hover:bg-sky-700">ບັນທຶກ</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Inspection modal (ແທັບ 2) --}}
    @if ($showInspectionModal)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="ins-modal">
            <div class="bg-white w-full md:max-w-lg rounded-t-lg md:rounded-lg p-5 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-800">{{ $editingInspectionId ? 'ແກ້ໄຂ ການ ກວດກາ' : 'ບັນທຶກ ການ ກວດກາ' }}</h3>
                    <button wire:click="$set('showInspectionModal', false)" class="text-gray-400 hover:text-gray-700 p-1" aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- ເລືອກ ເຄື່ອງ --}}
                @if (! $insEquipmentId)
                    <div class="relative">
                        <label class="block text-sm text-gray-600 mb-1">ເລືອກ ເຄື່ອງ <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.live.debounce.300ms="insSearch" placeholder="ຄົ້ນຫາ ຊື່/ລະຫັດ…" class="w-full rounded-md border-gray-300 text-sm" />
                        @if ($insResults->isNotEmpty())
                            <div class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto">
                                @foreach ($insResults as $eq)
                                    <button type="button" wire:click="pickInspectionEquipment({{ $eq->id }})" class="block w-full text-left px-3 py-2 text-sm hover:bg-gray-50">{{ $eq->name }} <span class="font-mono text-xs text-gray-400">{{ $eq->asset_code }}</span></button>
                                @endforeach
                            </div>
                        @endif
                        @error('insEquipmentId')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                @else
                    <div class="flex items-center justify-between bg-gray-50 border border-gray-100 rounded px-3 py-2 text-sm">
                        <span class="font-medium text-gray-700">{{ $insEquipmentLabel }} <span class="text-xs text-gray-400">(ຈຳນວນ {{ $insEquipmentQty }})</span></span>
                        <button wire:click="$set('insEquipmentId', null)" class="text-xs text-sky-600 hover:underline">ປ່ຽນ</button>
                    </div>
                @endif

                {{-- ແມ່ແບບ + ເຊັກລິສ --}}
                @if ($insEquipmentId)
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ແມ່ແບບ ກວດກາ</label>
                        <select wire:model.live="insTemplateId" class="w-full rounded-md border-gray-300 text-sm">
                            <option value="">— ບໍ່ ໃຊ້ ແມ່ແບບ —</option>
                            @foreach ($insTemplateOptions as $t)<option value="{{ $t->id }}">{{ $t->matches ? '★ ' : '' }}{{ $t->name }}@if ($t->category) ({{ $t->category }})@endif</option>@endforeach
                        </select>
                        @if ($insTemplateOptions->isEmpty())
                            <p class="text-xs text-gray-400 mt-1">ຍັງ ບໍ່ ມີ ແມ່ແບບ ໃດ@unless ($deptScoped) — <a href="{{ route('equipment.templates') }}" wire:navigate class="text-sky-600 hover:underline">ສ້າງ ແມ່ແບບ</a>@endunless</p>
                        @else
                            <p class="text-xs text-gray-400 mt-1">★ = ກົງ ປະເພດ ເຄື່ອງ · ເລືອກ ອັນ ໃດ ກໍ ໄດ້.</p>
                        @endif
                    </div>
                    {{-- ປະເພດ ນ້ຳມັນ (ສະແດງ ເມື່ອ ແມ່ແບບ ມີ ຂໍ້ ຕາມ ປະເພດ) --}}
                    @if ($insTemplateNeedsFuel)
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">ປະເພດ / Type <span class="text-red-500">*</span></label>
                            <div class="flex gap-4 text-sm">
                                <label class="inline-flex items-center gap-1.5"><input type="radio" wire:model.live="insFuelType" value="engine" class="text-sky-600"> ນ້ຳມັນ (Engine)</label>
                                <label class="inline-flex items-center gap-1.5"><input type="radio" wire:model.live="insFuelType" value="ev" class="text-sky-600"> ໄຟຟ້າ (EV)</label>
                            </div>
                            @if ($insFuelType === '')
                                <p class="text-xs text-amber-600 mt-1">ເລືອກ ປະເພດ ກ່ອນ ຈຶ່ງ ສະແດງ ລາຍການ ກວດ</p>
                            @endif
                            @error('insFuelType')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    @endif
                    @if (count($insChecklist))
                        <div class="overflow-x-auto border border-gray-200 rounded-md">
                            <table class="w-full text-xs" style="table-layout:fixed;">
                                <colgroup><col style="width:26px"><col><col style="width:96px"><col style="width:104px"></colgroup>
                                <thead class="bg-gray-50 text-gray-600">
                                    <tr>
                                        <th class="px-1 py-1.5 border-b border-gray-200 text-center font-semibold">#</th>
                                        <th class="px-2 py-1.5 border-b border-gray-200 text-left font-semibold">ລາຍການ ກວດ</th>
                                        <th class="px-1 py-1.5 border-b border-gray-200 text-center font-semibold">ສະຖານະ</th>
                                        <th class="px-2 py-1.5 border-b border-gray-200 text-left font-semibold">ໝາຍເຫດ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($insChecklist as $i => $c)
                                        <tr wire:key="chk-{{ $i }}" class="border-b border-gray-50 last:border-0">
                                            <td class="px-1 py-1 text-center text-gray-400 align-top">{{ $i + 1 }}</td>
                                            <td class="px-2 py-1 text-gray-700 align-top leading-tight">{{ $c['label'] }}</td>
                                            <td class="px-1 py-1 align-top">
                                                <div class="flex gap-1 justify-center">
                                                    <button type="button" wire:click="toggleChecklist({{ $i }}, 'pass')" class="w-9 rounded border py-0.5 {{ ($c['status'] ?? '') === 'pass' ? 'bg-green-50 text-green-700 border-green-300 font-medium' : 'border-gray-300 text-gray-400' }}">OK</button>
                                                    <button type="button" wire:click="toggleChecklist({{ $i }}, 'fail')" class="w-9 rounded border py-0.5 {{ ($c['status'] ?? '') === 'fail' ? 'bg-red-50 text-red-700 border-red-300 font-medium' : 'border-gray-300 text-gray-400' }}">NG</button>
                                                </div>
                                            </td>
                                            <td class="px-1 py-1 align-top"><input type="text" wire:model="insChecklist.{{ $i }}.note" placeholder="—" class="w-full rounded border-gray-300 text-xs py-1" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-gray-400">OK = ຜ່ານ (ໃຊ້ໄດ້) · NG = ບໍ່ຜ່ານ (ຕ້ອງ ສ້ອມ) · ບໍ່ ເລືອກ = N/A (ບໍ່ ກ່ຽວ). ຄິດ % ອັດຕະໂນມັດ ຈາກ OK/NG.</p>
                    @endif
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    {{-- ຜູ້ ກວດ (ຈາກ ຜູ້ ລ໋ອກອິນ) + ເວລາ (ສະແຕັມ ອັດຕະໂນມັດ) --}}
                    <div class="md:col-span-2 flex flex-wrap gap-x-6 gap-y-1 text-sm bg-gray-50 border border-gray-100 rounded-md px-3 py-2">
                        <span class="text-gray-500">ຜູ້ ກວດ: <span class="font-medium text-gray-800">{{ auth()->user()->name }}</span></span>
                        <span class="text-gray-500">ວັນທີ/ເວລາ:
                            <span class="font-medium text-gray-800">
                                @if ($editingInspectionId){{ $insDate ? \Illuminate\Support\Carbon::parse($insDate)->format('d/m/Y') : '—' }} <span class="text-xs text-gray-400">(ຕາມ ເດີມ)</span>@else ສະແຕັມ ອັດຕະໂນມັດ ຕອນ ບັນທຶກ @endif
                            </span>
                        </span>
                    </div>

                    {{-- ຜົນ ກວດ — ຄິດ ຈາກ ເຊັກລິສ ຖ້າ ມີ, ບໍ່ ດັ່ງນັ້ນ ເລືອກ ເອງ --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ຜົນ ກວດ</label>
                        @if ($this->insScore)
                            @php $sc = $this->insScore; $scRes = ['pass' => ['ຜ່ານ', 'bg-green-50 text-green-700 border-green-200'], 'follow_up' => ['ຕ້ອງຕິດຕາມ', 'bg-amber-50 text-amber-700 border-amber-200'], 'fail' => ['ບໍ່ຜ່ານ - ຫ້າມ ໃຊ້ງານ', 'bg-red-50 text-red-700 border-red-200']][$sc['result']]; @endphp
                            <div class="rounded-md border {{ $scRes[1] }} px-3 py-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium">{{ $scRes[0] }}</span>
                                    <span class="text-sm">ສະພາບ ໂດຍ ລວມ <b>{{ $sc['pct'] }}%</b></span>
                                </div>
                                <div class="mt-1 h-2 rounded bg-white/60 overflow-hidden">
                                    <div class="h-full bg-current opacity-60" style="width: {{ $sc['pct'] }}%"></div>
                                </div>
                                <div class="mt-1 text-xs opacity-80">ຜ່ານ {{ $sc['pass'] }} · ບໍ່ຜ່ານ {{ $sc['fail'] }} · ບໍ່ກ່ຽວ {{ $sc['na'] }} (ຄິດ ຈາກ {{ $sc['considered'] }} ຂໍ້)</div>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">ຄິດໄລ່ ອັດຕະໂນມັດ ຈາກ ເຊັກລິສ — 100%=ຜ່ານ · 70–99%=ຕິດຕາມ · ຕ່ຳກວ່າ 70%=ບໍ່ຜ່ານ.</p>
                        @else
                            <select wire:model="insResult" class="w-full rounded-md border-gray-300 text-sm">
                                <option value="pass">ຜ່ານ</option>
                                <option value="fail">ບໍ່ຜ່ານ</option>
                                <option value="follow_up">ຕ້ອງຕິດຕາມ</option>
                            </select>
                            <p class="text-xs text-gray-400 mt-1">ບໍ່ ໄດ້ ໃຊ້ ເຊັກລິສ — ເລືອກ ຜົນ ເອງ.</p>
                        @endif
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ກຳນົດ ກວດ ຄັ້ງ ໜ້າ</label>
                        <input type="date" wire:model="insNextDue" class="w-full rounded-md border-gray-300 text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ໝາຍເຫດ</label>
                        <textarea wire:model="insNotes" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                    </div>

                    {{-- ຮູບ (ຝັງ ວັນທີ+ເວລາ ອັດຕະໂນມັດ) --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ຮູບ ກວດກາ <span class="text-xs text-gray-400">(📷 ກ້ອງ/ແກເລີຣີ · ຝັງ ວັນທີ+ເວລາ ໃຫ້ ອັດຕະໂນມັດ)</span></label>
                        @if ($insPhoto)
                            <img src="{{ $insPhoto->temporaryUrl() }}" class="w-24 h-24 rounded object-cover border border-sky-200 mb-2" alt="ຮູບ" />
                        @endif
                        <input type="file" wire:model="insPhoto" accept="image/*" class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-sky-50 file:text-sky-700 file:min-h-[40px]" />
                        <div wire:loading wire:target="insPhoto" class="text-xs text-gray-400 mt-1">ກຳລັງ ອັບໂຫຼດ…</div>
                        @error('insPhoto')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- ອັບເດດ ສະຖານະ ຈາກ ຜົນ ກວດ --}}
                    <div class="md:col-span-2 border-t border-gray-100 pt-3">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" wire:model.live="insUpdateStatus" class="rounded border-gray-300 text-sky-600"> ອັບເດດ ສະຖານະ ເຄື່ອງ ຈາກ ຜົນ ກວດ
                        </label>
                        @if ($insUpdateStatus)
                            <div class="grid grid-cols-2 gap-3 mt-2">
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">ຈຳນວນ ຊ່ອມແປງ</label>
                                    <input type="number" min="0" wire:model="insRepair" class="w-full rounded-md border-gray-300 text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">ຈຳນວນ ຢຸດໃຊ້</label>
                                    <input type="number" min="0" wire:model="insRetired" class="w-full rounded-md border-gray-300 text-sm" />
                                    @error('insRepair')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div class="col-span-2 text-xs text-gray-400">ໃຊ້ງານ = {{ $insEquipmentQty }} − ຊ່ອມ − ຢຸດ.</div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button wire:click="$set('showInspectionModal', false)" class="text-sm text-gray-700 border border-gray-300 rounded-md px-4 py-2 min-h-[40px] hover:bg-gray-50">ຍົກເລີກ</button>
                    <button wire:click="saveInspection" wire:loading.attr="disabled" wire:target="saveInspection,insPhoto" class="text-sm text-white bg-sky-600 rounded-md px-4 py-2 min-h-[40px] hover:bg-sky-700 disabled:opacity-50">ບັນທຶກ</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Inspection detail (ເບິ່ງ ລາຍລະອຽດ · read-only) --}}
    @if ($viewingInspection)
        @php
            $vrb = ['pass' => 'bg-green-50 text-green-700', 'fail' => 'bg-red-50 text-red-700', 'follow_up' => 'bg-amber-50 text-amber-700'];
            $vrl = ['pass' => 'ຜ່ານ', 'fail' => 'ບໍ່ຜ່ານ - ຫ້າມ ໃຊ້ງານ', 'follow_up' => 'ຕ້ອງຕິດຕາມ'];
            $vcl = ['pass' => ['OK · ຜ່ານ', 'text-green-700'], 'fail' => ['NG · ບໍ່ຜ່ານ', 'text-red-700'], 'na' => ['N/A', 'text-gray-400']];
            $vfn = 'inspection-'.\Illuminate\Support\Str::slug($viewingInspection->equipment?->asset_code ?? 'eq').'-'.($viewingInspection->inspected_at?->format('Ymd-Hi') ?? 'na');
        @endphp
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="ins-view-modal">
            <div class="bg-white w-full md:max-w-sm rounded-t-lg md:rounded-lg p-3 space-y-2 max-h-[90vh] overflow-y-auto">
                {{-- ໃບ ກວດ ຮູບແບບ ໃບບິນ ~3.5" (336px) --}}
                <div id="ins-detail-card" class="w-[336px] max-w-full mx-auto bg-white text-gray-800 text-[11px] leading-tight space-y-1.5">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">ໃບ ກວດ ສະພາບ ເຄື່ອງ</h3>
                            <div class="text-[11px] text-gray-500">ປະເພດ ເຄື່ອງ: {{ $viewingInspection->equipment?->category ?? '—' }}</div>
                        </div>
                        <button data-noexport wire:click="$set('viewingInspectionId', null)" class="text-gray-400 hover:text-gray-700 p-1 shrink-0" aria-label="Close">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="border-t border-dashed border-gray-300"></div>

                    <div class="space-y-0.5">
                        <div><span class="text-gray-500">ເຄື່ອງ:</span> {{ $viewingInspection->equipment?->asset_code }} · {{ $viewingInspection->equipment?->name }}</div>
                        <div><span class="text-gray-500">ຜູ້ ກວດ:</span> {{ $viewingInspection->inspector_name ?? '—' }}</div>
                        <div><span class="text-gray-500">ວັນທີ/ເວລາ:</span> {{ $viewingInspection->inspected_at?->format('d/m/Y H:i') }}</div>
                        @if ($viewingInspection->template)
                            <div><span class="text-gray-500">ແມ່ແບບ:</span> {{ $viewingInspection->template->name }}</div>
                        @endif
                        @if ($viewingInspection->fuel_type)
                            <div><span class="text-gray-500">ປະເພດ:</span> {{ ['ev' => 'ໄຟຟ້າ (EV)', 'engine' => 'ນ້ຳມັນ (Engine)'][$viewingInspection->fuel_type] ?? $viewingInspection->fuel_type }}</div>
                        @endif
                        <div class="flex items-center gap-1 pt-0.5 flex-wrap">
                            <span class="text-gray-500">ຜົນ:</span>
                            <span class="rounded px-1.5 py-0.5 font-medium {{ $vrb[$viewingInspection->result] ?? 'bg-gray-100 text-gray-600' }}">{{ $vrl[$viewingInspection->result] ?? $viewingInspection->result }}</span>
                            @if (! is_null($viewingInspection->score))<span class="text-gray-600">ສະພາບ ໂດຍ ລວມ <b>{{ $viewingInspection->score }}%</b></span>@endif
                        </div>
                        @if ($viewingInspection->next_due_date)
                            <div><span class="text-gray-500">ກວດ ຄັ້ງ ໜ້າ:</span> {{ $viewingInspection->next_due_date->format('d/m/Y') }}</div>
                        @endif
                    </div>

                    @if (! empty($viewingInspection->checklist))
                        <div class="border border-gray-200 rounded divide-y divide-gray-100">
                            @foreach ($viewingInspection->checklist as $ci => $c)
                                @php $cs = $vcl[$c['status'] ?? 'pass'] ?? ['—', 'text-gray-400']; @endphp
                                <div class="px-1.5 py-1 flex items-start justify-between gap-2">
                                    <div class="text-gray-700">{{ $ci + 1 }}. {{ $c['label'] ?? '' }}
                                        @if (! empty($c['note']))<div class="text-[10px] text-gray-400">↳ {{ $c['note'] }}</div>@endif
                                    </div>
                                    <span class="font-medium whitespace-nowrap {{ $cs[1] }}">{{ $cs[0] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($viewingInspection->notes)
                        <div><span class="text-gray-500">ໝາຍເຫດ:</span> {{ $viewingInspection->notes }}</div>
                    @endif

                    @if ($viewingInspection->photo_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($viewingInspection->photo_path) }}" @click="bigImg='{{ \Illuminate\Support\Facades\Storage::url($viewingInspection->photo_path) }}'" class="w-24 h-24 rounded object-cover border border-gray-200 cursor-pointer hover:ring-2 hover:ring-sky-400" alt="ຮູບ ກວດກາ" />
                    @endif
                </div>{{-- /#ins-detail-card --}}

                <div class="flex flex-wrap justify-end gap-2 pt-2 border-t">
                    <button type="button" onclick="window.exportPdf('ins-detail-card', '{{ $vfn }}.pdf')" class="text-sm text-white bg-sky-600 rounded-md px-4 py-2 min-h-[40px] hover:bg-sky-700">⬇ PDF</button>
                    <button type="button" onclick="window.exportJpg('ins-detail-card', '{{ $vfn }}.jpg')" class="text-sm text-sky-700 border border-sky-200 rounded-md px-4 py-2 min-h-[40px] hover:bg-sky-50">⬇ JPG</button>
                    @can('equipment.edit')
                        <button wire:click="editInspection({{ $viewingInspection->id }})" class="text-sm text-sky-700 border border-sky-200 rounded-md px-4 py-2 min-h-[40px] hover:bg-sky-50">ແກ້ໄຂ</button>
                    @endcan
                    <button wire:click="$set('viewingInspectionId', null)" class="text-sm text-gray-700 border border-gray-300 rounded-md px-4 py-2 min-h-[40px] hover:bg-gray-50">ປິດ</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Lightbox — ກົດ ຮູບ ເບິ່ງ ໃຫຍ່ --}}
    <div x-show="bigImg" @click="bigImg=null" @keydown.escape.window="bigImg=null"
         class="fixed inset-0 z-[60] bg-black/80 flex items-center justify-center p-4" style="display:none">
        <img :src="bigImg" class="max-w-full max-h-full rounded shadow-lg" alt="ຮູບ ໃຫຍ່" />
        <button @click="bigImg=null" class="absolute top-4 right-4 text-white text-4xl leading-none" aria-label="ປິດ">&times;</button>
    </div>
</div>
