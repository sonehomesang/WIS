<div class="pb-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        <a href="{{ route('request') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">← ກັບໄປ list</a>
        @error('action')<div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">{{ $message }}</div>@enderror

        @include('partials._form-errors')

        {{-- meta --}}
        <div class="bg-white border border-gray-100 rounded-lg p-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            <div class="md:col-span-2">
                <label class="block text-gray-600 mb-1">ຈຸດປະສົງ (Purpose) <span class="text-red-500">*</span></label>
                <textarea wire:model="purpose" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                @error('purpose')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            @if ($fields['request_type'])
            <div>
                <label class="block text-gray-600 mb-1">ປະເພດ (Type)</label>
                <select wire:model="request_type" class="w-full rounded-md border-gray-300 text-sm">
                    <option value="">—</option><option value="CM">CM</option><option value="eForm">eForm</option><option value="project">Project</option>
                </select>
            </div>
            @endif
            @if ($fields['wo_e_form'])
            <div>
                <label class="block text-gray-600 mb-1">WO / eForm / Project</label>
                <input type="text" wire:model="wo_e_form" class="w-full rounded-md border-gray-300 text-sm" />
            </div>
            @endif
            @if ($fields['supplier'])
            <div>
                <label class="block text-gray-600 mb-1">Supplier</label>
                <select wire:model.live="assigned_supplier_id" class="w-full rounded-md border-gray-300 text-sm">
                    <option value="">—</option>
                    @foreach ($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                </select>
            </div>
            @endif
            @if ($fields['currency'])
            <div>
                <label class="block text-gray-600 mb-1">ສະກຸນເງິນ</label>
                <select wire:model="currency" class="w-full rounded-md border-gray-300 text-sm">
                    <option value="THB">THB (ບາດ)</option><option value="LAK">LAK (ກີບ)</option><option value="USD">USD</option>
                </select>
            </div>
            @endif
            @if ($fields['rooms'])
            <div>
                <label class="block text-gray-600 mb-1">ຫ້ອງ (Rooms)</label>
                <input type="text" wire:model="rooms" class="w-full rounded-md border-gray-300 text-sm" />
            </div>
            @endif
            @if ($fields['functions'])
            <div>
                <label class="block text-gray-600 mb-1">Functions</label>
                <input type="text" wire:model="functions" class="w-full rounded-md border-gray-300 text-sm" />
            </div>
            @endif
            @if ($fields['approver'])
            <div>
                <label class="block text-gray-600 mb-1">Approver <span class="text-red-500">*</span> <span class="text-xs text-gray-400">(ບັງຄັບຕອນສົ່ງ)</span></label>
                <select wire:model="approver_user_id" class="w-full rounded-md border-gray-300 text-sm">
                    <option value="">— ເລືອກ —</option>
                    @foreach ($users as $u)<option value="{{ $u->id }}">{{ $u->display_name ?? $u->email }}</option>@endforeach
                </select>
                @error('approver_user_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            @endif
            <div class="md:col-span-2">
                <label class="block text-gray-600 mb-1">ໝາຍເຫດ</label>
                <textarea wire:model="remark" rows="1" class="w-full rounded-md border-gray-300 text-sm"></textarea>
            </div>
        </div>

        {{-- items --}}
        <div class="bg-white border border-gray-100 rounded-lg p-4 space-y-3">
            <div class="flex items-center justify-between">
                <div class="text-sm font-medium text-gray-700">ລາຍການ ({{ count($items) }})</div>
                <button wire:click="addFreeItem" type="button" class="text-sm text-sky-700 border border-sky-200 bg-sky-50 rounded-md px-3 py-1.5 hover:bg-sky-100">+ ລາຍການ free-text</button>
            </div>
            @error('items')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

            {{-- material search --}}
            <div class="relative">
                <input type="text" wire:model.live.debounce.300ms="itemSearch" placeholder="ຄົ້ນຫາສິນຄ້າ catalog (ພິມ ≥2 ໂຕ)…" class="w-full rounded-md border-gray-300 text-sm" />
                @if ($matResults->count())
                    <div class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                        @foreach ($matResults as $m)
                            <button wire:click="addMaterial({{ $m->id }})" type="button" class="block w-full text-left px-3 py-2 text-sm hover:bg-sky-50 border-b border-gray-100">
                                <span class="font-mono text-xs text-gray-400">{{ $m->material_nbr ?? '—' }}</span> {{ Str::limit($m->description, 60) }}
                                <span class="text-xs text-gray-500">· {{ number_format($m->unit_price, 2) }} {{ $m->currency }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            @if (count($items))
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs text-gray-500 border-b">
                            <tr><th class="text-left py-1">ລາຍລະອຽດ</th><th class="py-1 w-20">ໜ່ວຍ</th><th class="py-1 w-20">ຈຳນວນ</th><th class="py-1 w-28">ລາຄາ</th><th class="py-1 w-28 text-right">ລວມ</th><th class="w-8"></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $i => $row)
                                <tr wire:key="ri-{{ $i }}" class="border-b border-gray-100">
                                    <td class="py-1.5 pr-2">
                                        <input type="text" wire:model="items.{{ $i }}.description" class="w-full rounded-md border-gray-300 text-sm" />
                                        @error("items.$i.description")<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                    </td>
                                    <td class="py-1.5 pr-1"><input type="text" wire:model="items.{{ $i }}.unit" class="w-full rounded-md border-gray-300 text-sm" /></td>
                                    <td class="py-1.5 pr-1"><input type="number" min="1" wire:model.live="items.{{ $i }}.quantity" class="w-full rounded-md border-gray-300 text-sm" /></td>
                                    <td class="py-1.5 pr-1"><input type="number" step="0.01" min="0" wire:model.live="items.{{ $i }}.unit_price" class="w-full rounded-md border-gray-300 text-sm" /></td>
                                    <td class="py-1.5 text-right text-gray-700">{{ number_format((float) ($row['unit_price'] ?? 0) * (int) ($row['quantity'] ?? 0), 2) }}</td>
                                    <td class="py-1.5 text-center"><button wire:click="removeItem({{ $i }})" type="button" class="text-gray-400 hover:text-red-600">✕</button></td>
                                </tr>
                                @if (! empty($comparisons[$i]))
                                    <tr wire:key="cmp-{{ $i }}"><td colspan="6" class="pb-2">
                                        <div class="text-xs bg-amber-50 border border-amber-200 rounded px-2 py-1.5">
                                            <span class="text-amber-700 font-medium">ປຽບທຽບ supplier ອື່ນ:</span>
                                            @foreach ($comparisons[$i] as $o)
                                                <button wire:click="useOffer({{ $i }}, {{ $o['material_id'] }})" type="button" class="inline-flex items-center gap-1 ml-1 rounded border border-amber-300 bg-white px-2 py-0.5 hover:bg-amber-100">
                                                    {{ $o['supplier_name'] }}: <span class="font-medium">{{ number_format($o['unit_price'], 2) }}</span> {{ $o['currency'] }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </td></tr>
                                @endif
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr><td colspan="4" class="text-right py-2 text-gray-500">ລວມ (net)</td><td class="text-right py-2 font-semibold text-gray-800">{{ number_format($liveTotal, 2) }} {{ $currency }}</td><td></td></tr>
                        </tfoot>
                    </table>
                    <p class="text-xs text-gray-400 mt-1">* VAT ຈະຄຳນວນ+freeze ຕອນສົ່ງ (ຕາມ supplier/global)</p>
                </div>
            @else
                <p class="text-sm text-gray-400">ຍັງບໍ່ມີລາຍການ — ຄົ້ນຫາ catalog ຫຼື ກົດ + free-text</p>
            @endif
        </div>

        <div class="flex justify-end gap-2">
            <button wire:click="save(false)" wire:loading.attr="disabled" class="text-sm text-gray-700 border border-gray-300 rounded-md px-4 py-2 min-h-[40px] hover:bg-gray-50 disabled:opacity-50">ບັນທຶກ draft</button>
            <button wire:click="save(true)" wire:loading.attr="disabled" class="text-sm text-white bg-sky-600 rounded-md px-4 py-2 min-h-[40px] hover:bg-sky-700 disabled:opacity-50">ສ້າງ + ສົ່ງ</button>
        </div>
    </div>
</div>
