<div class="pb-6">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        <a href="{{ route('borrow') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">← ກັບໄປ list</a>

        <div class="bg-white rounded-lg border border-gray-100 p-5 space-y-5">
            <div class="flex items-center justify-between">
                <div class="font-semibold text-gray-800">ຢືມ ເຄື່ອງ ໃໝ່</div>
                <a href="{{ route('borrow') }}" wire:navigate class="text-gray-400 hover:text-gray-700 p-1" title="ປິດ" aria-label="ປິດ">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </a>
            </div>

            @include('partials._form-errors')

            {{-- ① type --}}
            <div>
                <div class="font-semibold text-sm mb-2">① ປະເພດການຍືມ</div>
                <div class="space-y-1 text-sm">
                    <label class="flex gap-2"><input type="radio" wire:model.live="borrow_type" value="new_inventory"> ຢືມເຄື່ອງສາງ (ມີໃນ inventory)</label>
                    <label class="flex gap-2"><input type="radio" wire:model.live="borrow_type" value="tools_equipment"> ເຄື່ອງມື/ອຸປະກອນ (ຈາກ ທະບຽນ ເຄື່ອງ)</label>
                    <label class="flex gap-2"><input type="radio" wire:model.live="borrow_type" value="others"> ອື່ນໆ</label>
                </div>
                @if ($borrow_type === 'others')
                    <input type="text" wire:model="others_detail" placeholder="ລາຍລະອຽດ (ບັງຄັບ)" class="mt-2 w-full rounded-md border-gray-300 text-sm" />
                    @error('others_detail')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                @endif
            </div>

            {{-- ② ລາຍการเครื่อง (ໃສ່ ກ່ອນ ຈຸດປະສົງ) --}}
            <div>
                <div class="font-semibold text-sm mb-2">② ລາຍການເຄື່ອງ</div>
                @if ($borrow_type === 'new_inventory')
                    <div class="relative">
                        <input type="text" wire:model.live.debounce.300ms="itemSearch" placeholder="ຄົ້ນຫາ inventory (ຊື່/Material No.)…" class="w-full rounded-md border-gray-300 text-sm" />
                        @if ($invResults->isNotEmpty())
                            <div class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto">
                                @foreach ($invResults as $inv)
                                    <button type="button" wire:click="addInventoryItem({{ $inv->id }})" class="block w-full text-left px-3 py-2 text-sm hover:bg-gray-50">{{ $inv->name }} <span class="font-mono text-xs text-gray-400">{{ $inv->slug }}</span></button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @elseif ($borrow_type === 'tools_equipment')
                    <div class="relative">
                        <input type="text" wire:model.live.debounce.300ms="equipmentSearch" placeholder="ຄົ້ນຫາ ທະບຽນ ເຄື່ອງ (ຊື່/ລະຫັດ/ຊັບສິນ)…" class="w-full rounded-md border-gray-300 text-sm" />
                        @if ($eqResults->isNotEmpty())
                            <div class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto">
                                @foreach ($eqResults as $eq)
                                    <button type="button" wire:click="addEquipmentItem({{ $eq->id }})" class="block w-full text-left px-3 py-2 text-sm hover:bg-gray-50">{{ $eq->name }} <span class="font-mono text-xs text-gray-400">{{ $eq->asset_code }}@if ($eq->fixed_asset_no) · {{ $eq->fixed_asset_no }}@endif</span></button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @else
                    <button type="button" wire:click="addFreeItem" class="text-sm text-sky-600">+ ເພີ່ມແຖວ</button>
                @endif

                <div class="mt-2 border border-gray-200 rounded-md divide-y text-sm">
                    @forelse ($items as $i => $it)
                        <div wire:key="it-{{ $i }}" class="flex items-center gap-2 p-2">
                            @if (! empty($it['code']))<span class="font-mono text-xs text-gray-400 w-20 shrink-0 truncate">{{ $it['code'] }}</span>@endif
                            @if ($borrow_type === 'new_inventory' || $borrow_type === 'tools_equipment')
                                <span class="flex-1 truncate">{{ $it['item_name'] }}</span>
                            @else
                                <input type="text" wire:model="items.{{ $i }}.item_name" placeholder="ຊື່ເຄື່ອງ" class="flex-1 rounded-md border-gray-300 text-sm" />
                            @endif
                            <input type="number" min="1" wire:model="items.{{ $i }}.qty" class="w-14 rounded-md border-gray-300 text-sm" />
                            {{-- ຮູບ ຕໍ່ ລາຍການ (ບໍ່ ບັງຄັບ) — ກ້ອງ/ແກເລີຣີ --}}
                            <label class="shrink-0 cursor-pointer" title="ຖ່າຍ/ໃສ່ ຮູບ (ບໍ່ ບັງຄັບ)">
                                @if (! empty($itemPhotos[$i]))
                                    <img src="{{ $itemPhotos[$i]->temporaryUrl() }}" class="w-8 h-8 rounded object-cover border border-sky-200" alt="ຮູບ" />
                                @else
                                    <span class="inline-flex w-8 h-8 items-center justify-center rounded border border-gray-200 text-gray-400 hover:text-sky-600 hover:border-sky-300">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" /></svg>
                                    </span>
                                @endif
                                <input type="file" wire:model="itemPhotos.{{ $i }}" accept="image/*" class="hidden" />
                            </label>
                            <button type="button" wire:click="removeItem({{ $i }})" class="text-red-500 px-1">×</button>
                        </div>
                    @empty
                        <div class="p-3 text-center text-gray-400 text-xs">ຍັງບໍ່ມີລາຍການ</div>
                    @endforelse
                </div>
                <div wire:loading wire:target="itemPhotos" class="text-xs text-gray-400 mt-1">ກຳລັງ ອັບໂຫຼດ ຮູບ…</div>
                @error('items')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                @error('items.*.item_name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                @error('itemPhotos.*')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- ຈຸດປະສົງ + ໄລຍະ + ໝາຍເຫດ --}}
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="col-span-2"><label class="block text-gray-600 mb-1">ຈຸດປະສົງ *</label><input type="text" wire:model="purpose" class="w-full rounded-md border-gray-300" />@error('purpose')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror</div>
                <div><label class="block text-gray-600 mb-1">ວັນທີຢືມ *</label><input type="date" wire:model.live="borrow_date" class="w-full rounded-md border-gray-300" /></div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="block text-gray-600 mb-1">ໄລຍະ (ມື້) *</label><input type="number" min="1" max="365" wire:model.live="period_days" class="w-full rounded-md border-gray-300" /></div>
                    <div><label class="block text-gray-600 mb-1">ສົ່ງຄືນ</label><input disabled value="{{ $returnDate }}" class="w-full rounded-md border-gray-200 bg-gray-50" /></div>
                </div>
                <div class="col-span-2"><label class="block text-gray-600 mb-1">ໝາຍເຫດ</label><input type="text" wire:model="remark" class="w-full rounded-md border-gray-300" /></div>
            </div>

            {{-- ④ approval --}}
            <div>
                <div class="font-semibold text-sm mb-2">③ ສາຍອະນຸມັດ</div>
                <label class="flex gap-2 text-sm mb-2"><input type="checkbox" wire:model.live="requires_acknowledge"> ຕ້ອງ acknowledge ໂດຍ Line Manager?</label>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    @if ($requires_acknowledge)
                        <div><label class="block text-gray-600 mb-1">Line Manager *</label><select wire:model="acknowledge_user_id" class="w-full rounded-md border-gray-300"><option value="">—</option>@foreach ($users as $u)<option value="{{ $u->id }}">{{ $u->display_name ?? $u->email }}</option>@endforeach</select>@error('acknowledge_user_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror</div>
                    @endif
                    <div><label class="block text-gray-600 mb-1">Approver *</label><select wire:model="approver_user_id" class="w-full rounded-md border-gray-300"><option value="">—</option>@foreach ($users as $u)<option value="{{ $u->id }}">{{ $u->display_name ?? $u->email }}</option>@endforeach</select>@error('approver_user_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror</div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t">
                <a href="{{ route('borrow') }}" wire:navigate class="text-sm border border-gray-300 rounded-md px-4 py-2 min-h-[40px] hover:bg-gray-50 flex items-center">ຍົກເລີກ</a>
                <button wire:click="save(false)" class="text-sm border border-gray-300 rounded-md px-4 py-2 min-h-[40px] hover:bg-gray-50">💾 ບັນທຶກ draft</button>
                <button wire:click="save(true)" class="text-sm text-white bg-sky-600 rounded-md px-4 py-2 min-h-[40px] hover:bg-sky-700">ສົ່ງຂໍອະນຸມັດ →</button>
            </div>
        </div>
    </div>
</div>
