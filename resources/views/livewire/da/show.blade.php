@php
    $statusMeta = fn ($s) => match ($s) {
        'draft' => ['DRAFT', 'bg-gray-100 text-gray-600'],
        'submitted' => ['SUBMITTED', 'bg-blue-50 text-blue-700'],
        'purchasing_review' => ['PURCHASING REVIEW', 'bg-violet-50 text-violet-700'],
        'pending_approval' => ['PENDING APPROVAL', 'bg-amber-50 text-amber-700'],
        'resolved' => ['RESOLVED', 'bg-emerald-100 text-emerald-800'],
        'cancelled' => ['CANCELLED', 'bg-gray-100 text-gray-400'],
        default => [strtoupper($s), 'bg-gray-100 text-gray-600'],
    };
    [$slbl, $scls] = $statusMeta($record->status);
    $lbl = 'px-3 py-2 text-gray-500 text-xs bg-gray-50 border border-gray-200';
    $bd = 'px-3 py-2 border border-gray-200';
    $typeLabel = ['incorrect_supplied' => 'ສົ່ງຜິດ', 'oversupplied' => 'ສົ່ງເກີນ', 'undersupplied' => 'ສົ່ງຂາດ', 'damaged' => 'ເສຍຫາຍ', 'no_paperwork' => 'ບໍ່ມີเอกสาร', 'other' => 'ອື່ນໆ'];
    $kindLabel = ['overview' => 'ພาบรวม', 'defect' => 'ຈຸດເສຍ', 'comparison' => 'ປຽບທຽບ'];
@endphp

