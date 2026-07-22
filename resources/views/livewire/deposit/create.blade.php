@php $fileCls = 'block w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-2 file:rounded file:border-0 file:bg-sky-50 file:text-sky-700'; @endphp

<div class="pb-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        <div class="flex items-center justify-between">
            <a href="{{ route('deposit') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">← ກັບໄປ list</a>
        </div>

        @include('partials._form-errors')

        @error('action')<div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">{{ $message }}</div>@enderror
        @error('photos')<div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">{{ $message }}</div>@enderror

        {{-- request type --}}
        <div class="bg-white border border-gray-100 rounded-lg p-4 space-y-3">
            <div class="text-sm font-medium text-gray-700">ປະເພດການຝາກ</div>
            <div class="flex flex-wrap gap-3 text-sm">
                <label class="flex items-center gap-2"><input type="radio" wire:model="request_type" value="walk_in" /> Walk-in (ນຳມາແລ້ວ)</label>
                <label class="flex items-center gap-2"><input type="radio" wire:model="request_type" value="pre_request" /> Pre-request (ສົ່ງລ່ວງໜ້າ)</label>
            </div>
        </div>

        {{-- general info --}}
        <div class="bg-white border border-gray-100 rounded-lg p-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            <div>
                <label class="block text-gray-600 mb-1">ປະເພດເຄື່ອງ (Category) <span class="text-red-500">*</span></label>
                <input type="text" wire:model="item_category" class="w-full rounded-md border-gray-300 text-sm" />
                @error('item_category')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-gray-600 mb-1">ແຫຼ່ງທີ່ມາ (Origin/Source) <span class="text-red-500">*</span></label>
                <input type="text" wire:model="origin_source" class="w-full rounded-md border-gray-300 text-sm" />
                @error('origin_source')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-gray-600 mb-1">ໄລຍະເວລາທີ່ຄາດ (Duration) <span class="text-red-500">*</span></label>
                <input type="text" wire:model="expected_duration" placeholder="ເຊັ່ນ: 7 ມື້, 1 ເດືອນ" class="w-full rounded-md border-gray-300 text-sm" />
                @error('expected_duration')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-gray-600 mb-1">ວັນທີຝາກ (Deposit date) <span class="text-red-500">*</span></label>
                <input type="date" wire:model="deposit_date" class="w-full rounded-md border-gray-300 text-sm" />
                @error('deposit_date')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            @if ($request_type === 'pre_request')
                <div>
                    <label class="block text-gray-600 mb-1">ຄາດຈະນຳມາ (Expected arrival)</label>
                    <input type="date" wire:model="expected_arrival" class="w-full rounded-md border-gray-300 text-sm" />
                </div>
            @endif
            <div>
                <label class="block text-gray-600 mb-1">ຄາດຈະมาเอົาคืน (Expected claim)</label>
                <input type="date" wire:model="expected_claim_date" class="w-full rounded-md border-gray-300 text-sm" />
            </div>
            <div class="md:col-span-2">
                <label class="block text-gray-600 mb-1">ເຫດผົนการฝาก (Reason) <span class="text-red-500">*</span></label>
                <textarea wire:model="deposit_reason" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                @error('deposit_reason')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="block text-gray-600 mb-1">ໝາຍເຫດ (Remark, optional)</label>
                <textarea wire:model="remark" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea>
            </div>
        </div>

        {{-- items --}}
        <div class="bg-white border border-gray-100 rounded-lg p-4 space-y-3">
            <div class="flex items-center justify-between">
                <div class="text-sm font-medium text-gray-700">ລາຍການເຄື່ອງ ({{ count($items) }})</div>
                <button wire:click="addItem" type="button" class="text-sm text-sky-700 border border-sky-200 bg-sky-50 rounded-md px-3 py-1.5 hover:bg-sky-100">+ ເພີ່ມລາຍການ</button>
            </div>
            @error('items')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

            @foreach ($items as $i => $row)
                <div wire:key="item-{{ $i }}" class="border border-gray-200 rounded-md p-3 space-y-2">
                    <div class="flex items-start gap-2">
                        <div class="flex-1 grid grid-cols-1 sm:grid-cols-6 gap-2">
                            <div class="sm:col-span-3">
                                <label class="block text-xs text-gray-500 mb-1">ຊື່ເຄື່ອງ <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="items.{{ $i }}.item_name" class="w-full rounded-md border-gray-300 text-sm" />
                                @error("items.$i.item_name")<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">ຈຳນວນ <span class="text-red-500">*</span></label>
                                <input type="number" min="1" wire:model="items.{{ $i }}.qty" class="w-full rounded-md border-gray-300 text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">ໜ່ວຍ</label>
                                <select wire:model="items.{{ $i }}.unit" class="w-full rounded-md border-gray-300 text-sm">
                                    <option value="">—</option>
                                    @foreach ($uoms as $u)<option value="{{ $u->name }}">{{ $u->name }}</option>@endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">ມູນຄ່າ (ປະມาณ)</label>
                                <input type="number" step="0.01" min="0" wire:model="items.{{ $i }}.estimated_value" class="w-full rounded-md border-gray-300 text-sm" />
                            </div>
                            <div class="sm:col-span-3">
                                <label class="block text-xs text-gray-500 mb-1">ລາຍລະອຽດ (Description)</label>
                                <input type="text" wire:model="items.{{ $i }}.description" class="w-full rounded-md border-gray-300 text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">ສະກຸນເງິນ</label>
                                <select wire:model="items.{{ $i }}.currency" class="w-full rounded-md border-gray-300 text-sm">
                                    <option value="">—</option><option value="LAK">LAK</option><option value="THB">THB</option><option value="USD">USD</option>
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs text-gray-500 mb-1">ສະພາບຕอนฝาก</label>
                                <input type="text" wire:model="items.{{ $i }}.condition_on_deposit" class="w-full rounded-md border-gray-300 text-sm" />
                            </div>
                        </div>
                        @if (count($items) > 1)<button wire:click="removeItem({{ $i }})" type="button" class="text-gray-400 hover:text-red-600 p-1 mt-5" title="ລຶບ">✕</button>@endif
                    </div>
                    {{-- photos --}}
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">ຮູບເຄື່ອງ (deposit) — ບັງຄັບ ≥1 ກ່ອນສົ່ງ</label>
                        <input type="file" wire:model="photos.{{ $i }}" multiple accept="image/*" capture="environment" class="{{ $fileCls }}" />
                        <div wire:loading wire:target="photos.{{ $i }}" class="text-xs text-gray-400">ກຳລັງອັບ…</div>
                        @if (! empty($photos[$i]))<div class="flex gap-1 flex-wrap mt-1">@foreach ($photos[$i] as $f)@if ($f->isPreviewable())<img src="{{ $f->temporaryUrl() }}" alt="" class="w-12 h-12 rounded object-cover border border-sky-200" />@endif @endforeach</div>@endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex justify-end gap-2">
            <button wire:click="save(false)" wire:loading.attr="disabled" wire:target="save,photos" class="text-sm text-gray-700 border border-gray-300 rounded-md px-4 py-2 min-h-[40px] hover:bg-gray-50 disabled:opacity-50">ບັນທຶກເປັນຮ່າງ</button>
            <button wire:click="save(true)" wire:loading.attr="disabled" wire:target="save,photos" class="text-sm text-white bg-sky-600 rounded-md px-4 py-2 min-h-[40px] hover:bg-sky-700 disabled:opacity-50">ສ້າງ + ສົ່ງ</button>
        </div>
    </div>
</div>
