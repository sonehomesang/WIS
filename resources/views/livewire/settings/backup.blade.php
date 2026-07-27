<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @include('settings._tabs')

        @if (session('ok'))<div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2">{{ session('ok') }}</div>@endif
        @if ($error)<div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">✗ {{ $error }}</div>@endif

        <div class="bg-white border border-gray-100 rounded-lg p-5 space-y-3">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="font-medium text-gray-800">💾 Backup ຖານຂໍ້ມູນ</h3>
                    <p class="text-xs text-gray-500">mysqldump (gzip) ເກັບ ໃນ server (ບໍ່ເປີດ public). ດາວໂຫລດ ໄປ ເກັບ ນອກ ໄດ້.</p>
                </div>
                @if ($canManage)
                    <button wire:click="createBackup" wire:loading.attr="disabled" wire:target="createBackup"
                            class="text-sm text-white bg-sky-600 rounded-md px-4 py-2 hover:bg-sky-700 disabled:opacity-50 whitespace-nowrap">
                        <span wire:loading.remove wire:target="createBackup">+ ສ້າງ Backup</span>
                        <span wire:loading wire:target="createBackup">⏳ ກຳລັງ backup…</span>
                    </button>
                @endif
            </div>

            <div class="border border-gray-100 rounded-lg overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs">
                        <tr><th class="text-left px-3 py-2 font-medium">ໄຟລ໌</th><th class="text-left px-3 py-2 font-medium">ຂະໜາດ</th><th class="text-left px-3 py-2 font-medium">ວັນທີ</th><th class="px-3 py-2"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($backups as $b)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 font-mono text-xs text-gray-700">{{ $b['name'] }}</td>
                                <td class="px-3 py-2 text-gray-500 whitespace-nowrap">{{ number_format($b['size'] / 1024, 0) }} KB</td>
                                <td class="px-3 py-2 text-gray-500 whitespace-nowrap">{{ \Illuminate\Support\Carbon::createFromTimestamp($b['at'])->format('d/m/Y H:i') }}</td>
                                <td class="px-3 py-2 text-right whitespace-nowrap">
                                    @if ($canManage)
                                        <button wire:click="download('{{ $b['name'] }}')" class="text-xs text-sky-600 hover:underline">⬇ download</button>
                                        <button wire:click="deleteBackup('{{ $b['name'] }}')" wire:confirm="ລຶບ backup ນີ້?" class="text-xs text-red-500 hover:underline ml-2">✕ ລຶບ</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-6 text-center text-gray-400">ຍັງບໍ່ມີ backup</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Restore — super_admin only, destructive --}}
        @if ($isSuperAdmin)
            <div class="bg-white border border-red-200 rounded-lg p-5 space-y-3">
                <div>
                    <h3 class="font-medium text-red-700">♻️ Restore (ອັນຕະລາຍ)</h3>
                    <p class="text-xs text-gray-500">ຂຽນທັບ ຂໍ້ມູນ ປັຈຈຸບັນ ດ້ວຍ backup ທີ່ເລືອກ. <b class="text-red-600">ທຳໃນເວລາບຳລຸງຮັກສາ ເທົ່ານັ້ນ</b> — super_admin ເທົ່ານັ້ນ.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 items-end">
                    <div class="sm:col-span-2">
                        <label class="block text-xs text-gray-500 mb-1">ເລືອກ backup</label>
                        <select wire:model="restoreTarget" class="w-full rounded-md border-gray-300 text-sm">
                            <option value="">— ເລືອກ —</option>
                            @foreach ($backups as $b)<option value="{{ $b['name'] }}">{{ $b['name'] }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">ພິມ <code class="bg-gray-100 px-1 rounded">RESTORE</code></label>
                        <input type="text" wire:model="confirmRestore" placeholder="RESTORE" class="w-full rounded-md border-gray-300 text-sm" />
                    </div>
                </div>
                <button wire:click="restore" wire:loading.attr="disabled" wire:target="restore"
                        wire:confirm="ແນ່ໃຈບໍ່? ການ restore ຈະຂຽນທັບ ຂໍ້ມູນ ປັຈຈຸບັນ ທັງໝົດ."
                        class="text-sm text-white bg-red-600 rounded-md px-4 py-2 hover:bg-red-700 disabled:opacity-50">
                    <span wire:loading.remove wire:target="restore">♻️ Restore</span>
                    <span wire:loading wire:target="restore">⏳ ກຳລັງ restore…</span>
                </button>
            </div>
        @endif
    </div>
</div>