<div class="pb-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        <div class="flex items-center justify-between gap-2">
            <a href="{{ route('da') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">← ກັບໄປ list</a>
            <a href="{{ route('da.pdf', $record) }}" class="text-sm text-gray-700 border border-gray-300 rounded-md px-3 py-1.5 hover:bg-gray-50">📄 PDF</a>
        </div>

        @if (session('ok'))<div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2">{{ session('ok') }}</div>@endif
        @error('action')<div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">{{ $message }}</div>@enderror
        @if ($record->reject_reason && $record->status === 'purchasing_review')<div class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">⚠ Leader ສົ່ງกลับ: {{ $record->reject_reason }}</div>@endif
        @if ($record->status === 'cancelled' && $record->cancel_reason)<div class="text-sm text-gray-600 bg-gray-50 border border-gray-200 rounded-md px-3 py-2">ຍົກເລີກ: {{ $record->cancel_reason }}</div>@endif

        <div class="bg-white border border-gray-200 rounded-lg p-5 space-y-4 text-sm">
            <div class="flex items-start justify-between border-b border-gray-200 pb-3">
                <div>
                    <div class="text-xs text-gray-400">NAM THEUN 2 — WAREHOUSE INFORMATION SYSTEM</div>
                    <div class="text-lg font-bold text-gray-800">ໃບແຈ້ງຄວາມຜິດ / DISCREPANCY ADVICE</div>
                </div>
                <div class="text-right">
                    <div class="font-mono font-bold text-gray-800">{{ $record->da_number }}</div>
                    <span class="inline-flex items-center gap-1 text-xs font-medium rounded-full px-2.5 py-1 mt-1 {{ $scls }}">{{ $slbl }}</span>
                    @if ($record->next_step === 'oga')<div class="text-xs text-sky-600 mt-1">→ ສ້າງ OGA</div>@endif
                </div>
            </div>

            {{-- A --}}
            <div class="text-xs font-semibold text-gray-500 uppercase">A · Warehouse</div>
            <table class="w-full border-collapse">
                <tbody>
                    <tr><td class="{{ $lbl }}" style="width:22%">Supplier</td><td class="{{ $bd }}" style="width:28%">{{ $record->supplier_name ?? '—' }}</td><td class="{{ $lbl }}" style="width:22%">PO</td><td class="{{ $bd }}" style="width:28%">{{ $record->po_number ?? '—' }} @if ($record->po_date)({{ $record->po_date->format('d/m/Y') }})@endif</td></tr>
                    <tr><td class="{{ $lbl }}">ປະເພດຄວາມຜິດ</td><td class="{{ $bd }}" colspan="3">{{ collect($record->discrepancy_types ?? [])->map(fn ($t) => $typeLabel[$t] ?? $t)->implode(', ') ?: '—' }}</td></tr>
                    <tr><td class="{{ $lbl }}">Packing list</td><td class="{{ $bd }}">{{ $record->vendor_packing_list ?? '—' }}</td><td class="{{ $lbl }}">Vendor invoice</td><td class="{{ $bd }}">{{ $record->vendor_invoice_number ?? '—' }}</td></tr>
                    @if ($record->warehouse_recommendation_kind || $record->warehouse_recommendation_text)<tr><td class="{{ $lbl }}">ຄຳແນະນຳ</td><td class="{{ $bd }}" colspan="3">{{ $record->warehouse_recommendation_kind }} — {{ $record->warehouse_recommendation_text }}</td></tr>@endif
                    @if ($record->comments)<tr><td class="{{ $lbl }}">ໝາຍເຫດ</td><td class="{{ $bd }}" colspan="3">{{ $record->comments }}</td></tr>@endif
                </tbody>
            </table>

            {{-- items --}}
            <table class="w-full border-collapse">
                <thead><tr class="text-xs text-gray-500"><th class="{{ $bd }}">#</th><th class="{{ $bd }} text-left">Stock</th><th class="{{ $bd }} text-left">ລາຍລະອຽດ</th><th class="{{ $bd }}">ສັ່ງ</th><th class="{{ $bd }}">ສົ່ງ</th><th class="{{ $bd }}">ຮັບ</th></tr></thead>
                <tbody>
                    @foreach ($record->items as $idx => $it)
                        <tr><td class="{{ $bd }} text-center">{{ $idx + 1 }}</td><td class="{{ $bd }}">{{ $it->stock_code ?? '—' }}</td><td class="{{ $bd }}">{{ $it->description }}</td><td class="{{ $bd }} text-center">{{ $it->qty_ordered }}</td><td class="{{ $bd }} text-center">{{ $it->qty_delivered }}</td><td class="{{ $bd }} text-center">{{ $it->qty_received }}</td></tr>
                    @endforeach
                </tbody>
            </table>

            {{-- photos --}}
            @if ($record->photos->count())
                <div class="flex gap-3 flex-wrap">
                    @foreach ($record->photos as $p)
                        <div class="text-center"><img src="{{ $p->url }}" alt="" class="w-24 h-24 rounded object-cover border border-gray-200" /><div class="text-xs text-gray-400 mt-0.5">{{ $kindLabel[$p->kind] ?? $p->kind }}</div></div>
                    @endforeach
                </div>
            @endif

            {{-- B --}}
            @if (in_array($record->status, ['pending_approval', 'resolved'], true) || $record->purchasing_decisions)
                <div class="text-xs font-semibold text-gray-500 uppercase pt-2 border-t border-gray-100">B · Purchasing</div>
                <table class="w-full border-collapse">
                    <tbody>
                        <tr><td class="{{ $lbl }}" style="width:22%">ການຕັດສິນໃຈ</td><td class="{{ $bd }}" colspan="3">{{ collect($record->purchasing_decisions ?? [])->implode(', ') ?: '—' }}</td></tr>
                        @if ($record->purchasing_note)<tr><td class="{{ $lbl }}">ໝາຍເຫດ</td><td class="{{ $bd }}" colspan="3">{{ $record->purchasing_note }}</td></tr>@endif
                        @if ($record->return_transport_account)<tr><td class="{{ $lbl }}">ການส่งคืน</td><td class="{{ $bd }}" colspan="3">{{ $record->return_transport_account }} / {{ $record->return_transport_mode }} · {{ $record->return_carrier_name }} {{ $record->return_carrier_phone }}</td></tr>@endif
                    </tbody>
                </table>
            @endif

            {{-- C --}}
            @if ($record->status === 'resolved')
                <div class="text-xs font-semibold text-gray-500 uppercase pt-2 border-t border-gray-100">C · Leader</div>
                <table class="w-full border-collapse">
                    <tbody>
                        <tr><td class="{{ $lbl }}" style="width:22%">Resolution</td><td class="{{ $bd }}" colspan="3">{{ $record->resolution_action ?? '—' }}</td></tr>
                        <tr><td class="{{ $lbl }}">ອະນຸมัด</td><td class="{{ $bd }}">{{ $record->approved_by_name }} ({{ $record->approved_title }})</td><td class="{{ $lbl }}">Next</td><td class="{{ $bd }}">{{ $record->next_step }}</td></tr>
                    </tbody>
                </table>
            @endif

            @if ($record->history->count())
                <div><div class="font-semibold text-gray-700 mb-1">ປະຫວັດ / History</div>
                    <ol class="text-xs text-gray-500 space-y-0.5">@foreach ($record->history as $h)<li><span class="font-mono text-gray-700">{{ $h->status }}</span> · {{ $h->user_name }} · {{ $h->created_at?->format('d/m/Y H:i') }}@if ($h->comment) — {{ $h->comment }}@endif</li>@endforeach</ol>
                </div>
            @endif
        </div>

        {{-- actions --}}
        <div class="bg-white rounded-lg border border-gray-100 px-5 py-3 flex flex-wrap gap-2 text-sm items-center">
            <span class="text-gray-400 mr-1">Actions:</span>
            @if ($record->status === 'draft')
                <button wire:click="submit" class="text-white bg-indigo-600 rounded px-3 py-1.5">ສົ່ງ</button>
                <button wire:click="$set('showCancel', true)" class="border rounded px-3 py-1.5">ຍົກເລີກ</button>
            @elseif ($record->status === 'submitted')
                @if ($canAct)<button wire:click="purchasingStart" class="text-white bg-violet-600 rounded px-3 py-1.5">ເລີ່ມ Purchasing review</button>@endif
                <button wire:click="$set('showCancel', true)" class="border rounded px-3 py-1.5">ຍົກເລີກ</button>
            @elseif ($record->status === 'purchasing_review')
                @if ($canAct)<button wire:click="$set('showDecide', true)" class="text-white bg-amber-600 rounded px-3 py-1.5">ບັນທຶກການຕັດສິນໃຈ</button>@endif
                <button wire:click="$set('showCancel', true)" class="border rounded px-3 py-1.5">ຍົກເລີກ</button>
            @elseif ($record->status === 'pending_approval')
                @if ($canAct)<button wire:click="$set('showApprove', true)" class="text-white bg-emerald-600 rounded px-3 py-1.5">ອະນຸมัด</button><button wire:click="$set('showReject', true)" class="text-amber-700 border border-amber-200 bg-amber-50 rounded px-3 py-1.5">ສົ່ງกลับ</button>@endif
            @else
                <span class="text-gray-400">— ບໍ່ມີ action ({{ $record->status }})</span>
            @endif
            @if ($deletable)<span class="ml-auto"></span><button wire:click="openDelete" class="text-red-600 border border-red-200 rounded px-3 py-1.5 hover:bg-red-50">🗑 ລຶບ</button>@endif
        </div>

        {{-- modals --}}
        @if ($showCancel)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="bg-white rounded-lg p-5 w-full max-w-sm space-y-3">
                <h3 class="font-medium text-gray-800">ຍົກເລີກ DA</h3>
                <textarea wire:model="cancelReason" rows="2" placeholder="ເຫດผົน…" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                <div class="flex justify-end gap-2"><button wire:click="$set('showCancel', false)" class="border rounded px-3 py-1.5 text-sm">ປິດ</button><button wire:click="cancel" class="bg-red-600 text-white rounded px-3 py-1.5 text-sm">ຢืนยัน</button></div>
            </div></div>
        @endif
        @if ($showDecide)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="bg-white rounded-lg p-5 w-full max-w-md space-y-3 max-h-[90vh] overflow-y-auto">
                <h3 class="font-medium text-gray-800">B · ການຕັດສິນໃຈ Purchasing</h3>
                @error('action')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <div class="space-y-1">@foreach ($decisionOptions as $d)<label class="flex items-center gap-2 text-sm"><input type="checkbox" value="{{ $d }}" wire:model="decisions" class="rounded border-gray-300 text-sky-600" /> {{ $d }}</label>@endforeach</div>
                <textarea wire:model="purchasingNote" rows="2" placeholder="ໝາຍເຫດ…" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                <div class="grid grid-cols-2 gap-2">
                    <select wire:model="transportAccount" class="rounded-md border-gray-300 text-sm"><option value="">ບัญชีขนส่ง</option><option value="vendor">vendor</option><option value="ntpc">ntpc</option></select>
                    <select wire:model="transportMode" class="rounded-md border-gray-300 text-sm"><option value="">ວິທີ</option><option value="road">road</option><option value="air">air</option></select>
                    <input type="text" wire:model="carrierName" placeholder="Carrier" class="rounded-md border-gray-300 text-sm" />
                    <input type="text" wire:model="carrierPhone" placeholder="Phone" class="rounded-md border-gray-300 text-sm" />
                </div>
                <div class="flex justify-end gap-2"><button wire:click="$set('showDecide', false)" class="border rounded px-3 py-1.5 text-sm">ປິດ</button><button wire:click="purchasingDecide" class="bg-amber-600 text-white rounded px-3 py-1.5 text-sm">ຢืนยัน</button></div>
            </div></div>
        @endif
        @if ($showApprove)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="bg-white rounded-lg p-5 w-full max-w-md space-y-3">
                <h3 class="font-medium text-gray-800">C · ອະນຸมัด (Leader)</h3>
                <textarea wire:model="resolution" rows="2" placeholder="Resolution action…" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                <input type="text" wire:model="approvedTitle" placeholder="ຕຳແໜ່ງ (e.g. Leader Warehousing)" class="w-full rounded-md border-gray-300 text-sm" />
                <div><label class="block text-sm text-gray-600 mb-1">Next step</label><select wire:model="nextStep" class="w-full rounded-md border-gray-300 text-sm"><option value="finished">ปิด (finished)</option><option value="oga">ສ້າງ OGA ຕໍ່</option></select></div>
                <div class="flex justify-end gap-2"><button wire:click="$set('showApprove', false)" class="border rounded px-3 py-1.5 text-sm">ປິດ</button><button wire:click="approve" class="bg-emerald-600 text-white rounded px-3 py-1.5 text-sm">ຢืนยันອະນຸมัด</button></div>
            </div></div>
        @endif
        @if ($showReject)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="bg-white rounded-lg p-5 w-full max-w-sm space-y-3">
                <h3 class="font-medium text-gray-800">ສົ່ງกลับ Purchasing</h3>
                @error('action')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <textarea wire:model="rejectReason" rows="3" placeholder="ເຫດผົน…" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                <div class="flex justify-end gap-2"><button wire:click="$set('showReject', false)" class="border rounded px-3 py-1.5 text-sm">ປິດ</button><button wire:click="reject" class="bg-amber-600 text-white rounded px-3 py-1.5 text-sm">ຢืนยัน</button></div>
            </div></div>
        @endif
        @if ($showDelete)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="bg-white rounded-lg p-5 w-full max-w-sm space-y-3">
                <h3 class="font-medium text-red-700">🗑 ລຶບ DA</h3>
                <textarea wire:model="deleteReason" rows="3" placeholder="ເຫດผົน…" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                @error('deleteReason')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <div class="flex justify-end gap-2"><button wire:click="$set('showDelete', false)" class="border rounded px-3 py-1.5 text-sm">ປິດ</button><button wire:click="deleteRecord" class="bg-red-600 text-white rounded px-3 py-1.5 text-sm">ຢืนยันລຶບ</button></div>
            </div></div>
        @endif
    </div>
</div>
