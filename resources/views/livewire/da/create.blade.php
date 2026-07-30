@php
    $fileCls = 'block w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-2 file:rounded file:border-0 file:bg-sky-50 file:text-sky-700';
    $typeLabel = ['incorrect_supplied' => 'ສົ່ງຜິດ', 'oversupplied' => 'ສົ່ງເກີນ', 'undersupplied' => 'ສົ່ງຂາດ', 'damaged' => 'ເສຍຫາຍ', 'no_paperwork' => 'ບໍ່ມີເອກະສານ', 'other' => 'ອື່ນໆ'];
@endphp

<div class="pb-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        <x-page-subheader :back="route('da')" back-label="ລາຍການ DA" />
        @error('action')<div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">{{ $message }}</div>@enderror

        @include('partials._form-errors')

        <div class="bg-white border border-gray-100 rounded-lg p-4 space-y-3">
            <div class="text-sm font-medium text-gray-700">A. ຂໍ້ມູນ Warehouse</div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                <div><label class="block text-gray-600 mb-1">ວັນທີ <span class="text-red-500">*</span></label><input type="date" wire:model="date" class="w-full rounded-md border-gray-300 text-sm" />@error('date')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="block text-gray-600 mb-1">PO number <span class="text-red-500">*</span></label><input type="text" wire:model="po_number" class="w-full rounded-md border-gray-300 text-sm" />@error('po_number')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="block text-gray-600 mb-1">ວັນທີ PO</label><input type="date" wire:model="po_date" class="w-full rounded-md border-gray-300 text-sm" /></div>
                <div><label class="block text-gray-600 mb-1">Supplier</label><select wire:model="supplier_id" class="w-full rounded-md border-gray-300 text-sm"><option value="">—</option>@foreach ($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
                <div><label class="block text-gray-600 mb-1">Purchasing officer</label><input type="text" wire:model="purchasing_officer_name" class="w-full rounded-md border-gray-300 text-sm" /></div>
                <div><label class="block text-gray-600 mb-1">Vendor packing list</label><input type="text" wire:model="vendor_packing_list" class="w-full rounded-md border-gray-300 text-sm" /></div>
                <div><label class="block text-gray-600 mb-1">Vendor invoice #</label><input type="text" wire:model="vendor_invoice_number" class="w-full rounded-md border-gray-300 text-sm" /></div>
            </div>

            <div>
                <label class="block text-sm text-gray-600 mb-1">ປະເພດຄວາມຜິດ <span class="text-red-500">*</span> (ເລືອກໄດ້ຫຼາຍ)</label>
                <div class="flex flex-wrap gap-3">
                    @foreach ($typeOptions as $t)
                        <label class="flex items-center gap-1.5 text-sm"><input type="checkbox" value="{{ $t }}" wire:model="discrepancy_types" class="rounded border-gray-300 text-sky-600" /> {{ $typeLabel[$t] ?? $t }}</label>
                    @endforeach
                </div>
                @error('discrepancy_types')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- items --}}
        <div class="bg-white border border-gray-100 rounded-lg p-4 space-y-3">
            <div class="flex items-center justify-between">
                <div class="text-sm font-medium text-gray-700">ລາຍການເຄື່ອງ ({{ count($items) }})</div>
                <button wire:click="addItem" type="button" class="text-sm text-sky-700 border border-sky-200 bg-sky-50 rounded-md px-3 py-1.5 hover:bg-sky-100">+ ເພີ່ມ</button>
            </div>
            @error('items')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            @foreach ($items as $i => $row)
                <div wire:key="dai-{{ $i }}" class="border border-gray-200 rounded-md p-3 grid grid-cols-1 sm:grid-cols-12 gap-2 text-sm">
                    <div class="sm:col-span-3"><label class="block text-xs text-gray-500 mb-1">Stock code</label><input type="text" wire:model="items.{{ $i }}.stock_code" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div class="sm:col-span-5"><label class="block text-xs text-gray-500 mb-1">ລາຍລະອຽດ <span class="text-red-500">*</span></label><input type="text" wire:model="items.{{ $i }}.description" class="w-full rounded-md border-gray-300 text-sm" />@error("items.$i.description")<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div class="sm:col-span-1"><label class="block text-xs text-gray-500 mb-1">ສັ່ງ</label><input type="number" min="0" wire:model="items.{{ $i }}.qty_ordered" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div class="sm:col-span-1"><label class="block text-xs text-gray-500 mb-1">ສົ່ງ</label><input type="number" min="0" wire:model="items.{{ $i }}.qty_delivered" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div class="sm:col-span-1"><label class="block text-xs text-gray-500 mb-1">ຮັບ</label><input type="number" min="0" wire:model="items.{{ $i }}.qty_received" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div class="sm:col-span-1 flex items-end justify-center">@if (count($items) > 1)<button wire:click="removeItem({{ $i }})" type="button" class="text-gray-400 hover:text-red-600 pb-2">✕</button>@endif</div>
                </div>
            @endforeach
        </div>

        {{-- recommendation + photos --}}
        <div class="bg-white border border-gray-100 rounded-lg p-4 space-y-3 text-sm">
            <div>
                <label class="block text-gray-600 mb-1">ຄຳແນະນຳ Warehouse</label>
                <select wire:model="warehouse_recommendation_kind" class="w-full md:w-1/2 rounded-md border-gray-300 text-sm">
                    <option value="">—</option><option value="accept_goods">ຮັບເຂົ້າ stock</option><option value="return_supplier">ສົ່ງຄືນ supplier</option><option value="direct_charge">Direct charge</option><option value="other">ອື່ນໆ</option>
                </select>
                <textarea wire:model="warehouse_recommendation_text" rows="2" placeholder="ລາຍລະອຽດ…" class="w-full rounded-md border-gray-300 text-sm mt-2"></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div><label class="block text-xs text-gray-500 mb-1">ຮູບ ພາບຮວມ (overview)</label><input type="file" wire:model="photoOverview" accept="image/*" class="{{ $fileCls }}" />@error('photoOverview')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="block text-xs text-gray-500 mb-1">ຮູບ ຈຸດເສຍ (defect)</label><input type="file" wire:model="photoDefect" accept="image/*" class="{{ $fileCls }}" /></div>
                <div><label class="block text-xs text-gray-500 mb-1">ຮູບ ປຽບທຽບ (comparison)</label><input type="file" wire:model="photoComparison" accept="image/*" class="{{ $fileCls }}" /></div>
            </div>
            <div wire:loading wire:target="photoOverview,photoDefect,photoComparison" class="text-xs text-gray-400">ກຳລັງອັບ…</div>
            <textarea wire:model="comments" rows="2" placeholder="ໝາຍເຫດເພີ່ມເຕີມ…" class="w-full rounded-md border-gray-300 text-sm"></textarea>
        </div>

        <div class="flex justify-end gap-2">
            <button wire:click="save(false)" wire:loading.attr="disabled" wire:target="save,photoOverview,photoDefect,photoComparison" class="text-sm text-gray-700 border border-gray-300 rounded-md px-4 py-2 min-h-[40px] hover:bg-gray-50 disabled:opacity-50">ບັນທຶກ draft</button>
            <button wire:click="save(true)" wire:loading.attr="disabled" wire:target="save,photoOverview,photoDefect,photoComparison" class="text-sm text-white bg-sky-600 rounded-md px-4 py-2 min-h-[40px] hover:bg-sky-700 disabled:opacity-50">ສ້າງ + ສົ່ງ</button>
        </div>
    </div>
</div>
