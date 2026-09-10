<div>
@php
    $ratingColors = [1 => '#ef4444', 2 => '#f97316', 3 => '#eab308', 4 => '#84cc16', 5 => '#22c55e'];
    $sections = [
        ['n' => 2, 'badge' => 'bg-sky-100 text-sky-700',     'lo' => 'ປະເມີນການບໍລິການ ຄັງສິນຄ້າ', 'en' => 'Warehouse Service', 'q' => [
            'wh_receiving' => ['ຄວາມຖືກຕ້ອງ ໃນການຮັບສິນຄ້າ', 'Accuracy of receiving goods'],
            'wh_condition' => ['ສະພາບສິນຄ້າ ຕອນຮັບ', 'Condition of goods upon receipt'],
            'wh_storage'   => ['ຄວາມຖືກຕ້ອງ ການຈັດເກັບ/ວາງສິນຄ້າ', 'Accuracy of stock storage and placement'],
        ]],
        ['n' => 3, 'badge' => 'bg-emerald-100 text-emerald-700', 'lo' => 'ປະເມີນການບໍລິການ ນຳເຂົ້າ-ສົ່ງອອກ', 'en' => 'Import & Export Service', 'q' => [
            'ie_customs'       => ['ປະສິດທິພາບ ການເຄລຍພາສີ', 'Customs clearance efficiency'],
            'ie_communication' => ['ການສື່ສານ ສະຖານະ ນຳເຂົ້າ/ສົ່ງອອກ', 'Communication regarding import/export status'],
            'ie_urgent'        => ['ການຈັດການ ສິນຄ້າດ່ວນ', 'Handling of urgent shipments'],
        ]],
        ['n' => 4, 'badge' => 'bg-amber-100 text-amber-700',   'lo' => 'ຄວາມເພິ່ງພໍໃຈ ໂດຍລວມ', 'en' => 'Overall Satisfaction', 'q' => [
            'overall_wh' => ['ຕໍ່ບໍລິການ ຄັງສິນຄ້າ', 'Overall satisfaction with Warehouse services'],
            'overall_ie' => ['ຕໍ່ບໍລິການ ນຳເຂົ້າ-ສົ່ງອອກ', 'Overall satisfaction with Import-Export services'],
        ]],
    ];
@endphp

@if ($closed)
    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-8 text-center">
        <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 text-gray-400 grid place-items-center text-3xl">🕒</div>
        <h1 class="mt-4 text-xl font-bold text-gray-800">ແບບສອບຖາມ ຍັງບໍ່ເປີດ / ປິດແລ້ວ</h1>
        <p class="mt-1 text-sm text-gray-500">ຂະນະນີ້ ບໍ່ຢູ່ ໃນ ຊ່ວງ ເກັບ ຄຳຕອບ — ກະລຸນາ ຕິດຕໍ່ ຜູ້ດູແລ ຫຼື ລອງ ພາຍຫຼັງ.</p>
        <p class="text-xs text-gray-400 mt-1">The survey is not open right now.</p>
    </div>
@elseif ($done)
    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-8 text-center">
        <div class="w-16 h-16 mx-auto rounded-full bg-emerald-100 text-emerald-600 grid place-items-center text-3xl">✓</div>
        <h1 class="mt-4 text-xl font-bold text-gray-800">ຂອບໃຈຫຼາຍໆ!</h1>
        <p class="mt-1 text-sm text-gray-500">ຄຳຕອບຂອງທ່ານຖືກບັນທຶກແລ້ວ — ຊ່ວຍໃຫ້ພວກເຮົາປັບປຸງການບໍລິການ.</p>
        <p class="text-xs text-gray-400 mt-1">Your response has been recorded. Thank you.</p>
        @guest
            <button wire:click="$set('done', false)" class="mt-5 h-10 px-5 rounded-lg bg-white border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50">ຕອບອີກຄັ້ງ / Submit another</button>
        @endguest
    </div>
