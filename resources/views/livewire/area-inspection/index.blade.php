<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

        @if (session('ok'))
            <div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2">{{ session('ok') }}</div>
        @endif

        {{-- Tabs --}}
        <div class="flex items-center gap-1 border-b border-gray-200">
            <button wire:click="setTab('records')" class="px-3 py-2 text-sm border-b-2 -mb-px {{ $tab === 'records' ? 'border-sky-600 text-sky-700 font-medium' : 'border-transparent text-gray-500 hover:text-gray-700' }}">ບັນທຶກ ການ ກວດ</button>
            <button wire:click="setTab('templates')" class="px-3 py-2 text-sm border-b-2 -mb-px {{ $tab === 'templates' ? 'border-sky-600 text-sky-700 font-medium' : 'border-transparent text-gray-500 hover:text-gray-700' }}">ແມ່ແບບ ເຊັກລິສ</button>
        </div>

        {{-- ══════════ TAB: records (ລົງມື ກວດ) ══════════ --}}
        @if ($tab === 'records')
            <div class="flex flex-wrap items-center gap-2 justify-between">
                <div class="text-sm text-gray-500">@if ($showDeleted) 🗑️ Deleted Log @else ລາຍການ ໃບ ກວດ ທີ່ ບັນທຶກ ແລ້ວ @endif</div>
                <div class="flex items-center gap-2">
                    @if ($this->canManageDeleted())
                        <button wire:click="toggleDeleted" class="text-xs text-gray-600 border border-gray-300 rounded-md px-2 py-1 min-h-[32px] hover:bg-gray-50">{{ $showDeleted ? '← ກັບ ລາຍການ' : 'ບັນທຶກ ການ ລຶບ' }}</button>
                    @endif
                    @can('area_inspection.create')
                        @unless ($showDeleted)
                            <button wire:click="newInspection" class="inline-flex items-center gap-1 text-sm text-white bg-sky-600 rounded-md px-3 py-1.5 min-h-[36px] hover:bg-sky-700">+ ບັນທຶກ ການ ກວດ</button>
                        @endunless
                    @endcan
                </div>
            </div>

            <div class="bg-white border border-gray-100 rounded-lg overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th class="text-left font-medium px-3 py-2">ເລກ</th>
                            <th class="text-left font-medium px-3 py-2">ສະຖານທີ່</th>
                            <th class="text-left font-medium px-3 py-2">ວັນທີ</th>
                            <th class="text-left font-medium px-3 py-2">ຮອບ</th>
                            <th class="text-left font-medium px-3 py-2">ຜົນ</th>
                            <th class="text-left font-medium px-3 py-2">ຮັບຊາບ</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($rows as $r)
                            <tr wire:key="ai-{{ $r->id }}" class="hover:bg-gray-50/60">
                                <td class="px-3 py-2 font-mono text-xs text-gray-600">{{ $r->inspection_number }}</td>
                                <td class="px-3 py-2 text-gray-800">{{ $r->locationName() }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $r->inspected_on?->format('d/m/Y') }} @if ($r->inspected_time)<span class="text-gray-400">{{ $r->inspected_time }}</span>@endif</td>
                                <td class="px-3 py-2 text-gray-600">{{ $freqLabels[$r->frequency] ?? $r->frequency }}</td>
                                <td class="px-3 py-2">
                                    @if ($r->result === 'has_nc')
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-50 text-red-700">NC · {{ $r->ncCount() }} ຂໍ້</span>
                                    @else
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-green-50 text-green-700">ຜ່ານ (C)</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    @if ($r->acknowledged_at)
                                        <span class="text-xs text-emerald-700" title="{{ $r->acknowledged_at?->format('d/m/Y H:i') }}">✓ {{ $r->acknowledged_by_name }}</span>
                                    @else<span class="text-xs text-gray-400">—</span>@endif
                                </td>
                                <td class="px-3 py-2 text-right whitespace-nowrap">
                                    @if ($showDeleted)
                                        @if ($this->canManageDeleted())<button wire:click="restore({{ $r->id }})" class="text-xs text-sky-600 hover:underline">ກູ້ຄືນ</button>@endif
                                    @else
                                        @if ($canAck)
                                            @if ($r->acknowledged_at)
                                                <button wire:click="unacknowledge({{ $r->id }})" class="text-xs text-gray-500 hover:underline">ຍົກ ຮັບຊາບ</button>
                                            @else
                                                <button wire:click="acknowledge({{ $r->id }})" class="text-xs text-emerald-700 hover:underline">ຮັບຊາບ</button>
                                            @endif
                                        @endif
                                        @can('area_inspection.delete')<button wire:click="openDelete({{ $r->id }})" class="text-xs text-red-600 hover:underline ml-2">ລຶບ</button>@endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-3 py-8 text-center text-gray-400 text-sm">ຍັງ ບໍ່ ມີ ໃບ ກວດ — ກົດ "+ ບັນທຶກ ການ ກວດ".</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $rows->links() }}</div>
        @endif

        {{-- ══════════ TAB: templates (ແມ່ແບບ ເຊັກລິສ) ══════════ --}}
        @if ($tab === 'templates')
            <div class="flex flex-wrap items-center gap-2 justify-between">
                <div class="text-sm text-gray-500">ແມ່ແບບ ເຊັກລິສ ກວດ ສະຖານທີ່ — ໃຊ້ ຕອນ ບັນທຶກ ການ ກວດ</div>
                @can('area_inspection.create')
                    <button wire:click="newTemplate" class="inline-flex items-center gap-1 text-sm text-white bg-sky-600 rounded-md px-3 py-1.5 min-h-[36px] hover:bg-sky-700">+ ສ້າງ ແມ່ແບບ</button>
                @endcan
            </div>

            <div class="bg-white border border-gray-100 rounded-lg overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th class="text-left font-medium px-3 py-2">ຊື່ ແມ່ແບບ</th>
                            <th class="text-left font-medium px-3 py-2">ຮອບ</th>
                            <th class="text-left font-medium px-3 py-2">ຈຳນວນ ຂໍ້</th>
                            <th class="text-left font-medium px-3 py-2">ສະຖານະ</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($allTemplates as $t)
                            <tr wire:key="tpl-{{ $t->id }}" class="hover:bg-gray-50/60">
                                <td class="px-3 py-2 text-gray-800 font-medium">{{ $t->name }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $freqLabels[$t->frequency] ?? $t->frequency }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ count($t->normalizedItems()) }} ຂໍ້</td>
                                <td class="px-3 py-2">
                                    @if ($t->is_active)
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-green-50 text-green-700">ໃຊ້ງານ</span>
                                    @else
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">ປິດ</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right whitespace-nowrap">
                                    @can('area_inspection.edit')<button wire:click="editTemplate({{ $t->id }})" class="text-xs text-sky-600 hover:underline">ແກ້ໄຂ</button>@endcan
                                    <button wire:click="toggleTemplateActive({{ $t->id }})" class="text-xs text-gray-500 hover:underline ml-2">{{ $t->is_active ? 'ປິດ' : 'ເປີດ' }}</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-3 py-8 text-center text-gray-400 text-sm">ຍັງ ບໍ່ ມີ ແມ່ແບບ — ກົດ "+ ສ້າງ ແມ່ແບບ".</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ══════════ Record modal ══════════ --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-start justify-center bg-black/40 p-4 overflow-y-auto">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl my-6">
                <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                    <div class="font-medium text-gray-800">ບັນທຶກ ການ ກວດ ສະຖານທີ່</div>
                    <button wire:click="$set('showForm', false)" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>
                <div class="p-5 space-y-4 max-h-[75vh] overflow-y-auto">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">ແມ່ແບບ ເຊັກລິສ</label>
                            <select wire:model.live="fTemplateId" class="w-full rounded-md border-gray-300 text-sm">
                                <option value="">— ເລືອກ ແມ່ແບບ —</option>
                                @foreach ($templates as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }} ({{ $freqLabels[$t->frequency] ?? $t->frequency }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">ຮອບ</label>
                            <select wire:model="fFrequency" class="w-full rounded-md border-gray-300 text-sm">
                                @foreach ($freqLabels as $k => $lbl)<option value="{{ $k }}">{{ $lbl }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">ສະຖານທີ່ (ເລືອກ)</label>
                            <select wire:model="fLocationId" class="w-full rounded-md border-gray-300 text-sm">
                                <option value="">— ເລືອກ ຈາກ Facilities —</option>
                                @foreach ($locations as $loc)<option value="{{ $loc->id }}">{{ $loc->name }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">ຫຼື ພິມ ຊື່ ສະຖານທີ່</label>
                            <input type="text" wire:model="fLocationLabel" placeholder="ເຊັ່ນ: Chemical Room 2" class="w-full rounded-md border-gray-300 text-sm">
                            @error('fLocationLabel')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">ວັນທີ</label>
                            <input type="date" wire:model="fDate" class="w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">ເວລາ</label>
                            <input type="text" wire:model="fTime" placeholder="8:00" class="w-full rounded-md border-gray-300 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">ຜູ້ ກວດ (ສูงສุด 3)</label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            @for ($k = 0; $k < 3; $k++)<input type="text" wire:model="fInspectors.{{ $k }}" placeholder="ຜູ້ ກວດ {{ $k + 1 }}" class="w-full rounded-md border-gray-300 text-sm">@endfor
                        </div>
                    </div>
                    @error('checklist')<div class="text-xs text-red-600">{{ $message }}</div>@enderror
                    @if (count($checklist))
                        <div class="border border-gray-100 rounded-lg divide-y divide-gray-100">
                            @foreach ($checklist as $i => $row)
                                <div wire:key="cl-{{ $i }}" class="p-3 space-y-2">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="text-sm">
                                            <div class="font-medium text-gray-800">{{ $i + 1 }}. {{ $row['label'] }}</div>
                                            @if (!empty($row['requirement']))<div class="text-xs text-gray-500">{{ $row['requirement'] }}</div>@endif
                                        </div>
                                        <div class="flex gap-1 flex-shrink-0">
                                            @foreach (['C' => 'bg-green-600', 'NC' => 'bg-red-600', 'NA' => 'bg-gray-400'] as $st => $active)
                                                <label class="cursor-pointer">
                                                    <input type="radio" wire:model="checklist.{{ $i }}.status" value="{{ $st }}" class="sr-only peer">
                                                    <span class="inline-block text-xs font-medium px-2 py-1 rounded border border-gray-300 text-gray-500 peer-checked:text-white peer-checked:border-transparent peer-checked:{{ $active }}">{{ $st }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <input type="text" wire:model="checklist.{{ $i }}.observation" placeholder="ໝາຍເຫດ / corrective action" class="w-full rounded-md border-gray-300 text-xs">
                                        <input type="file" wire:model="photos.{{ $i }}" accept="image/*" class="text-xs text-gray-500">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-sm text-gray-400 text-center py-4">ເລືອກ ແມ່ແບບ ເພື່ອ ໂຫຼດ ຂໍ້ ກວດ.</div>
                    @endif
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">ໝາຍເຫດ ລວມ</label>
                        <textarea wire:model="fNotes" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                    </div>
                </div>
                <div class="px-5 py-3 border-t border-gray-100 flex justify-end gap-2">
                    <button wire:click="$set('showForm', false)" class="text-sm text-gray-600 px-3 py-1.5">ຍົກເລີກ</button>
                    <button wire:click="save" class="text-sm text-white bg-sky-600 rounded-md px-4 py-1.5 hover:bg-sky-700">ບັນທຶກ</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ Template builder modal ══════════ --}}
    @if ($showTemplateForm)
        <div class="fixed inset-0 z-50 flex items-start justify-center bg-black/40 p-4 overflow-y-auto">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl my-6">
                <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                    <div class="font-medium text-gray-800">{{ $tEditingId ? 'ແກ້ໄຂ ແມ່ແບບ' : 'ສ້າງ ແມ່ແບບ ໃໝ່' }}</div>
                    <button wire:click="$set('showTemplateForm', false)" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>
                <div class="p-5 space-y-4 max-h-[75vh] overflow-y-auto">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs text-gray-500 mb-1">ຊື່ ແມ່ແບບ</label>
                            <input type="text" wire:model="tName" placeholder="ເຊັ່ນ: ກວດ ຫ້ອງ ເຄມີ ປະຈຳ ເດືອນ" class="w-full rounded-md border-gray-300 text-sm">
                            @error('tName')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">ຮອບ</label>
                            <select wire:model="tFrequency" class="w-full rounded-md border-gray-300 text-sm">
                                @foreach ($freqLabels as $k => $lbl)<option value="{{ $k }}">{{ $lbl }}</option>@endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-xs text-gray-500">ຂໍ້ ກວດ (label + ເງື່ອນໄຂ/requirement)</label>
                            <button wire:click="addTemplateItem" class="text-xs text-sky-600 hover:underline">+ ເພີ່ມ ຂໍ້</button>
                        </div>
                        @error('tItems')<div class="text-xs text-red-600 mb-1">{{ $message }}</div>@enderror
                        <div class="space-y-2">
                            @foreach ($tItems as $i => $it)
                                <div wire:key="ti-{{ $i }}" class="flex gap-2 items-start">
                                    <span class="text-xs text-gray-400 pt-2 w-5">{{ $i + 1 }}.</span>
                                    <input type="text" wire:model="tItems.{{ $i }}.label" placeholder="ຫົວຂໍ້ (ເຊັ່ນ Ventilation)" class="w-1/3 rounded-md border-gray-300 text-sm">
                                    <input type="text" wire:model="tItems.{{ $i }}.requirement" placeholder="ເງື່ອນໄຂ (ເຊັ່ນ Area is well-ventilated…)" class="flex-1 rounded-md border-gray-300 text-sm">
                                    <button wire:click="removeTemplateItem({{ $i }})" class="text-red-500 hover:text-red-700 pt-1.5 text-sm">✕</button>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="tActive" class="rounded border-gray-300"> ໃຊ້ງານ (ໂຜ່ ໃນ dropdown ຕອນ ບັນທຶກ)
                    </label>
                </div>
                <div class="px-5 py-3 border-t border-gray-100 flex justify-end gap-2">
                    <button wire:click="$set('showTemplateForm', false)" class="text-sm text-gray-600 px-3 py-1.5">ຍົກເລີກ</button>
                    <button wire:click="saveTemplate" class="text-sm text-white bg-sky-600 rounded-md px-4 py-1.5 hover:bg-sky-700">ບັນທຶກ ແມ່ແບບ</button>
                </div>
            </div>
        </div>
    @endif

    @include('partials._delete-modal')
</div>
