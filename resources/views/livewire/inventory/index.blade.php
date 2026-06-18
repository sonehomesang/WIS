@php
    $svgEdit = 'm16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z';
    $svgPower = 'M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9';
    $svgTrash = 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16';
    $statusBadge = fn ($s) => match ($s) {
        'available' => 'bg-green-50 text-green-700',
        'borrowed' => 'bg-blue-50 text-blue-700',
        'maintenance' => 'bg-gray-100 text-gray-600',
        'low-stock' => 'bg-red-50 text-red-700',
        default => 'bg-gray-100 text-gray-600',
    };
@endphp

<div class="py-6 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">WH Inventories</h2>
                <p class="text-sm text-gray-500">ເຄື່ອງມືພາຍໃນສາງ · {{ $items->total() }} ລາຍການ</p>
            </div>
            <div class="flex items-center gap-2">
                <select wire:model.live="statusFilter" class="rounded-md border-gray-300 text-sm">
                    <option value="">ທຸກ status</option>
                    <option value="available">available</option>
                    <option value="borrowed">borrowed</option>
                    <option value="maintenance">maintenance</option>
                    <option value="low-stock">low-stock</option>
                </select>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ ຊື່/category/brand…" class="rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                @can('inventory.create')<button wire:click="newItem" class="text-sm text-white bg-sky-600 rounded-md px-3 py-2 min-h-[40px] hover:bg-sky-700 whitespace-nowrap">+ Add</button>@endcan
            </div>
        </div>

        <div x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)" x-show="show" style="display:none"
             class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-1">ບັນທຶກແລ້ວ ✓</div>

        {{-- Desktop table --}}
        <div class="hidden md:block bg-white border border-gray-100 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500">
                    <tr>
                        <th class="text-left font-medium px-4 py-2">Item</th>
                        <th class="text-left font-medium px-4 py-2">Qty</th>
                        <th class="text-left font-medium px-4 py-2">Location</th>
                        <th class="text-left font-medium px-4 py-2">Status</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $it)
                        <tr wire:key="inv-{{ $it->id }}" class="border-t border-gray-100">
                            <td class="px-4 py-2">
                                <div class="flex items-center gap-2">
                                    @if ($photo = $it->primaryPhoto->first())
                                        <img src="{{ $photo->url }}" alt="" class="w-9 h-9 rounded object-cover border border-gray-200 shrink-0" />
                                    @endif
                                    <div>
                                        <div class="font-medium text-gray-800 {{ $it->is_active ? '' : 'opacity-50' }}">{{ $it->name }}</div>
                                        <div class="text-xs text-gray-400">{{ collect([$it->category, $it->brand, $it->model])->filter()->implode(' · ') ?: '—' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-2 text-gray-600">{{ $it->quantity }}@if ($it->unit) {{ $it->unit }}@endif</td>
                            <td class="px-4 py-2 text-gray-600 text-xs">{{ collect([$it->location?->name, $it->building?->name, $it->room?->name, $it->shelf_label])->filter()->implode(' / ') ?: '—' }}</td>
                            <td class="px-4 py-2"><span class="text-xs rounded px-2 py-0.5 {{ $statusBadge($it->status) }}">{{ $it->status }}</span></td>
                            <td class="px-4 py-2 text-right whitespace-nowrap text-gray-500">
                                @canany(['inventory.activate', 'inventory.deactivate'])<button wire:click="toggle({{ $it->id }})" class="p-1 {{ $it->is_active ? 'text-green-600 hover:text-gray-400' : 'text-gray-300 hover:text-green-600' }}" title="{{ $it->is_active ? 'Disable' : 'Enable' }}"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgPower }}" /></svg></button>@endcanany
                                @can('inventory.edit')<button wire:click="editItem({{ $it->id }})" class="p-1 hover:text-gray-800" aria-label="Edit"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgEdit }}" /></svg></button>@endcan
                                @can('inventory.delete')<button wire:click="delete({{ $it->id }})" wire:confirm="ລຶບ item ນີ້?" class="p-1 hover:text-red-600" aria-label="Delete"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgTrash }}" /></svg></button>@endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">ບໍ່ມີ item</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="md:hidden space-y-2">
            @forelse ($items as $it)
                <div wire:key="m-{{ $it->id }}" class="bg-white border border-gray-100 rounded-lg p-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            @if ($photo = $it->primaryPhoto->first())
                                <img src="{{ $photo->url }}" alt="" class="w-8 h-8 rounded object-cover border border-gray-200 shrink-0" />
                            @endif
                            <div class="font-medium text-gray-800 {{ $it->is_active ? '' : 'opacity-50' }}">{{ $it->name }}</div>
                        </div>
                        <span class="text-xs rounded px-2 py-0.5 {{ $statusBadge($it->status) }}">{{ $it->status }}</span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">Qty {{ $it->quantity }} {{ $it->unit }} · {{ collect([$it->location?->name, $it->building?->name])->filter()->implode(' / ') ?: '—' }}</div>
                    <div class="flex gap-2 mt-2">
                        @canany(['inventory.activate', 'inventory.deactivate'])<button wire:click="toggle({{ $it->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px]">{{ $it->is_active ? 'Disable' : 'Enable' }}</button>@endcanany
                        @can('inventory.edit')<button wire:click="editItem({{ $it->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px]">Edit</button>@endcan
                        @can('inventory.delete')<button wire:click="delete({{ $it->id }})" wire:confirm="ລຶບ item ນີ້?" class="text-xs border rounded px-2 py-1 min-h-[36px]">Delete</button>@endcan
                    </div>
                </div>
            @empty
                <div class="text-center text-gray-400 py-6">ບໍ່ມີ item</div>
            @endforelse
        </div>

        <div>{{ $items->links() }}</div>
    </div>

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="inv-modal">
            <div class="bg-white w-full md:max-w-2xl rounded-t-lg md:rounded-lg p-5 space-y-3 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-800">{{ $editingId ? 'ແກ້ໄຂ item' : 'ເພີ່ມ item ໃໝ່' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-700 p-1" aria-label="Close"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ຊື່ (Name) <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="name" class="w-full rounded-md border-gray-300 text-sm" />
                        @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div><label class="block text-sm text-gray-600 mb-1">Category</label><input type="text" wire:model="category" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div><label class="block text-sm text-gray-600 mb-1">Brand</label><input type="text" wire:model="brand" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div><label class="block text-sm text-gray-600 mb-1">Model</label><input type="text" wire:model="model" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div><label class="block text-sm text-gray-600 mb-1">Serial number</label><input type="text" wire:model="serial_number" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div><label class="block text-sm text-gray-600 mb-1">ຈຳນວນ (Qty) <span class="text-red-500">*</span></label><input type="number" min="0" wire:model="quantity" class="w-full rounded-md border-gray-300 text-sm" />@error('quantity')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror</div>
                    <div class="grid grid-cols-2 gap-2">
                        <div><label class="block text-sm text-gray-600 mb-1">Min qty</label><input type="number" min="0" wire:model="min_quantity" class="w-full rounded-md border-gray-300 text-sm" /></div>
                        <div><label class="block text-sm text-gray-600 mb-1">ໜ່ວຍ</label><select wire:model="unit" class="w-full rounded-md border-gray-300 text-sm"><option value="">—</option>@foreach ($uoms as $u)<option value="{{ $u->name }}">{{ $u->name }}</option>@endforeach</select></div>
                    </div>
                    <div><label class="block text-sm text-gray-600 mb-1">Location</label><select wire:model.live="location_id" class="w-full rounded-md border-gray-300 text-sm"><option value="">—</option>@foreach ($locations as $loc)<option value="{{ $loc->id }}">{{ $loc->name }}</option>@endforeach</select></div>
                    <div><label class="block text-sm text-gray-600 mb-1">Building</label><select wire:model.live="building_id" @disabled(! $location_id) class="w-full rounded-md border-gray-300 text-sm disabled:bg-gray-50"><option value="">—</option>@foreach ($formBuildings as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
                    <div><label class="block text-sm text-gray-600 mb-1">Room</label><select wire:model="room_id" @disabled(! $building_id) class="w-full rounded-md border-gray-300 text-sm disabled:bg-gray-50"><option value="">—</option>@foreach ($formRooms as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</select></div>
                    <div><label class="block text-sm text-gray-600 mb-1">Shelf</label><input type="text" wire:model="shelf_label" placeholder="A-3-2" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div><label class="block text-sm text-gray-600 mb-1">Status</label><select wire:model="status" class="w-full rounded-md border-gray-300 text-sm"><option value="available">available</option><option value="borrowed">borrowed</option><option value="maintenance">maintenance</option><option value="low-stock">low-stock</option></select></div>
                    <div class="md:col-span-2"><label class="block text-sm text-gray-600 mb-1">ລາຍລະອຽດ</label><textarea wire:model="description" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea></div>

                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ຮູບ (ສູງສຸດ {{ \App\Livewire\Inventory\Index::MAX_PHOTOS }} ໃບ · ≤4MB/ໃບ)</label>
                        @if (count($existingPhotos))
                            <div class="flex flex-wrap gap-2 mb-2">
                                @foreach ($existingPhotos as $p)
                                    <div wire:key="ep-{{ $p['id'] }}" class="relative">
                                        <img src="{{ $p['url'] }}" alt="" class="w-20 h-20 rounded-md object-cover border border-gray-200" />
                                        <button type="button" wire:click="removePhoto({{ $p['id'] }})" wire:confirm="ລຶບຮູບນີ້?" class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-red-600 text-white text-xs leading-none flex items-center justify-center" aria-label="ລຶບຮູບ">×</button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if (count($newPhotos))
                            <div class="flex flex-wrap gap-2 mb-2">
                                @foreach ($newPhotos as $i => $ph)
                                    <div wire:key="np-{{ $i }}">
                                        @if ($ph->isPreviewable())
                                            <img src="{{ $ph->temporaryUrl() }}" alt="" class="w-20 h-20 rounded-md object-cover border border-sky-300" />
                                        @else
                                            <div class="w-20 h-20 rounded-md border border-red-300 bg-red-50 text-red-500 text-[10px] flex items-center justify-center text-center px-1">ບໍ່ແມ່ນຮູບ</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <input type="file" wire:model="newPhotos" multiple accept="image/*" class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-sky-50 file:text-sky-700 file:min-h-[40px]" />
                        <div wire:loading wire:target="newPhotos" class="text-xs text-gray-400 mt-1">ກຳລັງອັບໂຫລດ…</div>
                        @error('newPhotos.*')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        @error('newPhotos')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <label class="flex items-center gap-2 text-sm text-gray-700 md:col-span-2"><input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-sky-600 focus:ring-sky-500" /> Active</label>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button wire:click="$set('showModal', false)" class="text-sm text-gray-700 border border-gray-300 rounded-md px-4 py-2 min-h-[40px] hover:bg-gray-50">ຍົກເລີກ</button>
                    <button wire:click="save" class="text-sm text-white bg-sky-600 rounded-md px-4 py-2 min-h-[40px] hover:bg-sky-700">ບັນທຶກ</button>
                </div>
            </div>
        </div>
    @endif
</div>