@else
    <form wire:submit="submit" class="space-y-4">
        {{-- Intro --}}
        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-sky-100 bg-gradient-to-b from-sky-100 to-sky-50">
                <h1 class="text-lg font-bold text-gray-800">ແບບສອບຖາມຄວາມເພິ່ງພໍໃຈ</h1>
                <p class="text-xs text-gray-500">Warehouse &amp; Import-Export Customer Satisfaction Survey</p>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">ຄຳຕອບຂອງທ່ານຊ່ວຍໃຫ້ພວກເຮົາປັບປຸງການບໍລິການ · ~2 ນາທີ.</p>
                @if ($isGuest)
                    <div class="grid sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">ໜ່ວຍງານ ຂອງທ່ານ · Your unit</label>
                            <select wire:model.live="unit_id" class="w-full h-10 rounded-lg border-gray-300 text-sm">
                                <option value="">— ເລືອກ / Select (ບໍ່ບັງຄັບ) —</option>
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">ພະແນກ · Department</label>
                            <select wire:model="department_id" @disabled(! $unit_id) class="w-full h-10 rounded-lg border-gray-300 text-sm disabled:bg-gray-50 disabled:text-gray-400">
                                <option value="">— {{ $unit_id ? 'ເລືອກ / Select (ບໍ່ບັງຄັບ)' : 'ເລືອກ ໜ່ວຍງານ ກ່ອນ' }} —</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1">🔒 ຕອບໄດ້ແບບບໍ່ລະບຸຕົວ (anonymous) — ໜ່ວຍງານ/ພະແນກ ໃຊ້ ຈັດ ໝວດ ຜົນ ເທົ່ານັ້ນ.</p>
                @else
                    <span class="inline-flex items-center gap-1 text-xs text-gray-500 bg-gray-50 border border-gray-200 rounded-full px-3 py-1">👤 {{ auth()->user()->display_name }} · {{ auth()->user()->unit?->name ?? 'ບໍ່ມີໜ່ວຍງານ' }}</span>
                @endif
            </div>
        </div>

        {{-- Q1 frequency --}}
        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6">
            <h2 class="font-bold text-gray-800">1. ຄວາມຖີ່ໃນການຕິດຕໍ່ກັບພະແນກ</h2>
            <p class="text-xs text-gray-500 mb-3">Frequency of Interaction with Our Department</p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                @foreach (['daily' => 'ປະຈຳວັນ / Daily', 'weekly' => 'ປະຈຳອາທິດ / Weekly', 'monthly' => 'ປະຈຳເດືອນ / Monthly', 'occasionally' => 'ບາງຄັ້ງຄາວ / Occasionally'] as $val => $lbl)
                    <button type="button" wire:click="$set('frequency', '{{ $val }}')"
                            class="flex items-center gap-2 border rounded-lg px-3 py-2 text-sm text-left transition {{ $frequency === $val ? 'border-sky-500 bg-sky-50 text-sky-700 font-semibold ring-1 ring-sky-200' : 'border-gray-200 hover:bg-gray-50 text-gray-600' }}">
                        <span class="w-4 h-4 rounded-full border {{ $frequency === $val ? 'border-sky-600 bg-sky-600' : 'border-gray-300' }} shrink-0"></span>
                        {{ $lbl }}
                    </button>
                @endforeach
            </div>
            @error('frequency')<p class="text-xs text-red-600 mt-2">{{ $message }}</p>@enderror
        </div>

        <p class="text-center text-xs text-gray-500">ຄະແນນ: <b>1</b> ຕ່ຳຫຼາຍ (Very Poor) → <b>5</b> ດີເລີດ (Excellent)</p>

        {{-- rating sections --}}
        @foreach ($sections as $sec)
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 space-y-5">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg {{ $sec['badge'] }} grid place-items-center text-xs font-bold">{{ $sec['n'] }}</span>
                    <h2 class="font-bold text-gray-800">{{ $sec['lo'] }} · {{ $sec['en'] }}</h2>
                </div>
                @foreach ($sec['q'] as $field => $labels)
                    <div>
                        <div class="text-sm text-gray-700">{{ $labels[0] }}</div>
                        <div class="text-[11px] text-gray-400 mb-1.5">{{ $labels[1] }}</div>
                        <div class="flex gap-2">
                            @for ($i = 1; $i <= 5; $i++)
                                <button type="button" wire:click="$set('{{ $field }}', {{ $i }})"
                                        class="w-10 h-10 rounded-full border grid place-items-center font-bold text-sm transition {{ $this->{$field} === $i ? 'text-white border-transparent' : 'text-gray-500 border-gray-300 hover:border-gray-400' }}"
                                        @if ($this->{$field} === $i) style="background: {{ $ratingColors[$i] }}" @endif>{{ $i }}</button>
                            @endfor
                        </div>
                        @error($field)<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
        @endforeach

        {{-- Q5 comments --}}
        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 space-y-4">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-gray-200 text-gray-700 grid place-items-center text-xs font-bold">5</span>
                <h2 class="font-bold text-gray-800">ຂໍ້ສະເໜີ ແລະ ຄຳເຫັນ · Suggestions</h2>
            </div>
            <div>
                <label class="text-sm text-gray-600">ສິ່ງທີ່ພວກເຮົາເຮັດໄດ້ດີ · What we are doing well</label>
                <textarea wire:model="doing_well" rows="2" class="mt-1 w-full rounded-lg border-gray-300 text-sm"></textarea>
            </div>
            <div>
                <label class="text-sm text-gray-600">ດ້ານທີ່ຢາກໃຫ້ປັບປຸງ · Area to improve</label>
                <textarea wire:model="improve" rows="2" class="mt-1 w-full rounded-lg border-gray-300 text-sm"></textarea>
            </div>
        </div>

        <button type="submit" wire:loading.attr="disabled"
                class="w-full h-12 rounded-xl bg-sky-600 text-white font-semibold hover:bg-sky-700 disabled:opacity-60">
            <span wire:loading.remove wire:target="submit">ສົ່ງແບບສອບຖາມ · Submit</span>
            <span wire:loading wire:target="submit">ກຳລັງສົ່ງ…</span>
        </button>
    </form>
@endif
</div>
