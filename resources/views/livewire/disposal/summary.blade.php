<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">ລິສ ລວມ ຈຳໜ່າຍ <span class="text-gray-400 text-sm font-normal">· Disposal summary</span></h2>
                <p class="text-sm text-gray-400">ລວມ {{ $items->count() }} ລາຍການ · {{ number_format($totalQty) }} ໜ່ວຍ · ມູນຄ່າ {{ number_format($totalValue) }} ກີບ</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('disposal.summary.pdf', ['from' => $from, 'to' => $to, 'department_id' => $department_id, 'status' => $status]) }}" target="_blank" class="text-sm text-gray-700 border border-gray-300 rounded-md px-3 py-1.5 hover:bg-gray-50">📄 PDF</a>
                <a href="{{ route('disposal') }}" wire:navigate class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</a>
            </div>
        </div>

        <div class="bg-white border border-gray-100 rounded-lg p-4 flex flex-wrap items-end gap-3 text-sm">
            <div><label class="block text-xs text-gray-500 mb-1">ແຕ່ ວັນທີ</label><input type="date" wire:model.live="from" class="rounded-md border-gray-300 text-sm" /></div>
            <div><label class="block text-xs text-gray-500 mb-1">ຫາ ວັນທີ</label><input type="date" wire:model.live="to" class="rounded-md border-gray-300 text-sm" /></div>
            <div><label class="block text-xs text-gray-500 mb-1">ພະແນກ</label><select wire:model.live="department_id" class="rounded-md border-gray-300 text-sm"><option value="">ທັງໝົດ</option>@foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
            <div><label class="block text-xs text-gray-500 mb-1">ສະຖານະ</label><select wire:model.live="status" class="rounded-md border-gray-300 text-sm"><option value="disposed">ຈຳໜ່າຍ ແລ້ວ</option><option value="approved">ອະນຸມັດ ແລ້ວ</option><option value="all">ອະນຸມັດ + ຈຳໜ່າຍ</option></select></div>
        </div>

        <div class="bg-white border border-gray-100 rounded-lg overflow-auto max-h-[calc(100vh-18rem)]">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 bg-gray-50 text-gray-700 border-b border-gray-200 shadow-sm">
                    <tr>
                        <th class="text-left font-semibold px-3 py-2 whitespace-nowrap">DS</th>
                        <th class="text-left font-semibold px-3 py-2 w-full">ລາຍການ</th>
                        <th class="text-left font-semibold px-3 py-2 whitespace-nowrap">ພະແນກ</th>
                        <th class="text-right font-semibold px-3 py-2 whitespace-nowrap">ຈຳນວນ</th>
                        <th class="text-left font-semibold px-3 py-2 whitespace-nowrap">ເຫດຜົນ</th>
                        <th class="text-left font-semibold px-3 py-2 whitespace-nowrap">ຄຳແນະນຳ</th>
                        <th class="text-right font-semibold px-3 py-2 whitespace-nowrap">ມູນຄ່າ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($items as $it)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 align-top whitespace-nowrap"><a href="{{ route('disposal.show', $it->record_id) }}" wire:navigate class="font-mono text-indigo-600 hover:underline">{{ $it->record?->request_number }}</a></td>
                            <td class="px-3 py-2 align-top">{{ $it->item_name }}@if ($it->asset_code)<span class="text-xs text-gray-400 font-mono"> · {{ $it->asset_code }}</span>@endif</td>
                            <td class="px-3 py-2 align-top whitespace-nowrap text-gray-600">{{ $it->record?->department?->name ?? '—' }}</td>
                            <td class="px-3 py-2 align-top text-right whitespace-nowrap">{{ $it->qty }} {{ $it->unit }}</td>
                            <td class="px-3 py-2 align-top whitespace-nowrap text-gray-600">{{ $it->reason ?: '—' }}</td>
                            <td class="px-3 py-2 align-top whitespace-nowrap text-gray-600">{{ $it->recommendation ?: '—' }}</td>
                            <td class="px-3 py-2 align-top text-right whitespace-nowrap">{{ $it->estimated_value ? number_format($it->estimated_value) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">ບໍ່ ມີ ລາຍການ ໃນ ໄລຍະ ນີ້</td></tr>
                    @endforelse
                </tbody>
                @if ($items->count())
                    <tfoot class="bg-gray-50 font-medium text-gray-700 border-t border-gray-200">
                        <tr>
                            <td class="px-3 py-2" colspan="3">ລວມ {{ $items->count() }} ລາຍການ</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">{{ number_format($totalQty) }}</td>
                            <td colspan="2"></td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">{{ number_format($totalValue) }} ກີບ</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
