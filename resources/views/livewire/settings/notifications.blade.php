<div class="pb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @include('settings._tabs')
        <div x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)" x-show="show" style="display:none"
             class="fixed bottom-4 right-4 z-50 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 shadow-lg">ບັນທຶກແລ້ວ ✓</div>

        @php $editable = auth()->user()->can('settings.edit'); @endphp

        {{-- feature flags --}}
        <div class="bg-white border border-gray-100 rounded-lg p-5 md:max-w-md space-y-3">
            <div>
                <h3 class="font-medium text-gray-800">🔔 ການແຈ້ງເຕືອນ (feature flags)</h3>
                <p class="text-xs text-gray-500">ສະວິດຄວບຄຸມ ການແຈ້ງເຕືອນ ທັງລະບົບ.</p>
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" wire:model="enabled" @disabled(! $editable) class="rounded border-gray-300 text-sky-600 focus:ring-sky-500" />
                ເປີດໃຊ້ ການແຈ້ງເຕືອນ (master) <span class="text-xs text-gray-400">— ປິດ = ບໍ່ສ້າງ notification ໃໝ່ທັງໝົด</span>
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" wire:model="borrowReminder" @disabled(! $editable) class="rounded border-gray-300 text-sky-600 focus:ring-sky-500" />
                ເຕືອນ ການຢືມໃກ້ຄົບກຳນົດ ອັດຕະໂນมัด
            </label>
            @if ($editable)
                <div class="pt-1"><button wire:click="saveFlags" class="text-sm text-white bg-sky-600 rounded-md px-4 py-2 hover:bg-sky-700">ບັນທຶກ flags</button></div>
            @endif
        </div>

        {{-- templates --}}
        <div class="bg-white border border-gray-100 rounded-lg p-5 space-y-3">
            <div>
                <h3 class="font-medium text-gray-800">✉️ ແມ່ແບບ ຂໍ້ຄວາມ (templates)</h3>
                <p class="text-xs text-gray-500">ໃຊ້ <code class="bg-gray-100 px-1 rounded">{number}</code> {requester} {reason} {invoice} ເປັນ placeholder.</p>
            </div>
            <div class="space-y-3">
                @foreach ($templates as $i => $t)
                    <div wire:key="tpl-{{ $t['key'] }}" class="border border-gray-100 rounded-lg p-3 space-y-2">
                        <div class="flex items-center justify-between">
                            <code class="text-xs text-sky-700 bg-sky-50 px-1.5 py-0.5 rounded">{{ $t['key'] }}</code>
                            @if ($editable)<button wire:click="resetTemplate({{ $i }})" class="text-xs text-gray-400 hover:text-gray-600">↺ ค่าเริ่มต้น</button>@endif
                        </div>
                        <div><label class="block text-xs text-gray-500 mb-1">ຫົວຂໍ້</label><input type="text" wire:model="templates.{{ $i }}.title" @disabled(! $editable) class="w-full rounded-md border-gray-300 text-sm" /></div>
                        <div><label class="block text-xs text-gray-500 mb-1">ຂໍ້ຄວາມ</label><input type="text" wire:model="templates.{{ $i }}.message" @disabled(! $editable) class="w-full rounded-md border-gray-300 text-sm" /></div>
                    </div>
                @endforeach
            </div>
            @if ($editable)
                <div><button wire:click="saveTemplates" class="text-sm text-white bg-sky-600 rounded-md px-4 py-2 hover:bg-sky-700">ບັນທຶກ templates</button></div>
            @endif
        </div>
    </div>
</div>
