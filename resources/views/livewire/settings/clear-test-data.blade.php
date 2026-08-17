<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @include('settings._tabs')

        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            ⚠️ <b>ເຄື່ອງມື ອັນຕະລາຍ (super admin ເທົ່ານັ້ນ)</b> — ໃຊ້ ລ້າງ ຂໍ້ມູນ ທົດສອບ ກ່ອນ Go-Live ເພື່ອ ບໍ່ ໃຫ້ ປະປົນ ກັບ ຂໍ້ມູນ ຈິງ. ຂໍ້ມູນ ທີ່ ລຶບ <b>ກູ້ ຄືນ ບໍ່ ໄດ້</b>. ຜູ້ໃຊ້ · ບົດບາດ · ການຕັ້ງຄ່າ · ໜ່ວຍງານ · templates · ສະຖານະພາບ — <b>ບໍ່ ຖືກ ລຶບ</b>.
        </div>

        @if (session('cleared') !== null)
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                @php $s = session('cleared'); @endphp
                ✓ ລ້າງ ສຳເລັດ.
                @if (empty($s))<span class="text-emerald-600">(ບໍ່ ມີ ແຖວ ໃຫ້ ລຶບ)</span>
                @else <span class="text-emerald-700">ລຶບ: {{ collect($s)->map(fn ($n, $t) => "$t=$n")->implode(' · ') }}</span>@endif
            </div>
        @endif

        <form wire:submit="clear" class="space-y-4">
            {{-- transactions --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-4 py-2.5 bg-gray-50/70 border-b border-gray-100 flex items-center gap-2.5"><span class="w-6 h-6 rounded-md bg-slate-100 text-slate-600 flex items-center justify-center text-xs">📄</span><h3 class="text-sm font-semibold text-gray-700">ຂໍ້ມູນ ທຸລະກຳ (Transactions) — ລ້າງ ໄດ້ ຢ່າງ ປອດໄພ</h3></div>
                <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach ($groups as $key => $g)
                        @unless ($g['master'])
                            <label class="flex items-center justify-between gap-2 rounded-lg border border-gray-200 px-3 py-2 cursor-pointer hover:bg-gray-50 {{ in_array($key, $selected, true) ? 'ring-1 ring-sky-300 bg-sky-50/40' : '' }}">
                                <span class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" wire:model.live="selected" value="{{ $key }}" class="rounded border-gray-300 text-sky-600" /> {{ $g['label'] }}</span>
                                <span class="text-xs font-mono tabular-nums {{ $counts[$key] ? 'text-gray-500' : 'text-gray-300' }}">{{ number_format($counts[$key]) }}</span>
                            </label>
                        @endunless
                    @endforeach
                </div>
            </div>

            {{-- master data (danger) --}}
            <div class="bg-white border border-rose-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-4 py-2.5 bg-rose-50/70 border-b border-rose-100 flex items-center gap-2.5"><span class="w-6 h-6 rounded-md bg-rose-100 text-rose-600 flex items-center justify-center text-xs">⚠️</span><h3 class="text-sm font-semibold text-rose-700">ຂໍ້ມູນ ຫຼັກ (Master) — ລ້າງ ສະເພາະ ຖ້າ ເປັນ ຂໍ້ມູນ ທົດສອບ ເທົ່ານັ້ນ</h3></div>
                <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach ($groups as $key => $g)
                        @if ($g['master'])
                            <label class="flex items-center justify-between gap-2 rounded-lg border border-rose-200 px-3 py-2 cursor-pointer hover:bg-rose-50 {{ in_array($key, $selected, true) ? 'ring-1 ring-rose-300 bg-rose-50' : '' }}">
                                <span class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" wire:model.live="selected" value="{{ $key }}" class="rounded border-rose-300 text-rose-600" /> {{ $g['label'] }}</span>
                                <span class="text-xs font-mono tabular-nums {{ $counts[$key] ? 'text-rose-500' : 'text-gray-300' }}">{{ number_format($counts[$key]) }}</span>
                            </label>
                        @endif
                    @endforeach
                </div>
            </div>

            @error('selected')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror

            {{-- confirm + action --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 flex flex-col sm:flex-row sm:items-end gap-3">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-500 mb-1">ພິມ <b class="font-mono text-rose-600">CLEAR</b> ເພື່ອ ຢືນຢັນ ({{ count($selected) }} ກຸ່ມ ຖືກ ເລືອກ)</label>
                    <input type="text" wire:model="confirm" placeholder="CLEAR" autocomplete="off" class="w-full sm:w-56 rounded-lg border-gray-300 text-sm font-mono" />
                    @error('confirm')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <button type="submit" wire:loading.attr="disabled" wire:target="clear" wire:confirm="ຢືນຢັນ ລຶບ ຂໍ້ມູນ ທີ່ ເລືອກ ຖາວອນ? ກູ້ ຄືນ ບໍ່ ໄດ້!" class="inline-flex items-center justify-center gap-1.5 text-sm font-semibold text-white bg-rose-600 rounded-lg px-4 py-2.5 hover:bg-rose-700 disabled:opacity-50 transition shadow-sm">🗑 ລ້າງ ຂໍ້ມູນ ທີ່ ເລືອກ</button>
            </div>
        </form>
    </div>
</div>
