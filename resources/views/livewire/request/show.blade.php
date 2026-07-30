@php
    $statusMeta = fn ($s) => match ($s) {
        'draft' => ['DRAFT', 'bg-gray-100 text-gray-600'],
        'submitted' => ['SUBMITTED', 'bg-blue-50 text-blue-700'],
        'approved' => ['APPROVED', 'bg-sky-50 text-sky-700'],
        'validated' => ['VALIDATED', 'bg-cyan-50 text-cyan-700'],
        'dispatched' => ['DISPATCHED', 'bg-amber-50 text-amber-700'],
        'received' => ['RECEIVED', 'bg-emerald-50 text-emerald-700'],
        'completed' => ['COMPLETED', 'bg-emerald-100 text-emerald-800'],
        'rejected' => ['REJECTED', 'bg-red-50 text-red-700'],
        'cancelled' => ['CANCELLED', 'bg-gray-100 text-gray-400'],
        default => [strtoupper($s), 'bg-gray-100 text-gray-600'],
    };
    [$slbl, $scls] = $statusMeta($record->status);
    $lbl = 'px-3 py-2 text-gray-500 text-xs bg-gray-50 border border-gray-200';
    $bd = 'px-3 py-2 border border-gray-200';
@endphp

<div class="pb-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        <x-page-subheader :back="route('request')" back-label="ລາຍການ">
            <x-slot:actions>
                <a href="{{ route('request.pdf', $record) }}" class="text-sm text-gray-700 border border-gray-300 rounded-md px-3 py-1.5 hover:bg-gray-50">📄 PDF</a>
            </x-slot>
        </x-page-subheader>

        @if (session('ok'))<div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2">{{ session('ok') }}</div>@endif
        @error('action')<div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">{{ $message }}</div>@enderror
        @if ($record->status === 'rejected' && $record->reject_reason)<div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">ປະຕິເສດ: {{ $record->reject_reason }}</div>@endif
        @if ($record->status === 'cancelled' && $record->cancel_reason)<div class="text-sm text-gray-600 bg-gray-50 border border-gray-200 rounded-md px-3 py-2">ຍົກເລີກ: {{ $record->cancel_reason }}</div>@endif

        {{-- document --}}
        <div class="bg-white border border-gray-200 rounded-lg p-5 space-y-4 text-sm">
            <div class="flex items-start justify-between border-b border-gray-200 pb-3">
                <div>
                    <div class="text-xs text-gray-400">NAM THEUN 2 — WAREHOUSE INFORMATION SYSTEM</div>
                    <div class="text-lg font-bold text-gray-800">ໃບເບີກວັດສະດຸ / MATERIAL REQUEST</div>
                </div>
                <div class="text-right">
                    <div class="font-mono font-bold text-gray-800">{{ $record->request_number }}</div>
                    <span class="inline-flex items-center gap-1 text-xs font-medium rounded-full px-2.5 py-1 mt-1 {{ $scls }}">{{ $slbl }}</span>
                </div>
            </div>

            <table class="w-full border-collapse">
                <tbody>
                    <tr><td class="{{ $lbl }}" style="width:22%">ຜູ້ເບີກ / Requester</td><td class="{{ $bd }}" style="width:28%">{{ $record->requester_name }}</td><td class="{{ $lbl }}" style="width:22%">ໜ່ວຍ / Unit</td><td class="{{ $bd }}" style="width:28%">{{ $record->unit?->name ?? '—' }}</td></tr>
                    <tr><td class="{{ $lbl }}">ຈຸດປະສົງ / Purpose</td><td class="{{ $bd }}" colspan="3">{{ $record->purpose ?? '—' }}</td></tr>
                    <tr><td class="{{ $lbl }}">ປະເພດ / Type</td><td class="{{ $bd }}">{{ $record->request_type ?? '—' }}@if ($record->wo_e_form) · {{ $record->wo_e_form }}@endif</td><td class="{{ $lbl }}">Supplier</td><td class="{{ $bd }}">{{ $record->supplier?->name ?? '—' }}</td></tr>
                    <tr><td class="{{ $lbl }}">Approver</td><td class="{{ $bd }}">{{ $record->approver_name ?? '—' }}@if ($record->approved_at)<div class="text-xs text-gray-400">{{ $record->approved_at->format('d/m/Y H:i') }}</div>@endif</td><td class="{{ $lbl }}">Warehouse</td><td class="{{ $bd }}">{{ $record->warehouse_staff_name ?? '—' }}@if ($record->validated_at)<div class="text-xs text-gray-400">{{ $record->validated_at->format('d/m/Y H:i') }}</div>@endif</td></tr>
                </tbody>
            </table>

            {{-- items --}}
            <div>
                <div class="text-center bg-gray-200 border border-black/10 p-2 font-bold mb-2">ລາຍການ / Items ({{ $record->items->count() }})</div>
                <table class="w-full border-collapse">
                    <thead><tr class="text-xs text-gray-500"><th class="{{ $bd }} text-left">#</th><th class="{{ $bd }} text-left">ລາຍລະອຽດ</th><th class="{{ $bd }}">ໜ່ວຍ</th><th class="{{ $bd }}">ຈຳນວນ</th><th class="{{ $bd }} text-right">ລາຄາ</th><th class="{{ $bd }} text-right">ລວມ</th></tr></thead>
                    <tbody>
                        @foreach ($record->items as $idx => $it)
                            <tr>
                                <td class="{{ $bd }}">{{ $idx + 1 }}</td>
                                <td class="{{ $bd }}">{{ $it->description }}@if ($it->material_nbr)<span class="text-xs text-gray-400"> · {{ $it->material_nbr }}</span>@endif
                                    @if (! empty($it->shop_prices))<div class="text-xs text-gray-400 mt-0.5">ປຽບທຽບ: @foreach ($it->shop_prices as $o){{ $o['supplier_name'] }} {{ number_format($o['unit_price'], 2) }}@if (! $loop->last) · @endif @endforeach</div>@endif
                                </td>
                                <td class="{{ $bd }} text-center">{{ $it->unit ?? '—' }}</td>
                                <td class="{{ $bd }} text-center">{{ $it->quantity }}</td>
                                <td class="{{ $bd }} text-right">{{ number_format($it->unit_price, 2) }}</td>
                                <td class="{{ $bd }} text-right">{{ number_format($it->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="text-gray-700">
                        <tr><td class="{{ $bd }} text-right" colspan="5">ລວມ (net)</td><td class="{{ $bd }} text-right">{{ number_format($record->total, 2) }}</td></tr>
                        <tr><td class="{{ $bd }} text-right" colspan="5">VAT {{ $record->vat_enabled ? rtrim(rtrim(number_format($record->vat_rate, 2), '0'), '.').'%' : '(ປິດ)' }}</td><td class="{{ $bd }} text-right">{{ number_format($record->vat_amount, 2) }}</td></tr>
                        <tr class="font-bold"><td class="{{ $bd }} text-right" colspan="5">ລວມທັງໝົດ / Grand total</td><td class="{{ $bd }} text-right">{{ number_format($record->grand_total, 2) }} {{ $record->currency }}</td></tr>
                    </tfoot>
                </table>
            </div>

            {{-- receipt / close info --}}
            @if (in_array($record->status, ['received', 'completed'], true))
                <div class="border border-emerald-200 bg-emerald-50/50 rounded-md p-3 text-xs text-gray-600">
                    ຮັບເຄື່ອງ: {{ $record->received_at?->format('d/m/Y H:i') }} · {{ $record->received_by_name }}
                    · invoice {{ $record->invoice_received ? '✓' : '✗' }} · delivery-note {{ $record->delivery_note_received ? '✓' : '✗' }} · spec {{ $record->spec_match ? '✓' : '✗' }}
                    @if ($record->status === 'completed')<div class="mt-1">ປິດ: invoice #{{ $record->invoice_number }} · SAP {{ $record->sap_reference }}@if ($record->sapStatusLabel()) · <span class="font-medium">{{ $record->sapStatusLabel() }}</span>@endif</div>@endif
                </div>
            @endif

            @if ($record->history->count())
                <div>
                    <div class="font-semibold text-gray-700 mb-1">ປະຫວັດ / History</div>
                    <ol class="text-xs text-gray-500 space-y-0.5">
                        @foreach ($record->history as $h)
                            <li><span class="font-mono text-gray-700">{{ $h->status }}</span> · {{ $h->user_name }} · {{ $h->created_at?->format('d/m/Y H:i') }}@if ($h->comment) — {{ $h->comment }}@endif</li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </div>

        {{-- actions --}}
        <div class="bg-white rounded-lg border border-gray-100 px-5 py-3 flex flex-wrap gap-2 text-sm items-center sticky bottom-4 z-20 shadow-lg">
            <span class="text-gray-400 mr-1">Actions:</span>
            @if ($record->status === 'draft')
                @if ($isRequester || $editable)<button wire:click="submit" class="text-white bg-indigo-600 rounded px-3 py-1.5">ສົ່ງຄຳຂໍ</button>@endif
                <button wire:click="$set('showCancel', true)" class="border rounded px-3 py-1.5">ຍົກເລີກ</button>
            @elseif ($record->status === 'submitted')
                @if ($canApprove)<button wire:click="approve" class="text-white bg-sky-600 rounded px-3 py-1.5">ອະນຸມັດ</button><button wire:click="$set('showReject', true)" class="text-red-700 border border-red-200 bg-red-50 rounded px-3 py-1.5">ປະຕິເສດ</button>@endif
                <button wire:click="$set('showCancel', true)" class="border rounded px-3 py-1.5">ຍົກເລີກ</button>
            @elseif ($record->status === 'approved')
                @if ($editable)<button wire:click="validateRequest" class="text-white bg-cyan-600 rounded px-3 py-1.5">Validate (warehouse)</button><button wire:click="$set('showReject', true)" class="text-red-700 border border-red-200 bg-red-50 rounded px-3 py-1.5">ປະຕິເສດ</button>@endif
                <button wire:click="$set('showCancel', true)" class="border rounded px-3 py-1.5">ຍົກເລີກ</button>
            @elseif ($record->status === 'validated')
                @if ($editable)<button wire:click="openDispatch" class="text-white bg-amber-600 rounded px-3 py-1.5">Dispatch</button>@endif
                <button wire:click="$set('showCancel', true)" class="border rounded px-3 py-1.5">ຍົກເລີກ</button>
            @elseif ($record->status === 'dispatched')
                @if ($editable)<button wire:click="openReceive" class="text-white bg-emerald-600 rounded px-3 py-1.5">ຮັບເຄື່ອງ</button>@endif
            @elseif ($record->status === 'received')
                @if ($editable)<button wire:click="$set('showClose', true)" class="text-white bg-emerald-700 rounded px-3 py-1.5">ປິດໃບ (invoice + SAP)</button>@endif
            @else
                <span class="text-gray-400">— ບໍ່ມີ action ({{ $record->status }})</span>
            @endif

            @if ($deletable)<span class="ml-auto"></span><button wire:click="openDelete" class="text-red-600 border border-red-200 rounded px-3 py-1.5 hover:bg-red-50">🗑 ລຶບ</button>@endif
        </div>

        {{-- modals --}}
        @if ($showCancel)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="bg-white rounded-lg p-5 w-full max-w-sm space-y-3">
                <h3 class="font-medium text-gray-800">ຍົກເລີກໃບເບີກ</h3>
                <textarea wire:model="cancelReason" rows="2" placeholder="ເຫດຜົນ…" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                <div class="flex justify-end gap-2"><button wire:click="$set('showCancel', false)" class="border rounded px-3 py-1.5 text-sm">ປິດ</button><button wire:click="cancel" class="bg-red-600 text-white rounded px-3 py-1.5 text-sm">ຢືນຢັນ</button></div>
            </div></div>
        @endif
        @if ($showReject)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="bg-white rounded-lg p-5 w-full max-w-sm space-y-3">
                <h3 class="font-medium text-gray-800">ປະຕິເສດ</h3>
                @error('action')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <textarea wire:model="rejectReason" rows="3" placeholder="ເຫດຜົນ…" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                <div class="flex justify-end gap-2"><button wire:click="$set('showReject', false)" class="border rounded px-3 py-1.5 text-sm">ປິດ</button><button wire:click="reject" class="bg-red-600 text-white rounded px-3 py-1.5 text-sm">ຢືນຢັນປະຕິເສດ</button></div>
            </div></div>
        @endif
        @if ($showDispatch)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="bg-white rounded-lg p-5 w-full max-w-sm space-y-3">
                <h3 class="font-medium text-gray-800">Dispatch</h3>
                <div><label class="block text-sm text-gray-600 mb-1">ວິທີສົ່ງ</label><select wire:model="deliveryMethod" class="w-full rounded-md border-gray-300 text-sm"><option value="supplier_delivery">Supplier delivery</option><option value="pickup_at_supplier">Pickup at supplier</option></select></div>
                <div><label class="block text-sm text-gray-600 mb-1">ວັນທີຄາດສົ່ງ</label><input type="date" wire:model="plannedDeliveryDate" class="w-full rounded-md border-gray-300 text-sm" /></div>
                <div class="flex justify-end gap-2"><button wire:click="$set('showDispatch', false)" class="border rounded px-3 py-1.5 text-sm">ປິດ</button><button wire:click="doDispatch" class="bg-amber-600 text-white rounded px-3 py-1.5 text-sm">ຢືນຢັນ</button></div>
            </div></div>
        @endif
        @if ($showReceive)
            <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4"><div class="bg-white w-full md:max-w-lg rounded-t-lg md:rounded-lg p-5 space-y-3 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between">
                    <h3 class="font-medium text-gray-800">ຮັບເຄື່ອງ</h3>
                    <button wire:click="receiveAll" class="text-xs text-emerald-700 border border-emerald-200 rounded-md px-2.5 py-1 hover:bg-emerald-50">✓ ຮັບໝົດ ທຸກລາຍການ</button>
                </div>
                <p class="text-xs text-gray-400">ໃສ່ ຈຳນວນທີ່ຮັບ ແຕ່ລະລາຍການ (partial ໄດ້ — ໃບຈະ "received" ເມື່ອ ຮັບຄົບ ທຸກລາຍການ).</p>
                <div class="border border-gray-100 rounded-lg divide-y divide-gray-50">
                    @foreach ($record->items as $it)
                        <div class="flex items-center gap-3 px-3 py-2">
                            <div class="flex-1 min-w-0">
                                <div class="text-sm text-gray-800 truncate">{{ $it->description }}</div>
                                <div class="text-xs text-gray-400">ສັ່ງ {{ $it->quantity }}{{ $it->unit ? ' '.$it->unit : '' }} · ຮັບແລ້ວ {{ $it->received_qty }}</div>
                            </div>
                            @php $remain = max(0, $it->quantity - $it->received_qty); @endphp
                            <input type="number" min="0" max="{{ $remain }}" wire:model="rcQty.{{ $it->id }}" @disabled($remain === 0) class="w-20 rounded-md border-gray-300 text-sm {{ $remain === 0 ? 'bg-gray-100' : '' }}" />
                        </div>
                    @endforeach
                </div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="rcInvoice" class="rounded border-gray-300 text-sky-600" /> ໄດ້ຮັບ invoice</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="rcDeliveryNote" class="rounded border-gray-300 text-sky-600" /> ໄດ້ຮັບ delivery note</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="rcSpecMatch" class="rounded border-gray-300 text-sky-600" /> ກົງ spec</label>
                @error('action')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <div class="flex justify-end gap-2"><button wire:click="$set('showReceive', false)" class="border rounded px-3 py-1.5 text-sm">ປິດ</button><button wire:click="confirmReceipt" class="bg-emerald-600 text-white rounded px-3 py-1.5 text-sm">ຢືນຢັນຮັບ</button></div>
            </div></div>
        @endif
        @if ($showClose)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="bg-white rounded-lg p-5 w-full max-w-sm space-y-3">
                <h3 class="font-medium text-gray-800">ປິດໃບເບີກ</h3>
                <div><label class="block text-sm text-gray-600 mb-1">ເລກ Invoice <span class="text-red-500">*</span></label><input type="text" wire:model="invoiceNumber" class="w-full rounded-md border-gray-300 text-sm" />@error('invoiceNumber')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="block text-sm text-gray-600 mb-1">SAP reference <span class="text-red-500">*</span></label><input type="text" wire:model="sapReference" class="w-full rounded-md border-gray-300 text-sm" />@error('sapReference')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="block text-sm text-gray-600 mb-1">SAP PR/FR status</label><select wire:model="sapStatus" class="w-full rounded-md border-gray-300 text-sm"><option value="">— ບໍ່ລະບຸ —</option>@foreach (\App\Models\MaterialRequest::sapStatuses() as $k => $lbl)<option value="{{ $k }}">{{ $lbl }}</option>@endforeach</select>@error('sapStatus')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div class="flex justify-end gap-2"><button wire:click="$set('showClose', false)" class="border rounded px-3 py-1.5 text-sm">ປິດ</button><button wire:click="close" class="bg-emerald-700 text-white rounded px-3 py-1.5 text-sm">ຢືນຢັນປິດ</button></div>
            </div></div>
        @endif
        @if ($showDelete)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="bg-white rounded-lg p-5 w-full max-w-sm space-y-3">
                <h3 class="font-medium text-red-700">🗑 ລຶບໃບເບີກ</h3>
                <p class="text-xs text-gray-500">ຍ້າຍໄປ Deleted Log (ກູ້ຄືນໄດ້).</p>
                <textarea wire:model="deleteReason" rows="3" placeholder="ເຫດຜົນ…" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                @error('deleteReason')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <div class="flex justify-end gap-2"><button wire:click="$set('showDelete', false)" class="border rounded px-3 py-1.5 text-sm">ປິດ</button><button wire:click="deleteRecord" class="bg-red-600 text-white rounded px-3 py-1.5 text-sm">ຢືນຢັນລຶບ</button></div>
            </div></div>
        @endif
    </div>
</div>
