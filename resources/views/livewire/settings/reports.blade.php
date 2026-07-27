<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @include('settings._tabs')

        <div class="flex flex-wrap items-center gap-2">
            <label class="text-xs text-gray-500">ແຕ່ <input type="date" wire:model.live="from" class="rounded-md border-gray-300 text-sm" /></label>
            <label class="text-xs text-gray-500">ຫາ <input type="date" wire:model.live="to" class="rounded-md border-gray-300 text-sm" /></label>
            <button wire:click="export" class="ml-auto text-sm text-white bg-sky-600 rounded-md px-3 py-1.5 hover:bg-sky-700">⬇ Export CSV</button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach ($summary as $m)
                <div class="bg-white border border-gray-100 rounded-lg p-4">
                    <div class="flex items-baseline justify-between">
                        <h3 class="font-medium text-gray-800">{{ $m['label'] }}</h3>
                        <span class="text-2xl font-bold text-gray-800">{{ number_format($m['total']) }}</span>
                    </div>
                    <div class="mt-2 space-y-1">
                        @forelse ($m['byStatus'] as $status => $count)
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-500">{{ $status }}</span>
                                <span class="font-medium text-gray-700 tabular-nums">{{ $count }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400">ບໍ່ມີ ຂໍ້ມູນ ໃນຊ່ວງນີ້</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
        <p class="text-xs text-gray-400">ນັບຕາມ ວັນທີສ້າງ (created_at) ໃນຊ່ວງທີ່ເລືອກ.</p>
    </div>
</div>
