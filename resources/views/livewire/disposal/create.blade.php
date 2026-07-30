<div class="pb-10">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        <x-page-subheader :back="route('disposal')" back-label="ລາຍການ ຈຳໜ່າຍ">
            <x-slot:hint>ຕ້ອງ ຜ່ານ ການ ເຊັນ ຮັບຮອງ 5 ຝ່າຍ</x-slot>
        </x-page-subheader>

        @include('partials._form-errors')

        <div class="bg-white border border-gray-100 rounded-lg p-5 grid grid-cols-1 sm:grid-cols-4 gap-3 text-sm">
            <div class="sm:col-span-2"><label class="block text-gray-600 mb-1">ຫົວຂໍ້ / ຊຸດ</label><input type="text" wire:model="title" placeholder="ເຊັ່ນ: ຈຳໜ່າຍ ເຄື່ອງມື ຊຳລຸດ ໄຕມາດ 3" class="w-full rounded-md border-gray-300 text-sm" /></div>
            <div><label class="block text-gray-600 mb-1">ພະແນກ</label><select wire:model="department_id" class="w-full rounded-md border-gray-300 text-sm"><option value="">—</option>@foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
            <div><label class="block text-gray-600 mb-1">ໝາຍເຫດ</label><input type="text" wire:model="note" class="w-full rounded-md border-gray-300 text-sm" /></div>
        </div>

        <div class="space-y-3">
            <div class="text-sm font-medium text-gray-600">ລາຍການ ຈຳໜ່າຍ ({{ count($items) }})</div>

            @foreach ($items as $i => $row)
                @php $src = $row['source_type'] ?? 'equipment'; @endphp
                <div wire:key="di-{{ $i }}" class="bg-white border border-gray-200 rounded-lg p-4 space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium">#{{ $i + 1 }}</span>
                        <div class="inline-flex rounded-md border border-gray-300 overflow-hidden text-xs ml-auto">
                            @foreach (['inventory' => 'Inventory', 'equipment' => 'Equipment', 'deposit' => 'Deposit', 'new' => '+ ໃໝ່'] as $sv => $sl)
                                <button type="button" wire:click="$set('items.{{ $i }}.source_type', '{{ $sv }}')" class="px-2.5 py-1 {{ $src === $sv ? 'bg-sky-600 text-white' : 'text-gray-600 hover:bg-gray-50' }} {{ ! $loop->first ? 'border-l border-gray-300' : '' }}">{{ $sl }}</button>
                            @endforeach
                        </div>
                        @if (count($items) > 1)<button type="button" wire:click="removeItem({{ $i }})" class="text-gray-400 hover:text-red-600 p-1" title="ລຶບ">✕</button>@endif
                    </div>

                    @unless ($src === 'new')
                        <div class="relative">
                            <label class="block text-xs text-gray-500 mb-1">🔎 ຄົ້ນ ຈາກ ທະບຽນ {{ ['inventory' => 'Inventory', 'equipment' => 'Equipment & Tools', 'deposit' => 'ເຄື່ອງ ຝາກ'][$src] ?? '' }}</label>
                            <input type="text" wire:model.live.debounce.350ms="items.{{ $i }}.asset_code" placeholder="ພິມ ລະຫັດ/ຊື່ ເພື່ອ ຄົ້ນ…" autocomplete="off" class="w-full rounded-md border-gray-300 text-sm" />
                            @if (! empty($assetMatches[$i]))
                                <ul class="absolute z-30 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-52 overflow-auto text-xs">
                                    @foreach ($assetMatches[$i] as $m)
                                        <li><button type="button" wire:click="pickAsset({{ $i }}, '{{ $m['source'] }}', {{ $m['id'] }})" class="w-full text-left px-2.5 py-1.5 hover:bg-sky-50 border-b border-gray-50 last:border-0">
                                            <span class="inline-block text-[9px] rounded px-1 mr-0.5 {{ $m['source'] === 'equipment' ? 'bg-amber-50 text-amber-700' : ($m['source'] === 'inventory' ? 'bg-emerald-50 text-emerald-700' : 'bg-sky-50 text-sky-700') }}">{{ ['equipment' => 'EQ', 'inventory' => 'INV', 'deposit' => 'DEP'][$m['source']] }}</span>
                                            <span class="font-mono text-sky-700">{{ $m['code'] }}</span> · {{ $m['name'] }}@if ($m['fixed'])<span class="text-gray-400"> · {{ $m['fixed'] }}</span>@endif
                                        </button></li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endunless

                    <div class="grid grid-cols-2 sm:grid-cols-6 gap-2 text-sm">
                        <div class="col-span-2"><label class="block text-xs text-gray-500 mb-1">ຊື່ເຄື່ອງ *</label><input type="text" wire:model="items.{{ $i }}.item_name" class="w-full rounded-md border-gray-300 text-sm" />@error("items.$i.item_name")<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div><label class="block text-xs text-gray-500 mb-1">ລະຫັດເຄື່ອງ</label><input type="text" wire:model="items.{{ $i }}.asset_code" class="w-full rounded-md border-gray-300 text-sm font-mono" /></div>
                        <div><label class="block text-xs text-gray-500 mb-1">ຊັບສິນ</label><input type="text" wire:model="items.{{ $i }}.fixed_asset_no" class="w-full rounded-md border-gray-300 text-sm font-mono" /></div>
                        <div><label class="block text-xs text-gray-500 mb-1">ຈຳນວນ *</label><input type="number" min="1" wire:model="items.{{ $i }}.qty" class="w-full rounded-md border-gray-300 text-sm" /></div>
                        <div><label class="block text-xs text-gray-500 mb-1">ໜ່ວຍ</label><input type="text" wire:model="items.{{ $i }}.unit" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    </div>

                    <div><label class="block text-xs text-gray-500 mb-1">ສະພາບ ປັດຈຸບັນ</label><input type="text" wire:model="items.{{ $i }}.condition" placeholder="ເຊັ່ນ: ມໍເຕີ ໄໝ້ · ໃຊ້ ບໍ່ ໄດ້" class="w-full rounded-md border-gray-300 text-sm" /></div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-red-600 mb-1">ເຫດຜົນ ຈຳໜ່າຍ</label>
                            <select wire:model="items.{{ $i }}.reason" class="w-full rounded-md border-gray-300 text-sm"><option value="">— ເລືອກ —</option>@foreach ($reasons as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach</select>
                            <input type="text" wire:model="items.{{ $i }}.reason_detail" placeholder="ລາຍລະອຽດ ເພີ່ມ (optional)" class="w-full rounded-md border-gray-300 text-xs mt-1" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">ຄຳແນະນຳ</label>
                            <select wire:model="items.{{ $i }}.recommendation" class="w-full rounded-md border-gray-300 text-sm"><option value="">— ເລືອກ —</option>@foreach ($recommendations as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach</select>
                            <input type="text" wire:model="items.{{ $i }}.recommendation_detail" placeholder="ລາຍລະອຽດ ເພີ່ມ (optional)" class="w-full rounded-md border-gray-300 text-xs mt-1" />
                        </div>
                    </div>

                    @if (! empty($row['history']))
                        <div class="border border-sky-200 rounded-md">
                            <div class="flex items-center gap-2 px-3 py-1.5 border-b border-sky-100 text-xs bg-sky-50/50">
                                <span class="font-medium text-gray-700">🕘 ປະຫວັດ ບັນຫາ &amp; ສ້ອມ/ປ່ຽນ ຂອງ ເຄື່ອງ ນີ້</span>
                                <span class="text-gray-400 ml-auto">ດຶງ ອັດຕະໂນມັດ · {{ count($row['history']) }} ຄັ້ງ</span>
                            </div>
                            @foreach ($row['history'] as $j => $h)
                                @php $kc = ['maintenance' => ['ບຳລຸງ', 'bg-sky-50 text-sky-700'], 'inspection' => ['ກວດ ບໍ່ຜ່ານ', 'bg-amber-50 text-amber-700'], 'repair' => ['ສ້ອມ CM', 'bg-red-50 text-red-700']][$h['kind'] ?? 'maintenance'] ?? ['—', 'bg-gray-50 text-gray-600']; @endphp
                                <label class="flex items-start gap-2 px-3 py-1.5 border-b border-gray-50 last:border-0 text-xs cursor-pointer">
                                    <input type="checkbox" wire:model="items.{{ $i }}.history.{{ $j }}.include" class="mt-0.5 rounded border-gray-300 text-sky-600" />
                                    <span class="flex-1">
                                        <span class="text-gray-500 font-mono">{{ $h['date'] ?? '—' }}</span>
                                        <span class="rounded px-1.5 py-0.5 mx-1 {{ $kc[1] }}">{{ $kc[0] }}</span>
                                        {{ $h['problem'] ?? '' }} <span class="text-gray-400">→</span> <span class="text-gray-700">{{ $h['action'] ?? '' }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <p class="text-[11px] text-gray-400">ⓘ ຂໍ້ ທີ່ ຕິກ ຈະ ຂຶ້ນ ໃນ ໃບ Disposal + PDF ເປັນ ຫຼັກຖານ ປະກອບ</p>
                    @endif

                    <div>
                        <label class="block text-xs text-gray-500 mb-1">ຮູບ ຫຼັກຖານ</label>
                        <input type="file" wire:model="photos.{{ $i }}" multiple accept="image/*" capture="environment" class="block w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-2 file:rounded file:border-0 file:bg-sky-50 file:text-sky-700" />
                        <div wire:loading wire:target="photos.{{ $i }}" class="text-xs text-gray-400 mt-1">ກຳລັງ ອັບ…</div>
                        @if (! empty($photos[$i]))<div class="flex gap-1 flex-wrap mt-1">@foreach ($photos[$i] as $f)@if ($f->isPreviewable())<img src="{{ $f->temporaryUrl() }}" class="w-12 h-12 rounded object-cover border border-sky-200" />@endif @endforeach</div>@endif
                    </div>
                </div>
            @endforeach

            <button type="button" wire:click="addItem" class="text-sm text-sky-700 border border-sky-200 rounded-md px-3 py-1.5 hover:bg-sky-50">+ ເພີ່ມ ລາຍການ</button>
        </div>

        <div class="bg-white border border-gray-100 rounded-lg px-5 py-3 flex items-center justify-between gap-2 sticky bottom-4 shadow-lg">
            <span class="text-xs text-gray-400">ຈຳໜ່າຍ ຕ້ອງ ຜ່ານ ການ ເຊັນ ຮັບຮອງ 5 ຝ່າຍ</span>
            <div class="flex gap-2">
                <button wire:click="save(false)" wire:loading.attr="disabled" wire:target="save,photos" class="text-sm text-gray-700 border border-gray-300 rounded-md px-4 py-2 hover:bg-gray-50 disabled:opacity-50">ບັນທຶກ ຮ່າງ</button>
                <button wire:click="save(true)" wire:loading.attr="disabled" wire:target="save,photos" class="text-sm text-white bg-indigo-600 rounded-md px-4 py-2 hover:bg-indigo-700 disabled:opacity-50">ສົ່ງ ຂໍ ອະນຸມັດ</button>
            </div>
        </div>
    </div>
</div>
