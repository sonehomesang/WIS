@php $fileCls = 'block w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-2 file:rounded file:border-0 file:bg-sky-50 file:text-sky-700'; @endphp

<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        <x-page-subheader :back="route('oga')" back-label="ລາຍການ OGA" />
        @error('action')<div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">{{ $message }}</div>@enderror

        @include('partials._form-errors')

        <div class="bg-white border border-gray-100 rounded-lg p-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            <div><label class="block text-gray-600 mb-1">ວັນທີ <span class="text-red-500">*</span></label><input type="date" wire:model="date" class="w-full rounded-md border-gray-300 text-sm" />@error('date')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="block text-gray-600 mb-1">PO number</label><input type="text" wire:model="po_number" class="w-full rounded-md border-gray-300 text-sm" /></div>
            <div>
                <label class="block text-gray-600 mb-1">ວິທີສົ່ງ (Ship via) <span class="text-red-500">*</span></label>
                <select wire:model="ship_via" class="w-full rounded-md border-gray-300 text-sm"><option value="road">Road</option><option value="air">Air</option></select>
            </div>
            <div>
                <label class="block text-gray-600 mb-1">ມາຈາກ DA (optional)</label>
                <select wire:model="source_da_id" class="w-full rounded-md border-gray-300 text-sm"><option value="">—</option>@foreach ($sourceDas as $d)<option value="{{ $d->id }}">{{ $d->da_number }} · {{ $d->supplier_name }}</option>@endforeach</select>
            </div>
            <div>
                <label class="block text-gray-600 mb-1">ປາຍທາງ (Supplier)</label>
                <select wire:model="supplier_id" class="w-full rounded-md border-gray-300 text-sm"><option value="">—</option>@foreach ($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
            </div>
            <div><label class="block text-gray-600 mb-1">ຜູ້ຕິດຕໍ່</label><input type="text" wire:model="dispatch_to_contact_person" class="w-full rounded-md border-gray-300 text-sm" /></div>
            <div class="md:col-span-2"><label class="block text-gray-600 mb-1">ທີ່ຢູ່ ປາຍທາງ</label><input type="text" wire:model="dispatch_to_address" class="w-full rounded-md border-gray-300 text-sm" /></div>
            <div><label class="block text-gray-600 mb-1">ເບີໂທ</label><input type="text" wire:model="dispatch_to_phone" class="w-full rounded-md border-gray-300 text-sm" /></div>
        </div>

        <div class="bg-white border border-gray-100 rounded-lg p-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            <div class="md:col-span-2"><label class="block text-gray-600 mb-1">ສິນຄ້າ (ສະຫຼຸບ) <span class="text-red-500">*</span></label><input type="text" wire:model="goods_consigned" placeholder="ເຊັ່ນ: 2 x VALVE, SOLENOID" class="w-full rounded-md border-gray-300 text-sm" />@error('goods_consigned')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="block text-gray-600 mb-1">ຂະໜາດ (Dimension)</label><input type="text" wire:model="dimension" class="w-full rounded-md border-gray-300 text-sm" /></div>
            <div><label class="block text-gray-600 mb-1">ນ້ຳໜັກລວມ (kg)</label><input type="number" step="0.01" min="0" wire:model="gross_weight_kg" class="w-full rounded-md border-gray-300 text-sm" /></div>
            <div class="md:col-span-2"><label class="block text-gray-600 mb-1">ເຫດຜົນການສົ່ງ</label><textarea wire:model="reason_of_despatch" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea></div>
            <div><label class="block text-gray-600 mb-1">ຄົນຂັບ (Driver)</label><input type="text" wire:model="driver_name" class="w-full rounded-md border-gray-300 text-sm" /></div>
            <div><label class="block text-gray-600 mb-1">ປ້າຍລົດ (Truck)</label><input type="text" wire:model="truck_plate_number" class="w-full rounded-md border-gray-300 text-sm" /></div>
        </div>

        {{-- packing list --}}
        <div class="bg-white border border-gray-100 rounded-lg p-4 space-y-3">
            <div class="flex items-center justify-between">
                <div class="text-sm font-medium text-gray-700">Packing list ({{ count($items) }})</div>
                <button wire:click="addItem" type="button" class="text-sm text-sky-700 border border-sky-200 bg-sky-50 rounded-md px-3 py-1.5 hover:bg-sky-100">+ ເພີ່ມ</button>
            </div>
            @error('items')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            @foreach ($items as $i => $row)
                <div wire:key="ogi-{{ $i }}" class="border border-gray-200 rounded-md p-3 grid grid-cols-1 sm:grid-cols-12 gap-2 text-sm">
                    <div class="sm:col-span-6"><label class="block text-xs text-gray-500 mb-1">ລາຍລະອຽດ <span class="text-red-500">*</span></label><input type="text" wire:model="items.{{ $i }}.description" class="w-full rounded-md border-gray-300 text-sm" />@error("items.$i.description")<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div class="sm:col-span-2"><label class="block text-xs text-gray-500 mb-1">ໜ່ວຍ</label><input type="text" wire:model="items.{{ $i }}.unit" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div class="sm:col-span-1"><label class="block text-xs text-gray-500 mb-1">ຈຳນວນ</label><input type="number" min="1" wire:model="items.{{ $i }}.qty" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div class="sm:col-span-2"><label class="block text-xs text-gray-500 mb-1">ນ້ຳໜັກ/ໜ່ວຍ</label><input type="number" step="0.01" wire:model="items.{{ $i }}.unit_weight_kg" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div class="sm:col-span-1 flex items-end justify-center">@if (count($items) > 1)<button wire:click="removeItem({{ $i }})" type="button" class="text-gray-400 hover:text-red-600 pb-2">✕</button>@endif</div>
                </div>
            @endforeach
        </div>

        {{-- dispatch photos --}}
        <div class="bg-white border border-gray-100 rounded-lg p-4 space-y-3 text-sm">
            <div class="text-sm font-medium text-gray-700">ຮູບ ຕອນສົ່ງ (dispatch)</div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div><label class="block text-xs text-gray-500 mb-1">ໂຫຼດຂຶ້ນລົດ (loaded)</label><input type="file" wire:model="photoLoaded" accept="image/*" class="{{ $fileCls }}" />@error('photoLoaded')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="block text-xs text-gray-500 mb-1">ປິດຜະນຶກ (sealed)</label><input type="file" wire:model="photoSealed" accept="image/*" class="{{ $fileCls }}" /></div>
                <div><label class="block text-xs text-gray-500 mb-1">ເອກະສານ (paper/PLI)</label><input type="file" wire:model="photoPaperPli" accept="image/*" class="{{ $fileCls }}" /></div>
            </div>
            <div wire:loading wire:target="photoLoaded,photoSealed,photoPaperPli" class="text-xs text-gray-400">ກຳລັງອັບ…</div>
            <textarea wire:model="comments" rows="2" placeholder="ໝາຍເຫດ…" class="w-full rounded-md border-gray-300 text-sm"></textarea>
        </div>

        <div class="flex justify-end gap-2">
            <button wire:click="save(false)" wire:loading.attr="disabled" wire:target="save,photoLoaded,photoSealed,photoPaperPli" class="text-sm text-gray-700 border border-gray-300 rounded-md px-4 py-2 min-h-[40px] hover:bg-gray-50 disabled:opacity-50">ບັນທຶກ draft</button>
            <button wire:click="save(true)" wire:loading.attr="disabled" wire:target="save,photoLoaded,photoSealed,photoPaperPli" class="text-sm text-white bg-sky-600 rounded-md px-4 py-2 min-h-[40px] hover:bg-sky-700 disabled:opacity-50">ສ້າງ + Dispatch</button>
        </div>
    </div>
</div>
