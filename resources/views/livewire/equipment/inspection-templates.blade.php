<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8">
        <div x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)" x-show="show" style="display:none"
             class="fixed bottom-4 right-4 z-50 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 shadow-lg">ບັນທຶກແລ້ວ ✓</div>

        {{-- frozen: ແທັບ ຫຼັກ 3 ອັນ (ໜ້າ ນີ້ ຢູ່ ໃຕ້ "ການ ກວດກາ") + toolbar --}}
        <div class="sticky top-16 z-30 bg-gray-100">
            <div class="flex gap-1 border-b border-gray-200">
                <a href="{{ route('equipment', ['tab' => 'register']) }}" wire:navigate class="px-4 py-2 text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700">ທະບຽນ ເຄື່ອງ</a>
                <a href="{{ route('equipment', ['tab' => 'inspection']) }}" wire:navigate class="px-4 py-2 text-sm border-b-2 border-sky-600 text-sky-700 font-medium">ການ ກວດກາ</a>
                <a href="{{ route('equipment', ['tab' => 'maintenance']) }}" wire:navigate class="px-4 py-2 text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700">ບຳລຸງຮັກສາ</a>
            </div>
            <div class="flex flex-wrap items-center gap-2 py-2">
                <span class="text-xs text-gray-500 mr-1 whitespace-nowrap">ແມ່ແບບ ການ ກວດກາ:</span>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ ຊື່ ແມ່ແບບ…" class="rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm w-52" />
                <select wire:model.live="categoryFilter" class="rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">ທຸກ ປະເພດ</option>
                    @foreach ($categories as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                </select>
                <div class="flex-1"></div>
                <span class="text-xs text-gray-400">{{ $templates->count() }} ແມ່ແບບ</span>
                @if ($canManageDeleted)
                    <button wire:click="toggleDeleted" class="text-sm border rounded-md px-3 py-2 min-h-[40px] whitespace-nowrap {{ $showDeleted ? 'bg-gray-700 text-white border-gray-700' : 'text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                        {{ $showDeleted ? '← ລາຍການ ປົກກະຕິ' : '🗑 ບັນທຶກ ການ ລຶບ' }}
                    </button>
                @endif
                @can('equipment.create')
                    <button wire:click="newTemplate" class="text-sm text-white bg-sky-600 rounded-md px-3 py-2 min-h-[40px] hover:bg-sky-700 whitespace-nowrap" @if ($showDeleted) style="display:none" @endif>+ ແມ່ແບບ ໃໝ່</button>
                @endcan
            </div>
        </div>

        <div class="bg-white border border-gray-100 rounded-lg overflow-x-hidden overflow-y-auto max-h-[calc(100vh-16rem)]">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 bg-gray-50 text-gray-600 text-xs border-b border-gray-200 shadow-sm">
                    <tr>
                        <th class="text-left px-3 py-2 font-semibold">ຊື່ ແມ່ແບບ</th>
                        <th class="text-left px-3 py-2 font-semibold">ປະເພດ ເຄື່ອງ</th>
                        <th class="text-left px-3 py-2 font-semibold">ຈຳນວນ ຂໍ້</th>
                        <th class="text-left px-3 py-2 font-semibold">ໃຊ້</th>
                        <th class="px-3 py-2 w-20"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($templates as $t)
                        <tr wire:key="tpl-{{ $t->id }}">
                            <td class="px-3 py-2 font-medium text-gray-800">
                                {{ $t->name }}
                                @if ($showDeleted)
                                    <div class="text-[11px] font-normal text-red-600 mt-0.5">🗑 ລຶບ: {{ $t->deleted_at?->format('d/m/Y H:i') }} · ໂດຍ {{ $t->deletedBy?->display_name ?? '—' }}@if ($t->deleted_reason) · ເຫດຜົນ: {{ $t->deleted_reason }}@endif</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-600">{{ $t->category ?? 'ທົ່ວໄປ' }}</td>
                            <td class="px-3 py-2 text-gray-600">{{ count($t->items ?? []) }}</td>
                            <td class="px-3 py-2"><span class="text-xs rounded px-2 py-0.5 {{ $t->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ $t->is_active ? 'ເປີດ' : 'ປິດ' }}</span></td>
                            <td class="px-3 py-2 pr-5 text-right whitespace-nowrap text-gray-500">
                                @if ($showDeleted)
                                    <button wire:click="restore({{ $t->id }})" class="text-xs text-emerald-700 border border-emerald-200 rounded px-2 py-1 hover:bg-emerald-50">↩ ກູ້ຄືນ</button>
                                @else
                                    <button wire:click="viewTemplate({{ $t->id }})" class="hover:text-sky-700 p-1" title="ເບິ່ງ">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                    </button>
                                    @can('equipment.edit')
                                        <button wire:click="editTemplate({{ $t->id }})" class="hover:text-gray-800 p-1" title="ແກ້ໄຂ">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /></svg>
                                        </button>
                                    @endcan
                                    @can('equipment.delete')
                                        <button wire:click="openDelete({{ $t->id }})" class="hover:text-red-600 p-1" title="ລຶບ">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-6 text-center text-gray-400">{{ $showDeleted ? 'ບໍ່ ມີ ແມ່ແບບ ທີ່ ຖືກ ລຶບ' : 'ຍັງບໍ່ມີ ແມ່ແບບ — ກົດ "+ ແມ່ແບບ ໃໝ່"' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ຢືນຢັນ ລຶບ + ເຫດຜົນ (shared partial + trait SoftDeletesWithReason) --}}
    @include('partials._delete-modal', ['title' => 'ລຶບ ແມ່ແບບ ນີ້?', 'subtitle' => $this->deletingRecord?->name])

    {{-- Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="tpl-modal">
            <div class="bg-white w-full md:max-w-lg rounded-t-lg md:rounded-lg p-5 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-800">{{ $editingId ? 'ແກ້ໄຂ ແມ່ແບບ' : 'ແມ່ແບບ ໃໝ່' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-700 p-1" aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @include('partials._form-errors', ['wrapClass' => 'md:col-span-2'])
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ຊື່ ແມ່ແບບ <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="tName" class="w-full rounded-md border-gray-300 text-sm" />
                        @error('tName')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ປະເພດ ເຄື່ອງ <span class="text-xs text-gray-400">(ວ່າງ = ທົ່ວໄປ)</span></label>
                        <input type="text" wire:model="tCategory" placeholder="Power tool · Sling…" class="w-full rounded-md border-gray-300 text-sm" />
                    </div>
                    <div class="flex items-end">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" wire:model="tActive" class="rounded border-gray-300 text-sky-600"> ເປີດ ໃຊ້
                        </label>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ວິທີ ກວດ</label>
                        <textarea wire:model="tMethod" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-gray-600 mb-1">ລາຍການ ເຊັກລິສ</label>
                    @php $tHasTyped = collect($tItems)->contains(fn ($x) => ($x['applies'] ?? 'both') !== 'both'); @endphp
                    @if ($tHasTyped)
                        <div class="flex items-center gap-2 mb-2 text-xs bg-sky-50 border border-sky-100 rounded-md px-2 py-1.5">
                            <span class="text-gray-600">ສະແດງ ຕາມ ປະເພດ:</span>
                            <select wire:model.live="tFilter" class="rounded-md border-gray-300 text-xs py-1">
                                <option value="all">ທັງໝົດ ({{ count($tItems) }} ຂໍ້)</option>
                                <option value="engine">ນ້ຳມັນ (Engine)</option>
                                <option value="ev">ໄຟຟ້າ (EV)</option>
                            </select>
                            <span class="text-gray-400">ຂໍ້ "ທັງ 2" ຂຶ້ນ ທຸກ ປະເພດ</span>
                        </div>
                    @endif
                    <div class="space-y-1.5">
                        @foreach ($tItems as $i => $item)
                            @php $ap = $item['applies'] ?? 'both'; @endphp
                            @continue($tFilter !== 'all' && $ap !== 'both' && $ap !== $tFilter)
                            <div wire:key="tit-{{ $i }}" class="border border-gray-100 rounded-md p-1.5">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs text-gray-400 w-5 text-right shrink-0">{{ $i + 1 }}.</span>
                                    <input type="text" wire:model="tItems.{{ $i }}.label" placeholder="ຂໍ້ ກວດ…" class="flex-1 rounded-md border-gray-300 text-xs py-1" />
                                    <select wire:model="tItems.{{ $i }}.applies" class="rounded-md border-gray-300 text-xs py-1 shrink-0" title="ໃຊ້ ກັບ ປະເພດ ໃດ">
                                        <option value="both">ທັງ 2</option>
                                        <option value="engine">ນ້ຳມັນ</option>
                                        <option value="ev">ໄຟຟ້າ</option>
                                    </select>
                                    <button type="button" wire:click="removeChecklistItem({{ $i }})" class="text-red-500 px-1 shrink-0" title="ລຶບ ຂໍ້">×</button>
                                </div>
                                <div class="flex items-center gap-2 flex-wrap mt-1 pl-6">
                                    <span class="text-[11px] text-gray-400">ຮອບ:</span>
                                    @foreach (\App\Models\InspectionTemplate::FREQ_LABELS as $fk => $fl)
                                        <label class="inline-flex items-center gap-1 text-[11px] text-gray-600">
                                            <input type="checkbox" value="{{ $fk }}" wire:model="tItems.{{ $i }}.freqs" class="rounded border-gray-300 text-sky-600 w-3.5 h-3.5"> {{ $fl }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-400 mt-1">ແຕ່ລະ ຂໍ້: ເລືອກ <b>ໃຊ້ ກັບ ປະເພດ</b> (ທັງ2/ນ້ຳມັນ/ໄຟຟ້າ) + ຕິກ <b>ຮອບ</b> ທີ່ ຕ້ອງ ກວດ (ຕິກ ຫຼາຍ ໄດ້; ບໍ່ ຕິກ = ຂຶ້ນ ທຸກ ຮອບ). ຕອນ ກວດ ຈິງ ຈະ ຄັດ ລິສ ຕາມ ຮອບ + ປະເພດ ທີ່ ເລືອກ.</p>
                    <button type="button" wire:click="addChecklistItem" class="mt-2 text-sm text-sky-600">+ ເພີ່ມ ຂໍ້</button>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t">
                    <button wire:click="$set('showModal', false)" class="text-sm text-gray-700 border border-gray-300 rounded-md px-4 py-2 min-h-[40px] hover:bg-gray-50">ຍົກເລີກ</button>
                    <button wire:click="save" class="text-sm text-white bg-sky-600 rounded-md px-4 py-2 min-h-[40px] hover:bg-sky-700">ບັນທຶກ</button>
                </div>
            </div>
        </div>
    @endif

    {{-- View (read-only) --}}
    @if ($viewing)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="tpl-view">
            <div class="bg-white w-full md:max-w-2xl rounded-t-lg md:rounded-lg p-5 space-y-3 max-h-[90vh] overflow-y-auto">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="text-lg font-medium text-gray-800">{{ $viewing->name }}</h3>
                        <div class="text-xs text-gray-500 mt-0.5">{{ $viewing->category ?? 'ທົ່ວໄປ' }} · <span class="rounded px-1.5 py-0.5 {{ $viewing->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ $viewing->is_active ? 'ເປີດ' : 'ປິດ' }}</span></div>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        @can('equipment.edit')
                            <button wire:click="editTemplate({{ $viewing->id }})" class="text-sm text-sky-700 border border-sky-200 rounded-md px-2.5 py-1.5 hover:bg-sky-50">ແກ້ໄຂ</button>
                        @endcan
                        <button wire:click="$set('viewingId', null)" class="text-gray-400 hover:text-gray-700 p-1" aria-label="Close">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>

                @if ($viewing->method)
                    <div class="text-xs text-gray-600 bg-gray-50 border border-gray-100 rounded-md px-3 py-2">{{ $viewing->method }}</div>
                @endif

                @php $vItems = $viewing->normalizedItems(); @endphp
                <div class="text-xs text-gray-500">{{ count($vItems) }} ຂໍ້</div>

                <div class="overflow-x-auto border border-gray-200 rounded-md">
                    <table class="w-full text-xs" style="min-width:560px">
                        <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                            <tr>
                                <th class="px-2 py-1.5 text-left font-semibold w-6">#</th>
                                <th class="px-2 py-1.5 text-left font-semibold">ລາຍການ</th>
                                <th class="px-2 py-1.5 text-left font-semibold w-20">ປະເພດ</th>
                                <th class="px-2 py-1.5 text-left font-semibold">ຮອບ ກວດ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vItems as $i => $c)
                                <tr wire:key="ivw-{{ $i }}" class="border-b border-gray-50 last:border-0">
                                    <td class="px-2 py-1 text-gray-400 align-top">{{ $i + 1 }}</td>
                                    <td class="px-2 py-1 align-top text-gray-700 leading-tight">{{ $c['label'] }}</td>
                                    <td class="px-2 py-1 align-top">
                                        @if ($c['applies'] === 'ev')<span class="text-[10px] font-bold rounded px-1.5 py-0.5 bg-teal-50 text-teal-700">ໄຟຟ້າ</span>
                                        @elseif ($c['applies'] === 'engine')<span class="text-[10px] font-bold rounded px-1.5 py-0.5 bg-amber-50 text-amber-700">ນ້ຳມັນ</span>
                                        @else<span class="text-gray-400 text-[10px]">ທັງ 2</span>@endif
                                    </td>
                                    <td class="px-2 py-1 align-top">
                                        @if (empty($c['freqs']))
                                            <span class="text-gray-400">ທຸກ ຮອບ</span>
                                        @else
                                            <div class="flex flex-wrap gap-1">
                                                @foreach (\App\Models\InspectionTemplate::FREQ_LABELS as $fk => $fl)
                                                    @if (in_array($fk, $c['freqs'], true))<span class="text-[10px] rounded px-1.5 py-0.5 bg-sky-50 text-sky-700">{{ $fl }}</span>@endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-3 py-6 text-center text-gray-400">ຍັງ ບໍ່ ມີ ລາຍການ</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end pt-1 border-t">
                    <button wire:click="$set('viewingId', null)" class="text-sm text-gray-700 border border-gray-300 rounded-md px-4 py-2 min-h-[40px] hover:bg-gray-50">ປິດ</button>
                </div>
            </div>
        </div>
    @endif
</div>
