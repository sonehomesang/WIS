<div class="pb-10">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        <x-page-subheader :back="route('request')" back-label="ລາຍການ" />

        {{-- ══ HERO ══ --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="h-1.5 bg-gradient-to-r from-amber-500 to-orange-500"></div>
            <div class="p-5 flex items-center gap-4 flex-wrap">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-orange-500 text-white flex items-center justify-center text-2xl shadow-sm shrink-0">📝</div>
                <div class="min-w-0">
                    <h2 class="text-lg font-bold text-gray-900">ສ້າງ ໃບ ເບີກ ເຄື່ອງ</h2>
                    <div class="text-sm text-gray-500">ໃສ່ ຈຸດ ປະສົງ + ຂໍ້ມູນ → ຄົ້ນ catalog / free-text → ສົ່ງ ຂໍ ອະນຸມັດ</div>
                </div>
            </div>
        </div>

        @error('action')<div class="text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-xl px-4 py-2.5">{{ $message }}</div>@enderror
        @include('partials._form-errors')

        {{-- meta --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 bg-gray-50/70 border-b border-gray-100 flex items-center gap-2.5"><span class="w-6 h-6 rounded-md bg-slate-100 text-slate-600 flex items-center justify-center text-xs">📋</span><h3 class="text-sm font-semibold text-gray-700">ຂໍ້ມູນ ໃບ ເບີກ</h3></div>
            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-500 mb-1">ຈຸດປະສົງ (Purpose) <span class="text-rose-500">*</span></label>
                    <textarea wire:model="purpose" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                    @error('purpose')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                @if ($fields['request_type'])
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">ປະເພດ (Type)</label>
                    <select wire:model="request_type" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">—</option>
                        @foreach (\App\Support\RequestType::options() as $rtKey => $rtLabel)<option value="{{ $rtKey }}">{{ $rtLabel }}</option>@endforeach
                    </select>
                </div>
                @endif
                @if ($fields['wo_e_form'])
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">WO / eForm / Project</label>
                    <input type="text" wire:model="wo_e_form" class="w-full rounded-lg border-gray-300 text-sm" />
                </div>
                @endif
                @if ($fields['supplier'])
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Supplier</label>
                    <select wire:model.live="assigned_supplier_id" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">—</option>
                        @foreach ($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                    </select>
                </div>
                @endif
                @if ($fields['currency'])
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">ສະກຸນເງິນ</label>
                    <select wire:model="currency" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="THB">THB (ບາດ)</option><option value="LAK">LAK (ກີບ)</option><option value="USD">USD</option>
                    </select>
                </div>
                @endif
                @if ($fields['rooms'])
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">ຫ້ອງ (Rooms)</label>
                    <input type="text" wire:model="rooms" class="w-full rounded-lg border-gray-300 text-sm" />
                </div>
                @endif
                @if ($fields['functions'])
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Functions</label>
                    <input type="text" wire:model="functions" class="w-full rounded-lg border-gray-300 text-sm" />
                </div>
                @endif
                @if ($fields['approver'])
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Approver <span class="text-rose-500">*</span> <span class="text-gray-400 font-normal">(ບັງຄັບຕອນສົ່ງ)</span></label>
                    <select wire:model="approver_user_id" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">— ເລືອກ —</option>
                        @foreach ($users as $u)<option value="{{ $u->id }}">{{ $u->display_name ?? $u->email }}</option>@endforeach
                    </select>
                    @error('approver_user_id')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                @endif
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-500 mb-1">ໝາຍເຫດ</label>
                    <textarea wire:model="remark" rows="1" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                </div>
            </div>
        </div>

        {{-- items --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 bg-gray-50/70 border-b border-gray-100 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2.5"><span class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center text-xs">📝</span><h3 class="text-sm font-semibold text-gray-700">ລາຍການ <span class="text-gray-400 font-normal">({{ count($items) }})</span></h3></div>
                <button wire:click="addFreeItem" type="button" class="text-sm font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-1.5 hover:bg-amber-100 transition">+ free-text</button>
            </div>
            <div class="p-4 space-y-3">
                @error('items')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror

                {{-- material search --}}
                <div class="relative">
                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔎</span>
                    <input type="text" wire:model.live.debounce.300ms="itemSearch" placeholder="ຄົ້ນຫາສິນຄ້າ catalog (ພິມ ≥2 ໂຕ)…" class="w-full pl-8 rounded-lg border-gray-300 text-sm" />
                    @if ($matResults->count())
                        <div class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-xl max-h-60 overflow-y-auto">
                            @foreach ($matResults as $m)
                                <button wire:click="addMaterial({{ $m->id }})" type="button" class="block w-full text-left px-3 py-2 text-sm hover:bg-amber-50 border-b border-gray-100 last:border-0">
                                    <span class="font-mono text-xs text-gray-400">{{ $m->material_nbr ?? '—' }}</span> {{ Str::limit($m->description, 60) }}
                                    <span class="text-xs text-gray-500">· {{ number_format($m->unit_price, 2) }} {{ $m->currency }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if (count($items))
                    <div class="overflow-x-auto border border-gray-100 rounded-lg">
                        <table class="w-full text-sm">
                            <thead class="text-[11px] uppercase tracking-wide text-slate-500 bg-slate-50 border-b border-gray-200">
                                <tr><th class="text-left py-2 px-2">ລາຍລະອຽດ</th><th class="py-2 px-1 w-20">ໜ່ວຍ</th><th class="py-2 px-1 w-20">ຈຳນວນ</th><th class="py-2 px-1 w-28">ລາຄາ</th><th class="py-2 px-2 w-28 text-right">ລວມ</th><th class="w-8"></th></tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $i => $row)
                                    <tr wire:key="ri-{{ $i }}" class="border-b border-gray-100">
                                        <td class="py-1.5 px-2 pr-2">
                                            <input type="text" wire:model="items.{{ $i }}.description" class="w-full rounded-lg border-gray-300 text-sm" />
                                            @error("items.$i.description")<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                                        </td>
                                        <td class="py-1.5 px-1"><input type="text" wire:model="items.{{ $i }}.unit" class="w-full rounded-lg border-gray-300 text-sm" /></td>
                                        <td class="py-1.5 px-1"><input type="number" min="1" wire:model.live="items.{{ $i }}.quantity" class="w-full rounded-lg border-gray-300 text-sm" /></td>
                                        <td class="py-1.5 px-1"><input type="number" step="0.01" min="0" wire:model.live="items.{{ $i }}.unit_price" class="w-full rounded-lg border-gray-300 text-sm" /></td>
                                        <td class="py-1.5 px-2 text-right text-gray-700 tabular-nums">{{ number_format((float) ($row['unit_price'] ?? 0) * (int) ($row['quantity'] ?? 0), 2) }}</td>
                                        <td class="py-1.5 text-center"><button wire:click="removeItem({{ $i }})" type="button" class="text-gray-400 hover:text-rose-600 transition">✕</button></td>
                                    </tr>
                                    @if (! empty($comparisons[$i]))
                                        <tr wire:key="cmp-{{ $i }}"><td colspan="6" class="pb-2 px-2">
                                            <div class="text-xs bg-amber-50 border border-amber-200 rounded-lg px-2.5 py-1.5">
                                                <span class="text-amber-700 font-medium">ປຽບທຽບ supplier ອື່ນ:</span>
                                                @foreach ($comparisons[$i] as $o)
                                                    <button wire:click="useOffer({{ $i }}, {{ $o['material_id'] }})" type="button" class="inline-flex items-center gap-1 ml-1 rounded-lg border border-amber-300 bg-white px-2 py-0.5 hover:bg-amber-100 transition">
                                                        {{ $o['supplier_name'] }}: <span class="font-medium">{{ number_format($o['unit_price'], 2) }}</span> {{ $o['currency'] }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        </td></tr>
                                    @endif
                                @endforeach
                            </tbody>
                            <tfoot class="bg-amber-50/60 border-t-2 border-amber-200">
                                <tr><td colspan="4" class="text-right py-2 px-2 text-gray-500 font-medium">ລວມ (net)</td><td class="text-right py-2 px-2 font-bold text-gray-800 tabular-nums">{{ number_format($liveTotal, 2) }} {{ $currency }}</td><td></td></tr>
                            </tfoot>
                        </table>
                    </div>
                    <p class="text-xs text-gray-400">* VAT ຈະຄຳນວນ+freeze ຕອນສົ່ງ (ຕາມ supplier/global)</p>
                @else
                    <p class="text-sm text-gray-400 text-center py-4">ຍັງບໍ່ມີລາຍການ — ຄົ້ນຫາ catalog ຫຼື ກົດ + free-text</p>
                @endif
            </div>
        </div>

        {{-- sticky save bar --}}
        <div class="bg-white/95 backdrop-blur border border-gray-200 rounded-xl px-5 py-3 flex items-center justify-end gap-2 sticky bottom-4 shadow-lg">
            <button wire:click="save(false)" wire:loading.attr="disabled" class="text-sm font-medium text-gray-700 border border-gray-200 rounded-lg px-4 py-2 hover:bg-gray-50 disabled:opacity-50 transition">ບັນທຶກ draft</button>
            <button wire:click="save(true)" wire:loading.attr="disabled" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-amber-600 rounded-lg px-4 py-2 hover:bg-amber-700 disabled:opacity-50 transition shadow-sm">ສ້າງ + ສົ່ງ →</button>
        </div>
    </div>
</div>
