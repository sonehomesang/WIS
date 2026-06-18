@php
    $badge = fn ($s) => match ($s) {
        'draft' => 'bg-gray-100 text-gray-700', 'acknowledged' => 'bg-blue-100 text-blue-700',
        'approved' => 'bg-sky-100 text-sky-700', 'active' => 'bg-emerald-100 text-emerald-700',
        'overdue' => 'bg-red-100 text-red-700', 'returned' => 'bg-gray-200 text-gray-700',
        'cancelled' => 'bg-gray-100 text-gray-500', default => 'bg-gray-100 text-gray-600',
    };
    $st = $record->display_status;
@endphp

<div class="pb-6">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        <a href="{{ route('borrow') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">← ກັບໄປ list</a>

        @if (session('ok'))<div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2">{{ session('ok') }}</div>@endif
        @error('action')<div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">{{ $message }}</div>@enderror

        <div class="bg-white rounded-lg border border-gray-100 overflow-hidden">
            {{-- header --}}
            <div class="px-5 py-3 border-b flex items-center justify-between">
                <div><span class="font-mono font-semibold">{{ $record->request_number }}</span> <span class="text-xs px-2 py-0.5 rounded {{ $badge($st) }}">{{ $st }}</span>
                    <div class="text-xs text-gray-400">{{ $record->borrower_name }} · ສ້າງ {{ $record->created_at?->format('Y-m-d H:i') }}</div></div>
            </div>

            {{-- info --}}
            <div class="grid md:grid-cols-2 gap-4 p-5 text-sm">
                <div class="space-y-1">
                    <div class="font-semibold text-gray-700">ຂໍ້ມູນ</div>
                    <div>ຜູ້ຢືມ: {{ $record->borrower_name }} ({{ $record->borrower_email }})</div>
                    <div>ຢືມ: {{ $record->borrow_date?->toDateString() }} · ສົ່ງ: {{ $record->planned_return_date?->toDateString() }} ({{ $record->period_days }} ມື້)</div>
                    <div>ປະເພດ: {{ $record->borrow_type }}</div>
                    @if ($record->purpose)<div>ຈຸດປະສົງ: {{ $record->purpose }}</div>@endif
                    <div>Approver: {{ $record->approver_name ?? '—' }}@if ($record->requires_acknowledge) · Line Mgr: {{ $record->acknowledge_name ?? '—' }}@endif</div>
                    @if ($record->cancel_reason)<div class="text-red-600">ເຫດຍົກເລີກ: {{ $record->cancel_reason }}</div>@endif
                </div>
                <div>
                    <div class="font-semibold text-gray-700 mb-1">ປະຫວັດ (timeline)</div>
                    <ul class="text-xs text-gray-500 space-y-1">
                        @foreach ($record->history as $h)
                            <li>{{ $h->created_at?->format('m-d H:i') }} · {{ $h->user_name }} → <b>{{ $h->action }}</b> ({{ $h->status }})</li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- items --}}
            <table class="w-full text-sm border-t">
                <thead class="bg-gray-100 text-gray-700"><tr class="text-left"><th class="px-4 py-2 font-semibold">ລາຍການ</th><th class="px-4 py-2 font-semibold">qty</th><th class="px-4 py-2 font-semibold">return</th></tr></thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($record->items as $it)
                        <tr><td class="px-4 py-2">{{ $it->item_name }}@if ($it->item_id)<span class="font-mono text-xs text-gray-400"> · #{{ $it->item_id }}</span>@endif</td><td class="px-4 py-2">{{ $it->qty }}</td><td class="px-4 py-2 text-gray-500">{{ $it->return_qty ?? '—' }}</td></tr>
                    @endforeach
                </tbody>
            </table>

            {{-- actions --}}
            <div class="px-5 py-3 border-t bg-gray-50 flex flex-wrap gap-2 text-sm">
                <span class="text-gray-400 mr-1 self-center">Actions:</span>
                @if ($record->status === 'draft')
                    <button wire:click="submit" class="text-white bg-sky-600 rounded px-3 py-1.5">ສົ່ງຂໍອະນຸມັດ</button>
                    <button wire:click="$set('showCancel', true)" class="border rounded px-3 py-1.5">ຍົກເລີກ</button>
                @elseif ($record->status === 'acknowledged')
                    @if ($steps['acknowledge'] && ! $record->acknowledged_at)<button wire:click="acknowledge" class="text-white bg-blue-600 rounded px-3 py-1.5">Acknowledge</button>@endif
                    @if ($steps['approve'])<button wire:click="approve" class="text-white bg-sky-600 rounded px-3 py-1.5">Approve</button>@endif
                    <button wire:click="$set('showCancel', true)" class="border rounded px-3 py-1.5">Reject/ຍົກເລີກ</button>
                @elseif ($record->status === 'approved')
                    <button wire:click="confirmTake" class="text-white bg-emerald-600 rounded px-3 py-1.5">ມອບເຄື່ອງ (confirmTake)</button>
                    <button wire:click="$set('showCancel', true)" class="border rounded px-3 py-1.5">ຍົກເລີກ</button>
                @elseif (in_array($record->status, ['active', 'overdue']))
                    <button wire:click="confirmReturn" class="text-white bg-sky-600 rounded px-3 py-1.5">ຮັບคืน (confirmReturn)</button>
                @else
                    <span class="text-gray-400 self-center">— ບໍ່ມີ action ({{ $record->status }})</span>
                @endif
            </div>
        </div>

        @if ($showCancel)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="bg-white rounded-lg p-5 w-full max-w-sm space-y-3">
                    <h3 class="font-medium">ເຫດຜົນ (optional)</h3>
                    <textarea wire:model="cancelReason" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                    <div class="flex justify-end gap-2"><button wire:click="$set('showCancel', false)" class="border rounded px-3 py-1.5 text-sm">ປິດ</button><button wire:click="cancel" class="bg-red-600 text-white rounded px-3 py-1.5 text-sm">ຢືນຢັນຍົກເລີກ</button></div>
                </div>
            </div>
        @endif
    </div>
</div>
