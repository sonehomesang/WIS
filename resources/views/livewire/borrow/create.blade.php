<div class="pb-6">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        <a href="{{ route('borrow') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">← ກັບໄປ list</a>

        <div class="bg-white rounded-lg border border-gray-100 p-5 space-y-5">
            {{-- ① type --}}
            <div>
                <div class="font-semibold text-sm mb-2">① ປະເພດການຍືມ</div>
                <div class="space-y-1 text-sm">
                    <label class="flex gap-2"><input type="radio" wire:model.live="borrow_type" value="new_inventory"> ຢືມເຄື່ອງສາງ (ມີໃນ inventory)</label>
                    <label class="flex gap-2"><input type="radio" wire:model.live="borrow_type" value="tools_equipment"> ເຄື່ອງມື/ອຸປະກອນ (ບໍ່ມີໃນລະບົບ)</label>
                    <label class="flex gap-2"><input type="radio" wire:model.live="borrow_type" value="others"> ອື່ນໆ</label>
                </div>
                @if ($borrow_type === 'others')
                    <input type="text" wire:model="others_detail" placeholder="ລາຍລະອຽດ (ບັງຄັບ)" class="mt-2 w-full rounded-md border-gray-300 text-sm" />
                    @error('others_detail')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                @endif
            </div>

            {{-- ② purpose + period --}}
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="col-span-2"><label class="block text-gray-600 mb-1">ຈຸດປະສົງ *</label><input type="text" wire:model="purpose" class="w-full rounded-md border-gray-300" />@error('purpose')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror</div>
                <div><label class="block text-gray-600 mb-1">ວັນທີຢືມ *</label><input type="date" wire:model.live="borrow_date" class="w-full rounded-md border-gray-300" /></div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="block text-gray-600 mb-1">ໄລຍະ (ມື້) *</label><input type="number" min="1" max="365" wire:model.live="period_days" class="w-full rounded-md border-gray-300" /></div>
                    <div><label class="block text-gray-600 mb-1">ສົ່ງຄືນ</label><input disabled value="{{ $returnDate }}" class="w-full rounded-md border-gray-200 bg-gray-50" /></div>
                </div>
                <div class="col-span-2"><label class="block text-gray-600 mb-1">ໝາຍເຫດ</label><input type="text" wire:model="remark" class="w-full rounded-md border-gray-300" /></div>
            </div>

            {{-- ③ items --}}
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
                @else
                    <button type="button" wire:click="addFreeItem" class="text-sm text-sky-600">+ ເພີ່ມແຖວ</button>
                @endif

                <div class="mt-2 border border-gray-200 rounded-md divide-y text-sm">
                    @forelse ($items as $i => $it)
                        <div wire:key="it-{{ $i }}" class="flex items-center gap-2 p-2">
                            @if ($borrow_type === 'new_inventory')
                                <span class="flex-1">{{ $it['item_name'] }}</span>
                            @else
                                <input type="text" wire:model="items.{{ $i }}.item_name" placeholder="ຊື່ເຄື່ອງ" class="flex-1 rounded-md border-gray-300 text-sm" />
                            @endif
                            <input type="number" min="1" wire:model="items.{{ $i }}.qty" class="w-16 rounded-md border-gray-300 text-sm" />
                            <button type="button" wire:click="removeItem({{ $i }})" class="text-red-500 px-1">×</button>
                        </div>
                    @empty
                        <div class="p-3 text-center text-gray-400 text-xs">ຍັງບໍ່ມີລາຍການ</div>
                    @endforelse
                </div>
                @error('items')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                @error('items.*.item_name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
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
                <button wire:click="save(false)" class="text-sm border border-gray-300 rounded-md px-4 py-2 min-h-[40px] hover:bg-gray-50">💾 ບັນທຶກ draft</button>
                <button wire:click="save(true)" class="text-sm text-white bg-sky-600 rounded-md px-4 py-2 min-h-[40px] hover:bg-sky-700">ສົ່ງຂໍອະນຸມັດ →</button>
            </div>
        </div>
    </div>
</div>
