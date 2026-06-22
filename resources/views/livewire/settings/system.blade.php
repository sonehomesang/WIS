<div class="pb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @include('settings._tabs')
        <div x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)" x-show="show" style="display:none"
             class="fixed bottom-4 right-4 z-50 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 shadow-lg">ບັນທຶກແລ້ວ ✓</div>

        <div class="bg-white border border-gray-100 rounded-lg p-5 md:max-w-md space-y-4">
            <div>
                <h3 class="font-medium text-gray-800">VAT (global)</h3>
                <p class="text-xs text-gray-500">ໃຊ້ເປັນຄ່າເລີ່ມຕົ້ນ — supplier contract ທີ່ active override ໄດ້.</p>
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" wire:model.live="vat_enabled" class="rounded border-gray-300 text-sky-600 focus:ring-sky-500" /> ເປີດໃຊ້ VAT
            </label>
            <div>
                <label class="block text-sm text-gray-600 mb-1">ອັດຕາ VAT (%)</label>
                <div class="flex items-center gap-2">
                    <input type="number" step="0.01" min="0" max="100" wire:model="vat_rate" @disabled(! $vat_enabled) class="w-32 rounded-md border-gray-300 text-sm disabled:bg-gray-100" />
                    <span class="text-sm text-gray-500">%</span>
                </div>
                @error('vat_rate')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            @can('settings.edit')
                <div class="pt-1"><button wire:click="save" class="text-sm text-white bg-sky-600 rounded-md px-5 py-2 min-h-[40px] hover:bg-sky-700">Save</button></div>
            @endcan
        </div>

        {{-- Request form fields — admin ເປີດ/ປິດ --}}
        <div class="bg-white border border-gray-100 rounded-lg p-5 md:max-w-md space-y-3">
            <div>
                <h3 class="font-medium text-gray-800">Request form fields</h3>
                <p class="text-xs text-gray-500">ເລືອກ field ໃດ ໃຫ້ສະແດງ ໃນຟອມ ໃບເບີກວັດສະດຸ (ປິດ = ເຊື່ອງ + ບໍ່ບັງຄັບ).</p>
            </div>
            @php $reqLabels = ['supplier' => 'Supplier', 'currency' => 'ສະກຸນເງິນ (Currency)', 'rooms' => 'ຫ້ອງ (Rooms)', 'functions' => 'Functions', 'approver' => 'Approver', 'request_type' => 'ປະເພດ (Type)', 'wo_e_form' => 'WO / eForm']; @endphp
            <div class="grid grid-cols-2 gap-2">
                @foreach ($reqLabels as $key => $label)
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="reqFields.{{ $key }}" class="rounded border-gray-300 text-sky-600 focus:ring-sky-500" /> {{ $label }}
                    </label>
                @endforeach
            </div>
            @can('settings.edit')
                <div class="pt-1"><button wire:click="saveRequestFields" class="text-sm text-white bg-sky-600 rounded-md px-5 py-2 min-h-[40px] hover:bg-sky-700">Save fields</button></div>
            @endcan
        </div>
    </div>
</div>
