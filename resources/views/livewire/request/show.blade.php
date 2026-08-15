@php
    $statusMeta = fn ($s) => match ($s) {
        'draft' => ['DRAFT', 'bg-gray-100 text-gray-600'],
        'submitted' => ['SUBMITTED', 'bg-blue-50 text-blue-700 ring-1 ring-blue-200'],
        'approved' => ['APPROVED', 'bg-sky-50 text-sky-700 ring-1 ring-sky-200'],
        'validated' => ['VALIDATED', 'bg-cyan-50 text-cyan-700 ring-1 ring-cyan-200'],
        'dispatched' => ['DISPATCHED', 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'],
        'received' => ['RECEIVED', 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'],
        'completed' => ['COMPLETED', 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200'],
        'rejected' => ['REJECTED', 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'],
        'cancelled' => ['CANCELLED', 'bg-gray-100 text-gray-400'],
        default => [strtoupper($s), 'bg-gray-100 text-gray-600'],
    };
    [$slbl, $scls] = $statusMeta($record->status);
    $strip = match ($record->status) {
        'completed', 'received' => 'from-emerald-500 to-teal-500',
        'validated' => 'from-cyan-500 to-sky-500',
        'approved' => 'from-sky-500 to-cyan-500',
        'submitted' => 'from-blue-500 to-indigo-500',
        'dispatched' => 'from-amber-500 to-orange-500',
        'rejected' => 'from-rose-500 to-red-500',
        'cancelled' => 'from-gray-300 to-gray-400',
        default => 'from-amber-500 to-orange-500',
    };
    $dt = fn ($d) => $d?->format('d/m/Y H:i') ?? '—';
    $fileTh = 'text-[11px] uppercase tracking-wide text-slate-500 font-semibold px-3 py-2';
    $td = 'px-3 py-2';
@endphp

<div class="pb-10">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        <x-page-subheader :back="route('request')" back-label="ລາຍການ">
            <x-slot:actions>
                <a href="{{ route('request.pdf', $record) }}" class="inline-flex items-center gap-1.5 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg px-3 py-1.5 hover:bg-gray-50 transition">📄 PDF</a>
            </x-slot>
        </x-page-subheader>

        {{-- ══ HERO ══ --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="h-1.5 bg-gradient-to-r {{ $strip }}"></div>
            <div class="p-5 flex items-start gap-4 flex-wrap">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $strip }} text-white flex items-center justify-center text-2xl shadow-sm shrink-0">📝</div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-mono text-xl font-bold text-gray-900 tracking-tight">{{ $record->request_number }}</span>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $scls }}">{{ $slbl }}</span>
                        @if ($record->sapStatusLabel())<span class="text-xs font-medium px-2 py-1 rounded-full bg-violet-50 text-violet-700 ring-1 ring-violet-200">SAP: {{ $record->sapStatusLabel() }}</span>@endif
                    </div>
                    <div class="text-gray-500 text-sm mt-1">{{ $record->requester_name }}@if ($record->unit) · {{ $record->unit->name }}@endif</div>
                    <div class="flex items-center gap-x-4 gap-y-1 flex-wrap mt-2.5 text-xs text-gray-500">
                        <span class="inline-flex items-center gap-1">📝 {{ $record->items->count() }} ລາຍການ · Qty {{ $record->items->sum('quantity') }}</span>
                        <span class="inline-flex items-center gap-1 font-semibold text-gray-700">💰 {{ number_format($record->grand_total, 2) }} {{ $record->currency }}</span>
                        @if ($record->supplier)<span class="inline-flex items-center gap-1">🏬 {{ $record->supplier->name }}</span>@endif
                    </div>
                </div>
            </div>
        </div>

        @if (session('ok'))<div class="text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-2.5">{{ session('ok') }}</div>@endif
        @error('action')<div class="text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-xl px-4 py-2.5">{{ $message }}</div>@enderror
        @if ($record->status === 'rejected' && $record->reject_reason)<div class="text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-xl px-4 py-2.5">ປະຕິເສດ: {{ $record->reject_reason }}</div>@endif
        @if ($record->status === 'cancelled' && $record->cancel_reason)<div class="text-sm text-gray-600 bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5">ຍົກເລີກ: {{ $record->cancel_reason }}</div>@endif

        {{-- ① general info --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 bg-gray-50/70 border-b border-gray-100 flex items-center gap-2.5"><span class="w-6 h-6 rounded-md bg-slate-100 text-slate-600 flex items-center justify-center text-xs">📋</span><h3 class="text-sm font-semibold text-gray-700">ໃບເບີກວັດສະດຸ <span class="text-gray-400 font-normal">/ Material Request</span></h3></div>
            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div><div class="text-xs text-gray-400">ຜູ້ເບີກ / Requester</div><div class="text-gray-800 mt-0.5">{{ $record->requester_name }}</div></div>
                <div><div class="text-xs text-gray-400">ໜ່ວຍ / Unit</div><div class="text-gray-800 mt-0.5">{{ $record->unit?->name ?? '—' }}</div></div>
                <div class="sm:col-span-2"><div class="text-xs text-gray-400">ຈຸດປະສົງ / Purpose</div><div class="text-gray-800 mt-0.5">{{ $record->purpose ?? '—' }}</div></div>
                <div><div class="text-xs text-gray-400">ປະເພດ / Type</div><div class="text-gray-800 mt-0.5">{{ $record->request_type ?? '—' }}@if ($record->wo_e_form) · {{ $record->wo_e_form }}@endif</div></div>
                <div><div class="text-xs text-gray-400">Supplier</div><div class="text-gray-800 mt-0.5">{{ $record->supplier?->name ?? '—' }}</div></div>
                <div><div class="text-xs text-gray-400">Approver</div><div class="text-gray-800 mt-0.5">{{ $record->approver_name ?? '—' }}@if ($record->approved_at)<span class="text-xs text-gray-400"> · {{ $record->approved_at->format('d/m/Y H:i') }}</span>@endif</div></div>
                <div><div class="text-xs text-gray-400">Warehouse</div><div class="text-gray-800 mt-0.5">{{ $record->warehouse_staff_name ?? '—' }}@if ($record->validated_at)<span class="text-xs text-gray-400"> · {{ $record->validated_at->format('d/m/Y H:i') }}</span>@endif</div></div>
            </div>
        </div>

        {{-- ② items --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 bg-gray-50/70 border-b border-gray-100 flex items-center gap-2.5"><span class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center text-xs">📝</span><h3 class="text-sm font-semibold text-gray-700">ລາຍການ <span class="text-gray-400 font-normal">({{ $record->items->count() }})</span></h3></div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-gray-200"><tr>
                        <th class="{{ $fileTh }} text-left w-8">#</th><th class="{{ $fileTh }} text-left">ລາຍລະອຽດ</th><th class="{{ $fileTh }} text-center">ໜ່ວຍ</th><th class="{{ $fileTh }} text-center">ຈຳນວນ</th><th class="{{ $fileTh }} text-right">ລາຄາ</th><th class="{{ $fileTh }} text-right">ລວມ</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($record->items as $idx => $it)
                            <tr>
                                <td class="{{ $td }} align-top text-gray-500">{{ $idx + 1 }}</td>
                                <td class="{{ $td }} align-top text-gray-800">{{ $it->description }}@if ($it->material_nbr)<span class="font-mono text-xs text-gray-400"> · {{ $it->material_nbr }}</span>@endif
                                    @if (! empty($it->shop_prices))<div class="text-xs text-gray-400 mt-0.5">ປຽບທຽບ: @foreach ($it->shop_prices as $o){{ $o['supplier_name'] }} {{ number_format($o['unit_price'], 2) }}@if (! $loop->last) · @endif @endforeach</div>@endif
                                </td>
                                <td class="{{ $td }} align-top text-center text-gray-600">{{ $it->unit ?? '—' }}</td>
                                <td class="{{ $td }} align-top text-center tabular-nums font-medium text-gray-800">{{ $it->quantity }}</td>
                                <td class="{{ $td }} align-top text-right tabular-nums text-gray-600">{{ number_format($it->unit_price, 2) }}</td>
                                <td class="{{ $td }} align-top text-right tabular-nums text-gray-800">{{ number_format($it->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="text-gray-700 border-t border-gray-200">
                        <tr><td class="{{ $td }} text-right text-gray-500" colspan="5">ລວມ (net)</td><td class="{{ $td }} text-right tabular-nums">{{ number_format($record->total, 2) }}</td></tr>
                        <tr><td class="{{ $td }} text-right text-gray-500" colspan="5">VAT {{ $record->vat_enabled ? rtrim(rtrim(number_format($record->vat_rate, 2), '0'), '.').'%' : '(ປິດ)' }}</td><td class="{{ $td }} text-right tabular-nums">{{ number_format($record->vat_amount, 2) }}</td></tr>
                        <tr class="font-bold bg-amber-50/60 border-t-2 border-amber-200"><td class="{{ $td }} text-right" colspan="5">ລວມທັງໝົດ / Grand total</td><td class="{{ $td }} text-right tabular-nums text-amber-700">{{ number_format($record->grand_total, 2) }} {{ $record->currency }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- receipt / close info --}}
        @if (in_array($record->status, ['received', 'completed'], true))
            <div class="bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-200 rounded-xl p-4 text-xs text-gray-600">
                <div class="flex items-center gap-2 text-sm font-semibold text-emerald-800 mb-1.5">✓ ຮັບ ເຄື່ອງ</div>
                ຮັບເຄື່ອງ: {{ $record->received_at?->format('d/m/Y H:i') }} · {{ $record->received_by_name }}
                · invoice {{ $record->invoice_received ? '✓' : '✗' }} · delivery-note {{ $record->delivery_note_received ? '✓' : '✗' }} · spec {{ $record->spec_match ? '✓' : '✗' }}
                @if ($record->status === 'completed')<div class="mt-1">ປິດ: invoice #{{ $record->invoice_number }} · SAP {{ $record->sap_reference }}@if ($record->sapStatusLabel()) · <span class="font-medium">{{ $record->sapStatusLabel() }}</span>@endif</div>@endif
            </div>
        @endif

        {{-- history --}}
        @if ($record->history->count())
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-4 py-2.5 bg-gray-50/70 border-b border-gray-100 flex items-center gap-2.5"><span class="w-6 h-6 rounded-md bg-slate-100 text-slate-600 flex items-center justify-center text-xs">🕘</span><h3 class="text-sm font-semibold text-gray-700">ປະຫວັດ / History</h3></div>
                <ol class="p-4 space-y-2">
                    @foreach ($record->history as $h)
                        <li class="flex gap-2.5 text-xs"><span class="w-2 h-2 rounded-full bg-amber-400 mt-1 shrink-0"></span><span class="text-gray-600"><span class="font-mono font-medium text-gray-800">{{ $h->status }}</span> · {{ $h->user_name }} · {{ $h->created_at?->format('d/m/Y H:i') }}@if ($h->comment) — {{ $h->comment }}@endif</span></li>
                    @endforeach
                </ol>
            </div>
        @endif

        {{-- actions --}}
        <div class="bg-white/95 backdrop-blur rounded-xl border border-gray-200 px-5 py-3 flex flex-wrap gap-2 text-sm items-center sticky bottom-4 z-20 shadow-lg">
            @if ($record->status === 'draft')
                @if ($isRequester || $editable)<button wire:click="submit" class="inline-flex items-center gap-1.5 text-white bg-indigo-600 font-medium rounded-lg px-4 py-2 hover:bg-indigo-700 transition shadow-sm">📤 ສົ່ງຄຳຂໍ</button>@endif
                <button wire:click="$set('showCancel', true)" class="text-gray-600 border border-gray-200 rounded-lg px-3 py-2 hover:bg-gray-50">ຍົກເລີກ</button>
            @elseif ($record->status === 'submitted')
                @if ($canApprove)<button wire:click="approve" class="inline-flex items-center gap-1.5 text-white bg-sky-600 font-medium rounded-lg px-4 py-2 hover:bg-sky-700 transition shadow-sm">✓ ອະນຸມັດ</button><button wire:click="$set('showReject', true)" class="text-rose-700 border border-rose-200 bg-rose-50 rounded-lg px-3 py-2 hover:bg-rose-100 transition">ປະຕິເສດ</button>@endif
                <button wire:click="$set('showCancel', true)" class="text-gray-600 border border-gray-200 rounded-lg px-3 py-2 hover:bg-gray-50">ຍົກເລີກ</button>
            @elseif ($record->status === 'approved')
                @if ($editable)<button wire:click="validateRequest" class="inline-flex items-center gap-1.5 text-white bg-cyan-600 font-medium rounded-lg px-4 py-2 hover:bg-cyan-700 transition shadow-sm">✓ Validate (warehouse)</button><button wire:click="$set('showReject', true)" class="text-rose-700 border border-rose-200 bg-rose-50 rounded-lg px-3 py-2 hover:bg-rose-100 transition">ປະຕິເສດ</button>@endif
                <button wire:click="$set('showCancel', true)" class="text-gray-600 border border-gray-200 rounded-lg px-3 py-2 hover:bg-gray-50">ຍົກເລີກ</button>
            @elseif ($record->status === 'validated')
                @if ($editable)<button wire:click="openDispatch" class="inline-flex items-center gap-1.5 text-white bg-amber-600 font-medium rounded-lg px-4 py-2 hover:bg-amber-700 transition shadow-sm">🚚 Dispatch</button>@endif
                <button wire:click="$set('showCancel', true)" class="text-gray-600 border border-gray-200 rounded-lg px-3 py-2 hover:bg-gray-50">ຍົກເລີກ</button>
            @elseif ($record->status === 'dispatched')
                @if ($editable)<button wire:click="openReceive" class="inline-flex items-center gap-1.5 text-white bg-emerald-600 font-medium rounded-lg px-4 py-2 hover:bg-emerald-700 transition shadow-sm">📥 ຮັບເຄື່ອງ</button>@endif
            @elseif ($record->status === 'received')
                @if ($editable)<button wire:click="$set('showClose', true)" class="inline-flex items-center gap-1.5 text-white bg-emerald-700 font-medium rounded-lg px-4 py-2 hover:bg-emerald-800 transition shadow-sm">✓ ປິດໃບ (invoice + SAP)</button>@endif
            @else
                <span class="text-gray-400">— ບໍ່ມີ action ({{ $record->status }})</span>
            @endif

            @if ($deletable)<span class="ml-auto"></span><button wire:click="openDelete" class="text-rose-600 border border-rose-200 rounded-lg px-3 py-2 hover:bg-rose-50 transition">🗑 ລຶບ</button>@endif
        </div>

        {{-- modals --}}
        @if ($showCancel)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="bg-white rounded-2xl p-5 w-full max-w-sm space-y-3 shadow-xl">
                <h3 class="font-semibold text-gray-800">ຍົກເລີກໃບເບີກ</h3>
                <textarea wire:model="cancelReason" rows="2" placeholder="ເຫດຜົນ…" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                <div class="flex justify-end gap-2"><button wire:click="$set('showCancel', false)" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm hover:bg-gray-50">ປິດ</button><button wire:click="cancel" class="bg-rose-600 text-white rounded-lg px-3 py-1.5 text-sm hover:bg-rose-700">ຢືນຢັນ</button></div>
            </div></div>
        @endif
        @if ($showReject)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="bg-white rounded-2xl p-5 w-full max-w-sm space-y-3 shadow-xl">
                <h3 class="font-semibold text-gray-800">ປະຕິເສດ</h3>
                @error('action')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                <textarea wire:model="rejectReason" rows="3" placeholder="ເຫດຜົນ…" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                <div class="flex justify-end gap-2"><button wire:click="$set('showReject', false)" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm hover:bg-gray-50">ປິດ</button><button wire:click="reject" class="bg-rose-600 text-white rounded-lg px-3 py-1.5 text-sm hover:bg-rose-700">ຢືນຢັນປະຕິເສດ</button></div>
            </div></div>
        @endif
        @if ($showDispatch)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="bg-white rounded-2xl p-5 w-full max-w-sm space-y-3 shadow-xl">
                <h3 class="font-semibold text-gray-800">Dispatch</h3>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">ວິທີສົ່ງ</label><select wire:model="deliveryMethod" class="w-full rounded-lg border-gray-300 text-sm"><option value="supplier_delivery">Supplier delivery</option><option value="pickup_at_supplier">Pickup at supplier</option></select></div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">ວັນທີຄາດສົ່ງ</label><input type="date" wire:model="plannedDeliveryDate" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                <div class="flex justify-end gap-2"><button wire:click="$set('showDispatch', false)" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm hover:bg-gray-50">ປິດ</button><button wire:click="doDispatch" class="bg-amber-600 text-white rounded-lg px-3 py-1.5 text-sm hover:bg-amber-700">ຢືນຢັນ</button></div>
            </div></div>
        @endif
        @if ($showReceive)
            <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4"><div class="bg-white w-full md:max-w-lg rounded-t-2xl md:rounded-2xl p-5 space-y-3 max-h-[90vh] overflow-y-auto shadow-xl">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800">ຮັບເຄື່ອງ</h3>
                    <button wire:click="receiveAll" class="text-xs font-medium text-emerald-700 border border-emerald-200 rounded-lg px-2.5 py-1 hover:bg-emerald-50">✓ ຮັບໝົດ ທຸກລາຍການ</button>
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
                            <input type="number" min="0" max="{{ $remain }}" wire:model="rcQty.{{ $it->id }}" @disabled($remain === 0) class="w-20 rounded-lg border-gray-300 text-sm {{ $remain === 0 ? 'bg-gray-100' : '' }}" />
                        </div>
                    @endforeach
                </div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="rcInvoice" class="rounded border-gray-300 text-emerald-600" /> ໄດ້ຮັບ invoice</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="rcDeliveryNote" class="rounded border-gray-300 text-emerald-600" /> ໄດ້ຮັບ delivery note</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="rcSpecMatch" class="rounded border-gray-300 text-emerald-600" /> ກົງ spec</label>
                @error('action')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                <div class="flex justify-end gap-2"><button wire:click="$set('showReceive', false)" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm hover:bg-gray-50">ປິດ</button><button wire:click="confirmReceipt" class="bg-emerald-600 text-white rounded-lg px-3 py-1.5 text-sm hover:bg-emerald-700">ຢືນຢັນຮັບ</button></div>
            </div></div>
        @endif
        @if ($showClose)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="bg-white rounded-2xl p-5 w-full max-w-sm space-y-3 shadow-xl">
                <h3 class="font-semibold text-gray-800">ປິດໃບເບີກ</h3>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">ເລກ Invoice <span class="text-rose-500">*</span></label><input type="text" wire:model="invoiceNumber" class="w-full rounded-lg border-gray-300 text-sm" />@error('invoiceNumber')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">SAP reference <span class="text-rose-500">*</span></label><input type="text" wire:model="sapReference" class="w-full rounded-lg border-gray-300 text-sm" />@error('sapReference')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">SAP PR/FR status</label><select wire:model="sapStatus" class="w-full rounded-lg border-gray-300 text-sm"><option value="">— ບໍ່ລະບຸ —</option>@foreach (\App\Models\MaterialRequest::sapStatuses() as $k => $lbl)<option value="{{ $k }}">{{ $lbl }}</option>@endforeach</select>@error('sapStatus')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                <div class="flex justify-end gap-2"><button wire:click="$set('showClose', false)" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm hover:bg-gray-50">ປິດ</button><button wire:click="close" class="bg-emerald-700 text-white rounded-lg px-3 py-1.5 text-sm hover:bg-emerald-800">ຢືນຢັນປິດ</button></div>
            </div></div>
        @endif
        @if ($showDelete)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="bg-white rounded-2xl p-5 w-full max-w-sm space-y-3 shadow-xl">
                <h3 class="font-semibold text-rose-700">🗑 ລຶບໃບເບີກ</h3>
                <p class="text-xs text-gray-500">ຍ້າຍໄປ Deleted Log (ກູ້ຄືນໄດ້).</p>
                <textarea wire:model="deleteReason" rows="3" placeholder="ເຫດຜົນ…" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                @error('deleteReason')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                <div class="flex justify-end gap-2"><button wire:click="$set('showDelete', false)" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm hover:bg-gray-50">ປິດ</button><button wire:click="deleteRecord" class="bg-rose-600 text-white rounded-lg px-3 py-1.5 text-sm hover:bg-rose-700">ຢືນຢັນລຶບ</button></div>
            </div></div>
        @endif
    </div>
</div>
