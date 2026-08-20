<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @include('settings._tabs')
        <div x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)" x-show="show" style="display:none"
             class="fixed bottom-4 right-4 z-50 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 shadow-lg">ບັນທຶກແລ້ວ ✓</div>

        {{-- SMTP settings --}}
        <div class="bg-white border border-gray-100 rounded-lg p-5 md:max-w-lg space-y-3">
            <div>
                <h3 class="font-medium text-gray-800">📧 ຕົວ ສົ່ງ email (SMTP)</h3>
                <p class="text-xs text-gray-500">ຕັ້ງ ບໍລິການ ສົ່ງ email (ລືມ ລະຫັດ · ແຈ້ງເຕືອນ). ຖ້າ ບໍ່ ເປີດ → email ຖືກ ຂຽນ ໃສ່ log ບໍ່ ໄດ້ ສົ່ງ ອອກ.</p>
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" wire:model.live="enabled" class="rounded border-gray-300 text-sky-600 focus:ring-sky-500" />
                ເປີດ ໃຊ້ SMTP (ສົ່ງ email ຈິງ)
            </label>

            @if ($enabled)
                <div class="space-y-3 border-t border-gray-100 pt-3">
                    <div class="grid grid-cols-3 gap-2">
                        <div class="col-span-2">
                            <label class="block text-xs text-gray-500 mb-1">SMTP host</label>
                            <input type="text" wire:model="host" placeholder="smtp.gmail.com · smtp-relay.brevo.com" class="w-full rounded-md border-gray-300 text-sm" />
                            @error('host')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Port</label>
                            <input type="number" wire:model="port" class="w-full rounded-md border-gray-300 text-sm" />
                            @error('port')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Encryption</label>
                        <select wire:model="scheme" class="w-full rounded-md border-gray-300 text-sm">
                            <option value="tls">TLS (port 587)</option>
                            <option value="ssl">SSL (port 465)</option>
                            <option value="">None</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Username</label>
                        <input type="text" wire:model="username" placeholder="you@gmail.com" class="w-full rounded-md border-gray-300 text-sm" autocomplete="off" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Password / App Password @if ($hasPassword)<span class="text-green-600">· ຕັ້ງ ໄວ້ ແລ້ວ</span>@endif</label>
                        <input type="password" wire:model="password" placeholder="{{ $hasPassword ? '•••••• (ວ່າງ = ບໍ່ ປ່ຽນ)' : '' }}" class="w-full rounded-md border-gray-300 text-sm" autocomplete="new-password" />
                        <p class="text-[11px] text-gray-400 mt-1">ເກັບ ແບບ ເຂົ້າລະຫັດ ໄວ້. ວ່າງ = ຮັກສາ ຄ່າ ເກົ່າ.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">From address</label>
                            <input type="email" wire:model="fromAddress" placeholder="noreply@homesang.pro" class="w-full rounded-md border-gray-300 text-sm" />
                            @error('fromAddress')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">From name</label>
                            <input type="text" wire:model="fromName" class="w-full rounded-md border-gray-300 text-sm" />
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-400">Gmail: host <b>smtp.gmail.com</b> · port <b>587</b> (TLS) · username = ອີເມລ · password = <b>App Password</b> (ບໍ່ ແມ່ນ ລະຫັດ login).</p>
                </div>
            @endif

            @can('settings.edit')<div class="pt-1"><button wire:click="save" class="text-sm text-white bg-sky-600 rounded-md px-5 py-2 min-h-[40px] hover:bg-sky-700">Save</button></div>@endcan
        </div>

        {{-- Test email --}}
        @can('settings.edit')
            <div class="bg-white border border-gray-100 rounded-lg p-5 md:max-w-lg space-y-3">
                <div>
                    <h3 class="font-medium text-gray-800">✉️ ສົ່ງ email ທົດສອບ</h3>
                    <p class="text-xs text-gray-500">ບັນທຶກ ຄ່າ ຂ້າງ ເທິງ ກ່ອນ ແລ້ວ ຄ່ອຍ ທົດສອບ.</p>
                </div>
                <div class="flex items-end gap-2">
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 mb-1">ສົ່ງ ໄປ ຫາ</label>
                        <input type="email" wire:model="testTo" class="w-full rounded-md border-gray-300 text-sm" />
                        @error('testTo')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <button wire:click="sendTest" wire:loading.attr="disabled" wire:target="sendTest" class="text-sm text-sky-700 border border-sky-200 rounded-md px-4 py-2 hover:bg-sky-50 disabled:opacity-50 whitespace-nowrap">
                        <span wire:loading.remove wire:target="sendTest">ສົ່ງ ທົດສອບ</span>
                        <span wire:loading wire:target="sendTest">ກຳລັງ ສົ່ງ…</span>
                    </button>
                </div>
                @if ($result)
                    <div class="text-sm rounded-md px-3 py-2 {{ $resultType === 'ok' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' }}">{{ $result }}</div>
                @endif
            </div>
        @endcan
    </div>
</div>
