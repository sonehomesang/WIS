<div class="py-6 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Organization</h2>
                <p class="text-sm text-gray-500">Unit → Department · ກົດ Unit ເພື່ອເບິ່ງ Departments</p>
            </div>
            <div x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)"
                 x-show="show" style="display:none"
                 class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-1">ບັນທຶກແລ້ວ ✓</div>
        </div>

        <div class="flex flex-col md:flex-row gap-4 items-start">
            {{-- Units --}}
            <div class="w-full md:w-1/3 bg-white border border-gray-100 rounded-lg overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <span class="font-medium text-sm text-gray-700">Units (ໜ່ວຍງານ)</span>
                    @can('units.create')
                        <button wire:click="newUnit" class="inline-flex items-center gap-1 text-xs text-gray-600 border border-gray-300 rounded-md px-2 py-1 min-h-[32px] hover:bg-gray-50">+ Add</button>
                    @endcan
                </div>
                <ul>
                    @forelse ($units as $unit)
                        <li wire:key="unit-{{ $unit->id }}" class="flex items-center justify-between px-2 py-1 border-b border-gray-100 {{ $selectedUnitId === $unit->id ? 'bg-sky-50' : 'hover:bg-gray-50' }}">
                            <button wire:click="selectUnit({{ $unit->id }})" class="flex-1 text-left px-2 py-2 min-h-[40px] text-sm {{ $selectedUnitId === $unit->id ? 'text-sky-700 font-medium' : 'text-gray-700' }}">
                                {{ $unit->name }}
                                @unless ($unit->is_active)<span class="text-xs text-gray-400">(inactive)</span>@endunless
                            </button>
                            <span class="flex items-center gap-2 pr-2">
                                <span class="text-xs text-gray-400">{{ $unit->departments_count }}</span>
                                @can('units.edit')
                                    <button wire:click="editUnit({{ $unit->id }})" class="text-gray-400 hover:text-gray-700 p-1" aria-label="Edit unit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /></svg>
                                    </button>
                                @endcan
                            </span>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-sm text-gray-400 text-center">ຍັງບໍ່ມີ unit</li>
                    @endforelse
                </ul>
            </div>

            {{-- Departments --}}
            <div class="w-full md:flex-1 bg-white border border-gray-100 rounded-lg overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <span class="font-medium text-sm text-gray-700">Departments @if ($selectedUnit)— {{ $selectedUnit->name }}@endif</span>
                    @can('departments.create')
                        <button wire:click="newDepartment" @disabled(! $selectedUnitId) class="inline-flex items-center gap-1 text-xs text-white bg-sky-600 rounded-md px-2 py-1 min-h-[32px] hover:bg-sky-700 disabled:opacity-40">+ Add</button>
                    @endcan
                </div>
                <ul>
                    @forelse ($departments as $d)
                        <li wire:key="dept-{{ $d->id }}" class="flex items-center justify-between px-4 py-2 border-b border-gray-100 min-h-[44px]">
                            <span class="text-sm text-gray-700">{{ $d->name }}@if ($d->description)<span class="text-xs text-gray-400"> · {{ $d->description }}</span>@endif</span>
                            <span class="flex items-center gap-3">
                                <span class="text-xs px-2 py-0.5 rounded {{ $d->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ $d->is_active ? 'active' : 'inactive' }}</span>
                                @can('departments.edit')
                                    <button wire:click="editDepartment({{ $d->id }})" class="text-gray-400 hover:text-gray-700 p-1" aria-label="Edit department">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /></svg>
                                    </button>
                                @endcan
                            </span>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-sm text-gray-400 text-center">@if ($selectedUnitId)ຍັງບໍ່ມີ department — ກົດ + Add@else ເລືອກ unit ກ່ອນ @endif</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    {{-- Create / Edit modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="org-modal">
            <div class="bg-white w-full md:max-w-md rounded-t-lg md:rounded-lg p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-800">{{ $editingId ? 'ແກ້ໄຂ' : 'ເພີ່ມ' }} {{ $type === 'unit' ? 'Unit' : 'Department' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-700 p-1" aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="space-y-3">
                    @if ($type === 'department')
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">ໜ່ວຍງານ (Org Unit) <span class="text-red-500">*</span></label>
                            <select wire:model="unitId" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm">
                                @foreach ($units as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-400 mt-1">1 Org Unit ມີໄດ້ຫຼາຍ Department · department ຢູ່ໃຕ້ Org Unit</p>
                            @error('unitId')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    @endif
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ຊື່ (Name) <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                        @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ຊື່ອັງກິດ (name_en)</label>
                        <input type="text" wire:model="name_en" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ລາຍລະອຽດ</label>
                        <textarea wire:model="description" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm"></textarea>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-sky-600 focus:ring-sky-500" /> Active
                    </label>
                    @unless ($editingId)<p class="text-xs text-gray-400">slug ຈະສ້າງອັດຕະໂນມັດຈາກຊື່</p>@endunless
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button wire:click="$set('showModal', false)" class="text-sm text-gray-700 border border-gray-300 rounded-md px-4 py-2 min-h-[40px] hover:bg-gray-50">ຍົກເລີກ</button>
                    <button wire:click="save" class="text-sm text-white bg-sky-600 rounded-md px-4 py-2 min-h-[40px] hover:bg-sky-700">ບັນທຶກ</button>
                </div>
            </div>
        </div>
    @endif
</div>
