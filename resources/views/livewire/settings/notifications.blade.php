<div class="pb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @include('settings._tabs')
        <div x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)" x-show="show" style="display:none"
             class="fixed bottom-4 right-4 z-50 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 shadow-lg">ບັນທຶກແລ້ວ ✓</div>

        @php
            $editable = auth()->user()->can('settings.edit');
            $emailFields = [
                'subject'    => ['label' => 'ຫົວ ຂໍ້ (subject)', 'type' => 'input'],
                'greeting'   => ['label' => 'ຄຳ ທັກທາຍ',        'type' => 'input'],
                'intro'      => ['label' => 'ເນື້ອ ຄວາມ',        'type' => 'textarea'],
                'button'     => ['label' => 'ປຸ່ມ (button)',     'type' => 'input'],
                'expiry'     => ['label' => 'ເສັ້ນ ໝົດ ອາຍຸ',    'type' => 'input'],
                'outro'      => ['label' => 'ຂໍ້ຄວາມ ທ້າຍ',      'type' => 'textarea'],
                'salutation' => ['label' => 'ລົງ ທ້າຍ',          'type' => 'input'],
            ];
            $moduleLabels = [
                'request' => 'ໃບເບີກ (Request)',
                'borrow'  => 'ການຢືມ (Borrow)',
                'deposit' => 'ການຝາກ (Deposit)',
                'da'      => 'DA Claims',
                'oga'     => 'OGA',
            ];
        @endphp

        {{-- 1. Global feature flags + send language --}}
        <div class="bg-white border border-gray-100 rounded-lg p-5 md:max-w-lg space-y-3">
            <div>
                <h3 class="font-medium text-gray-800">🔔 ການແຈ້ງເຕືອນ ທັງ ລະບົບ</h3>
                <p class="text-xs text-gray-500">ສະວິດ ຄວບຄຸມ ພາບ ລວມ + ພາສາ ທີ່ ສົ່ງ.</p>
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" wire:model="enabled" @disabled(! $editable) class="rounded border-gray-300 text-sky-600 focus:ring-sky-500" />
                ເປີດໃຊ້ ການແຈ້ງເຕືອນ (master) <span class="text-xs text-gray-400">— ປິດ = ບໍ່ ສ້າງ notification ໃໝ່ ທັງໝົດ</span>
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" wire:model="borrowReminder" @disabled(! $editable) class="rounded border-gray-300 text-sky-600 focus:ring-sky-500" />
                ເຕືອນ ການຢືມ ໃກ້ ຄົບ ກຳນົດ ອັດຕະໂນມັດ
            </label>
            <div class="pt-1">
                <label class="block text-xs text-gray-500 mb-1">ພາສາ ທີ່ ສົ່ງ ອອກ (ຜູ້ໃຊ້ ໄດ້ ຮັບ)</label>
                <select wire:model="lang" @disabled(! $editable) class="rounded-md border-gray-300 text-sm">
                    <option value="lo">ລາວ</option>
                    <option value="en">English</option>
                </select>
            </div>
            @if ($editable)
                <div class="pt-1"><button wire:click="saveFlags" class="text-sm text-white bg-sky-600 rounded-md px-4 py-2 hover:bg-sky-700">ບັນທຶກ</button></div>
            @endif
        </div>

        {{-- edit-language switch (shared by the editable cards below) --}}
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500">ກຳລັງ ແກ້ ພາສາ:</span>
            <div class="inline-flex rounded-md border border-gray-200 overflow-hidden text-sm">
                <button type="button" wire:click="$set('editLang','lo')" class="px-3 py-1.5 {{ $editLang === 'lo' ? 'bg-sky-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">ລາວ</button>
                <button type="button" wire:click="$set('editLang','en')" class="px-3 py-1.5 {{ $editLang === 'en' ? 'bg-sky-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">English</button>
            </div>
        </div>

        {{-- 2. Email category --}}
        <div class="bg-white border border-gray-100 rounded-lg p-5 space-y-3">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="font-medium text-gray-800">📧 ອີເມວ · ຕັ້ງ / ຣີເຊັດ ລະຫັດຜ່ານ</h3>
                    <p class="text-xs text-gray-500">ໃຊ້ ຮ່ວມ: ລືມ ລະຫັດ + ສ້າງ ບັນຊີ ໃໝ່. placeholder: <code class="bg-gray-100 px-1 rounded">{minutes}</code> <code class="bg-gray-100 px-1 rounded">{app}</code> <code class="bg-gray-100 px-1 rounded">{name}</code></p>
                    <p class="text-[11px] text-amber-600 mt-0.5">🔒 email ນີ້ ບໍ່ ໃຫ້ ປິດ (ເປັນ ທາງ ດຽວ ທີ່ ຜູ້ໃຊ້ ກູ້ ບັນຊີ ຄືນ)</p>
                </div>
                @if ($editable)<button wire:click="resetEmail" class="text-xs text-gray-400 hover:text-gray-600 shrink-0">↺ ຄ່າ ເລີ່ມ ຕົ້ນ</button>@endif
            </div>
            <div class="space-y-3">
                @foreach ($emailFields as $f => $meta)
                    <div wire:key="email-{{ $f }}-{{ $editLang }}">
                        <label class="block text-xs text-gray-500 mb-1">{{ $meta['label'] }}</label>
                        @if ($meta['type'] === 'textarea')
                            <textarea wire:model="email.{{ $editLang }}.{{ $f }}" @disabled(! $editable) rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                        @else
                            <input type="text" wire:model="email.{{ $editLang }}.{{ $f }}" @disabled(! $editable) class="w-full rounded-md border-gray-300 text-sm" />
                        @endif
                    </div>
                @endforeach
            </div>
            @if ($editable)
                <div><button wire:click="saveEmail" class="text-sm text-white bg-sky-600 rounded-md px-4 py-2 hover:bg-sky-700">ບັນທຶກ email</button></div>
            @endif
        </div>

        {{-- 3. In-app category (grouped by module) --}}
        <div class="bg-white border border-gray-100 rounded-lg p-5 space-y-3">
            <div>
                <h3 class="font-medium text-gray-800">🔔 ແຈ້ງເຕືອນ ໃນ ແອັບ (bell)</h3>
                <p class="text-xs text-gray-500">placeholder: <code class="bg-gray-100 px-1 rounded">{number}</code> {requester} {reason} {invoice} {borrower} {owner} {actor} {date}</p>
            </div>
            <div class="space-y-3">
                @php $currentModule = null; @endphp
                @foreach ($templates as $i => $t)
                    @if ($t['module'] !== $currentModule)
                        @php $currentModule = $t['module']; @endphp
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide pt-2">{{ $moduleLabels[$currentModule] ?? $currentModule }}</p>
                    @endif
                    <div wire:key="tpl-{{ $t['key'] }}" class="border border-gray-100 rounded-lg p-3 space-y-2 {{ $t['enabled'] ? '' : 'opacity-60' }}">
                        <div class="flex items-center justify-between gap-2">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model="templates.{{ $i }}.enabled" @disabled(! $editable) class="rounded border-gray-300 text-sky-600 focus:ring-sky-500" />
                                <code class="text-xs text-sky-700 bg-sky-50 px-1.5 py-0.5 rounded">{{ $t['key'] }}</code>
                            </label>
                            @if ($editable)<button wire:click="resetTemplate({{ $i }})" class="text-xs text-gray-400 hover:text-gray-600">↺ ຄ່າ ເລີ່ມ ຕົ້ນ</button>@endif
                        </div>
                        <div wire:key="tpl-{{ $t['key'] }}-title-{{ $editLang }}"><label class="block text-xs text-gray-500 mb-1">ຫົວຂໍ້</label><input type="text" wire:model="templates.{{ $i }}.{{ $editLang }}.title" @disabled(! $editable) class="w-full rounded-md border-gray-300 text-sm" /></div>
                        <div wire:key="tpl-{{ $t['key'] }}-msg-{{ $editLang }}"><label class="block text-xs text-gray-500 mb-1">ຂໍ້ຄວາມ</label><input type="text" wire:model="templates.{{ $i }}.{{ $editLang }}.message" @disabled(! $editable) class="w-full rounded-md border-gray-300 text-sm" /></div>
                    </div>
                @endforeach
            </div>
            @if ($editable)
                <div><button wire:click="saveTemplates" class="text-sm text-white bg-sky-600 rounded-md px-4 py-2 hover:bg-sky-700">ບັນທຶກ ແຈ້ງເຕືອນ ໃນ ແອັບ</button></div>
            @endif
        </div>
    </div>
</div>
