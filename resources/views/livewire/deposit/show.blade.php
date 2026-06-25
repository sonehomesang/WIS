@php
    $statusMeta = fn ($s) => match ($s) {
        'draft' => ['DRAFT', 'bg-gray-100 text-gray-600'],
        'submitted' => ['SUBMITTED', 'bg-blue-50 text-blue-700'],
        'accepted' => ['ACCEPTED', 'bg-cyan-50 text-cyan-700'],
        'stored' => ['STORED', 'bg-emerald-50 text-emerald-700'],
        'needs_fix' => ['NEEDS FIX', 'bg-amber-50 text-amber-700'],
        'claimed' => ['CLAIMED', 'bg-emerald-100 text-emerald-800'],
        'cancelled' => ['CANCELLED', 'bg-gray-100 text-gray-400'],
        default => [strtoupper($s), 'bg-gray-100 text-gray-600'],
    };
    [$slbl, $scls] = $statusMeta($record->status);
    $lbl = 'px-3 py-2 text-gray-500 text-xs bg-gray-50 border border-gray-200';
    $bd = 'px-3 py-2 border border-gray-200';
    $kindTag = ['deposit' => 'D', 'stored' => 'S', 'claim' => 'C'];
    $fileCls = 'block w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-2 file:rounded file:border-0 file:bg-sky-50 file:text-sky-700';
@endphp

<div class="pb-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        {{-- header --}}
        <div class="flex items-center justify-between gap-2">
            <a href="{{ route('deposit') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">← ກັບໄປ list</a>
            <div class="flex items-center gap-2">
                @if ($editable)<button wire:click="openEdit" class="text-sm text-white bg-amber-600 rounded-md px-3 py-1.5 hover:bg-amber-700">✏️ ແກ້ໄຂ</button>@endif
                <a href="{{ route('deposit.pdf', $record) }}" class="text-sm text-gray-700 border border-gray-300 rounded-md px-3 py-1.5 hover:bg-gray-50">📄 PDF</a>
            </div>
        </div>

        @if (session('ok'))<div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2">{{ session('ok') }}</div>@endif
        @error('action')<div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">{{ $message }}</div>@enderror

        @if ($record->status === 'needs_fix' && $record->needs_fix_reason)
            <div class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">⚠ ຕ້ອງปັບແກ້: {{ $record->needs_fix_reason }}</div>
        @endif
        @if ($record->status === 'cancelled' && $record->cancel_reason)
            <div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">ຍົກເລີກ: {{ $record->cancel_reason }}</div>
        @endif

        {{-- document --}}
        <div id="deposit-detail" class="bg-white border border-gray-200 rounded-lg p-5 space-y-4 text-sm">
            <div class="flex items-start justify-between border-b border-gray-200 pb-3">
                <div>
                    <div class="text-xs text-gray-400">NAM THEUN 2 — WAREHOUSE INFORMATION SYSTEM</div>
                    <div class="text-lg font-bold text-gray-800">ໃບຝາກເຄື່ອງ / DEPOSIT RECORD</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ $record->request_type === 'pre_request' ? 'Pre-request (ສົ່ງລ່ວງໜ້າ)' : 'Walk-in (ນຳມາແລ້ວ)' }}</div>
                </div>
                <div class="text-right">
                    <div class="font-mono font-bold text-gray-800">{{ $record->request_number }}</div>
                    <span class="inline-flex items-center gap-1 text-xs font-medium rounded-full px-2.5 py-1 mt-1 {{ $scls }}">{{ $slbl }}</span>
                </div>
            </div>

            {{-- ① general --}}
            <table class="w-full border-collapse">
                <tbody>
                    <tr><td class="{{ $lbl }}" style="width:25%">ເຈົ້າຂອງ / Owner</td><td class="{{ $bd }}" style="width:25%">{{ $record->owner_name }}</td><td class="{{ $lbl }}" style="width:25%">Email</td><td class="{{ $bd }}" style="width:25%">{{ $record->owner_email }}</td></tr>
                    <tr><td class="{{ $lbl }}">ໜ່ວຍງານ / Unit</td><td class="{{ $bd }}">{{ $record->unit?->name ?? '—' }}</td><td class="{{ $lbl }}">ພະແນก / Dept</td><td class="{{ $bd }}">{{ $record->department?->name ?? '—' }}</td></tr>
                    <tr><td class="{{ $lbl }}">ປະເພດ / Category</td><td class="{{ $bd }}">{{ $record->item_category ?? '—' }}</td><td class="{{ $lbl }}">ແຫຼ່ງທີ່ມາ / Origin</td><td class="{{ $bd }}">{{ $record->origin_source ?? '—' }}</td></tr>
                    <tr><td class="{{ $lbl }}">ວັນທີຝາກ / Deposit</td><td class="{{ $bd }}">{{ $record->deposit_date?->format('d/m/Y') }}</td><td class="{{ $lbl }}">ໄລຍະ / Duration</td><td class="{{ $bd }}">{{ $record->expected_duration ?? '—' }}</td></tr>
                    <tr><td class="{{ $lbl }}">ຄາດເອົາคืน / Exp. claim</td><td class="{{ $bd }}">{{ $record->expected_claim_date?->format('d/m/Y') ?? '—' }}</td><td class="{{ $lbl }}">เอົาคืนจริง / Claimed</td><td class="{{ $bd }}">{{ $record->actual_claim_date?->format('d/m/Y') ?? '—' }}</td></tr>
                    <tr><td class="{{ $lbl }}">ເຫດผົน / Reason</td><td class="{{ $bd }}" colspan="3">{{ $record->deposit_reason ?? '—' }}</td></tr>
                    @if ($record->remark)<tr><td class="{{ $lbl }}">ໝາຍເຫດ / Remark</td><td class="{{ $bd }}" colspan="3">{{ $record->remark }}</td></tr>@endif
                </tbody>
            </table>

            {{-- ② storage (if assigned) --}}
            @if ($record->storage_location || $record->storage_shelf_label || $record->warehouse_instructions)
                <div class="border border-cyan-200 bg-cyan-50/50 rounded-md p-3">
                    <div class="font-semibold text-gray-700 mb-1">ສະຖານທີ່ຈັດເກັບ (Storage Location)</div>
                    <div class="text-gray-700">{{ collect([$record->storage_location, $record->storage_shelf_label])->filter()->implode(' / ') ?: '—' }}</div>
                    @if ($record->warehouse_instructions)<div class="text-xs text-gray-500 mt-1">ຄຳແນະນຳ: {{ $record->warehouse_instructions }}</div>@endif
                    @if ($record->accepted_at)<div class="text-xs text-gray-400 mt-1">ຮັບฝากเมื่อ: {{ $record->accepted_at->format('d/m/Y H:i') }} · {{ $record->warehouse_staff_name }}</div>@endif
                </div>
            @endif

            {{-- ③ items --}}
            <div>
                <div class="text-center bg-gray-200 border border-black/10 p-2 font-bold mb-2">ລາຍການເຄື່ອງ / Items ({{ $record->items->count() }})</div>
                <div class="space-y-2">
                    @foreach ($record->items as $idx => $it)
                        <div class="border border-gray-200 rounded-md p-3">
                            <div class="flex items-center justify-between">
                                <div class="font-medium text-gray-800">#{{ $idx + 1 }} {{ $it->item_name }} <span class="text-gray-400">×{{ $it->qty }}{{ $it->unit ? ' '.$it->unit : '' }}</span></div>
                                @if ($it->estimated_value)<div class="text-xs text-gray-500">{{ number_format($it->estimated_value, 2) }} {{ $it->currency }}</div>@endif
                            </div>
                            @if ($it->description)<div class="text-xs text-gray-500 mt-0.5">{{ $it->description }}</div>@endif
                            <div class="text-xs text-gray-500 mt-0.5">
                                @if ($it->condition_on_deposit)ฝาก: {{ $it->condition_on_deposit }}@endif
                                @if ($it->condition_on_claim) · เอົาคืน: {{ $it->condition_on_claim }}@endif
                            </div>
                            @if ($it->photos->count())
                                <div class="flex gap-1.5 flex-wrap mt-2">
                                    @foreach ($it->photos as $p)
                                        <div class="relative">
                                            <img src="{{ $p->url }}" alt="" class="w-14 h-14 rounded object-cover border border-gray-200" />
                                            <span class="absolute top-0 left-0 bg-black/60 text-white text-[9px] px-1 rounded-br">{{ $kindTag[$p->kind] ?? $p->kind }}</span>
                                            @if ($editable)<button wire:click="removePhoto({{ $p->id }})" wire:confirm="ລຶບຮູບນີ້?" class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-red-600 text-white rounded-full text-[10px] leading-none">×</button>@endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ④ history --}}
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
                @if ($isOwner || $editable)<button wire:click="submit" class="text-white bg-indigo-600 rounded px-3 py-1.5">ສົ່ງຄຳຂໍ</button>@endif
                <button wire:click="$set('showCancel', true)" class="border rounded px-3 py-1.5">ຍົກເລີກ</button>
            @elseif ($record->status === 'submitted')
                @if ($editable)<button wire:click="openAccept" class="text-white bg-emerald-600 rounded px-3 py-1.5">ຮັບຝາກ + ກຳນົດບ່ອນເກັບ</button>@endif
                <button wire:click="$set('showCancel', true)" class="border rounded px-3 py-1.5">ຍົກເລີກ</button>
            @elseif ($record->status === 'accepted')
                @if ($editable)<button wire:click="openStore('confirmStored')" class="text-white bg-emerald-700 rounded px-3 py-1.5">📦 ຢืนยันເກັບເຂົ້າ (ຮູບ)</button>@endif
                <button wire:click="$set('showCancel', true)" class="border rounded px-3 py-1.5">ຍົກເລີກ</button>
            @elseif ($record->status === 'stored')
                @if ($editable)
                    <button wire:click="openClaim" class="text-white bg-indigo-700 rounded px-3 py-1.5">↥ ບັນທຶກการเอົาคืน</button>
                    <button wire:click="openFlag" class="text-amber-700 border border-amber-200 bg-amber-50 rounded px-3 py-1.5">⚠ ແຈ້ງปัญหา</button>
                @endif
            @elseif ($record->status === 'needs_fix')
                @if ($editable)<button wire:click="openStore('confirmFixed')" class="text-white bg-cyan-600 rounded px-3 py-1.5">🔧 ຢืนยันແກ້ແລ້ວ → stored</button>@endif
            @else
                <span class="text-gray-400">— ບໍ່ມີ action ({{ $record->status }})</span>
            @endif

            @if ($deletable)
                <span class="ml-auto"></span>
                <button wire:click="openDelete" class="text-red-600 border border-red-200 rounded px-3 py-1.5 hover:bg-red-50">🗑 ລຶບ</button>
            @endif
        </div>

        {{-- ── modals ── --}}
        @if ($showCancel)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="bg-white rounded-lg p-5 w-full max-w-sm space-y-3">
                    <h3 class="font-medium text-gray-800">ຍົກເລີກການຝາກ</h3>
                    <textarea wire:model="cancelReason" rows="2" placeholder="ເຫດผົน…" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                    <div class="flex justify-end gap-2"><button wire:click="$set('showCancel', false)" class="border rounded px-3 py-1.5 text-sm">ປິດ</button><button wire:click="cancel" class="bg-red-600 text-white rounded px-3 py-1.5 text-sm">ຢືนยันຍົກເລີກ</button></div>
                </div>
            </div>
        @endif

        @if ($showAccept)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="bg-white rounded-lg p-5 w-full max-w-md space-y-3">
                    <h3 class="font-medium text-gray-800">ຮັບຝາກ — ກຳນົດບ່ອນເກັບ</h3>
                    @error('action')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    <div><label class="block text-sm text-gray-600 mb-1">ບ່ອນເກັບ (Location)</label><input type="text" wire:model="afLocation" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div><label class="block text-sm text-gray-600 mb-1">ປ້າຍຊັ້ນວາງ (Shelf label)</label><input type="text" wire:model="afShelf" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <p class="text-xs text-gray-400">* ຕ້ອງມີ ບ່ອນເກັບ ຫຼື ປ້າຍຊັ້ນວາງ ຢ່າງໜ້ອຍ 1 ຢ່າງ</p>
                    <div><label class="block text-sm text-gray-600 mb-1">ຄຳແນະນຳ (Instructions)</label><textarea wire:model="afInstructions" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea></div>
                    <div class="flex justify-end gap-2"><button wire:click="$set('showAccept', false)" class="border rounded px-3 py-1.5 text-sm">ປິດ</button><button wire:click="accept" class="bg-emerald-600 text-white rounded px-3 py-1.5 text-sm">ຢືนยันຮັບฝาก</button></div>
                </div>
            </div>
        @endif

        @if ($showStore)
            <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4">
                <div class="bg-white w-full md:max-w-lg rounded-t-lg md:rounded-lg p-5 space-y-3 max-h-[90vh] overflow-y-auto">
                    <h3 class="text-lg font-medium text-gray-800">{{ $storeMode === 'confirmFixed' ? 'ຢืนยันแก้แล้ว — ຖ່າຍຮູບ' : 'ຢืนยันເກັບເຂົ້າ — ຖ່າຍຮູບ (ບັງຄັບ)' }}</h3>
                    @error('storePhotos')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    @foreach ($record->items as $it)
                        <div class="border border-gray-200 rounded-md p-3 space-y-2">
                            <div class="text-sm font-medium text-gray-700">{{ $it->item_name }} <span class="text-gray-400">×{{ $it->qty }}</span></div>
                            <input type="file" wire:model="storePhotos.{{ $it->id }}" multiple accept="image/*" capture="environment" class="{{ $fileCls }}" />
                            <div wire:loading wire:target="storePhotos.{{ $it->id }}" class="text-xs text-gray-400">ກຳລັງອັບ…</div>
                            @if (! empty($storePhotos[$it->id]))<div class="flex gap-1 flex-wrap">@foreach ($storePhotos[$it->id] as $f)@if ($f->isPreviewable())<img src="{{ $f->temporaryUrl() }}" alt="" class="w-12 h-12 rounded object-cover border border-sky-200" />@endif @endforeach</div>@endif
                        </div>
                    @endforeach
                    <div class="flex justify-end gap-2 pt-2">
                        <button wire:click="$set('showStore', false)" class="border border-gray-300 rounded px-4 py-2 text-sm">ປິດ</button>
                        <button wire:click="confirmStore" wire:loading.attr="disabled" wire:target="confirmStore,storePhotos" class="bg-emerald-700 text-white rounded px-4 py-2 text-sm disabled:opacity-50">ຢືนยัน</button>
                    </div>
                </div>
            </div>
        @endif

        @if ($showFlag)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="bg-white rounded-lg p-5 w-full max-w-sm space-y-3">
                    <h3 class="font-medium text-gray-800">ແຈ້ງปัญหา (needs fix)</h3>
                    @error('action')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    <textarea wire:model="flagReason" rows="3" placeholder="ເຫດผົน / ปัญหา…" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                    <div class="flex justify-end gap-2"><button wire:click="$set('showFlag', false)" class="border rounded px-3 py-1.5 text-sm">ປິດ</button><button wire:click="flagIssue" class="bg-amber-600 text-white rounded px-3 py-1.5 text-sm">ຢืนยัน</button></div>
                </div>
            </div>
        @endif

        @if ($showClaim)
            <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4">
                <div class="bg-white w-full md:max-w-lg rounded-t-lg md:rounded-lg p-5 space-y-3 max-h-[90vh] overflow-y-auto">
                    <h3 class="text-lg font-medium text-gray-800">ບັນທຶກการเอົาคืน — ຖ່າຍຮູບ (ບັງຄັບ)</h3>
                    @error('claimPhotos')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    <div><label class="block text-sm text-gray-600 mb-1">ວັນທີเอົาคืน</label><input type="date" wire:model="claimDate" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    @foreach ($record->items as $it)
                        <div class="border border-gray-200 rounded-md p-3 space-y-2">
                            <div class="text-sm font-medium text-gray-700">{{ $it->item_name }} <span class="text-gray-400">×{{ $it->qty }}</span></div>
                            <input type="file" wire:model="claimPhotos.{{ $it->id }}" multiple accept="image/*" capture="environment" class="{{ $fileCls }}" />
                            <div wire:loading wire:target="claimPhotos.{{ $it->id }}" class="text-xs text-gray-400">ກຳລັງອັບ…</div>
                            @if (! empty($claimPhotos[$it->id]))<div class="flex gap-1 flex-wrap">@foreach ($claimPhotos[$it->id] as $f)@if ($f->isPreviewable())<img src="{{ $f->temporaryUrl() }}" alt="" class="w-12 h-12 rounded object-cover border border-sky-200" />@endif @endforeach</div>@endif
                            <textarea wire:model="claimCondition.{{ $it->id }}" rows="1" placeholder="ສະພາບຕอนเอົาคืน (optional)…" class="w-full rounded-md border-gray-300 text-xs"></textarea>
                        </div>
                    @endforeach
                    <div class="flex justify-end gap-2 pt-2">
                        <button wire:click="$set('showClaim', false)" class="border border-gray-300 rounded px-4 py-2 text-sm">ປິດ</button>
                        <button wire:click="confirmClaim" wire:loading.attr="disabled" wire:target="confirmClaim,claimPhotos" class="bg-indigo-700 text-white rounded px-4 py-2 text-sm disabled:opacity-50">ຢืนยันเอົาคืน</button>
                    </div>
                </div>
            </div>
        @endif

        @if ($showDelete)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="bg-white rounded-lg p-5 w-full max-w-sm space-y-3">
                    <h3 class="font-medium text-red-700">🗑 ລຶບລາຍການຝາກ</h3>
                    <p class="text-xs text-gray-500">ຍ້າຍໄປ Deleted Log (ກູ້คืນໄດ້). ໃສ່ເຫດผົน.</p>
                    <textarea wire:model="deleteReason" rows="3" placeholder="ເຫດผົนการລຶບ…" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                    @error('deleteReason')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    <div class="flex justify-end gap-2"><button wire:click="$set('showDelete', false)" class="border rounded px-3 py-1.5 text-sm">ປິດ</button><button wire:click="deleteRecord" wire:loading.attr="disabled" wire:target="deleteRecord" class="bg-red-600 text-white rounded px-3 py-1.5 text-sm disabled:opacity-50">ຢืนยันລຶບ</button></div>
                </div>
            </div>
        @endif

        @if ($showEdit)
            <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4">
                <div class="bg-white w-full md:max-w-2xl rounded-t-lg md:rounded-lg p-5 space-y-4 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-800">✏️ ແກ້ໄຂ (Admin) — {{ $record->request_number }}</h3>
                        <button wire:click="$set('showEdit', false)" class="text-gray-400 hover:text-gray-700 p-1">✕</button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                        <div><label class="block text-gray-600 mb-1">ເຈົ້າຂອງ</label><input type="text" wire:model="ef.owner_name" class="w-full rounded-md border-gray-300 text-sm" />@error('ef.owner_name')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div><label class="block text-gray-600 mb-1">ປະເພດ</label><input type="text" wire:model="ef.item_category" class="w-full rounded-md border-gray-300 text-sm" /></div>
                        <div><label class="block text-gray-600 mb-1">ແຫຼ່ງທີ່ມາ</label><input type="text" wire:model="ef.origin_source" class="w-full rounded-md border-gray-300 text-sm" /></div>
                        <div><label class="block text-gray-600 mb-1">ໄລຍະເວລາ</label><input type="text" wire:model="ef.expected_duration" class="w-full rounded-md border-gray-300 text-sm" /></div>
                        <div><label class="block text-gray-600 mb-1">ວັນທີຝາກ</label><input type="date" wire:model="ef.deposit_date" class="w-full rounded-md border-gray-300 text-sm" /></div>
                        <div><label class="block text-gray-600 mb-1">ຄາດເອົາคืน</label><input type="date" wire:model="ef.expected_claim_date" class="w-full rounded-md border-gray-300 text-sm" /></div>
                        <div><label class="block text-gray-600 mb-1">ບ່ອນເກັບ</label><input type="text" wire:model="ef.storage_location" class="w-full rounded-md border-gray-300 text-sm" /></div>
                        <div><label class="block text-gray-600 mb-1">ປ້າຍຊັ້ນວາງ</label><input type="text" wire:model="ef.storage_shelf_label" class="w-full rounded-md border-gray-300 text-sm" /></div>
                        <div class="sm:col-span-2"><label class="block text-gray-600 mb-1">ເຫດผົน</label><textarea wire:model="ef.deposit_reason" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea></div>
                        <div class="sm:col-span-2"><label class="block text-gray-600 mb-1">ຄຳແນະນຳ warehouse</label><textarea wire:model="ef.warehouse_instructions" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea></div>
                        <div class="sm:col-span-2"><label class="block text-gray-600 mb-1">ໝາຍເຫດ</label><textarea wire:model="ef.remark" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea></div>
                    </div>

                    <div class="space-y-2">
                        <div class="text-sm font-medium text-gray-700">ລາຍການເຄື່ອງ</div>
                        @foreach ($record->items as $it)
                            <div wire:key="ei-{{ $it->id }}" class="border border-gray-200 rounded-md p-3 grid grid-cols-1 sm:grid-cols-6 gap-2 text-sm">
                                <div class="sm:col-span-3"><label class="block text-xs text-gray-500 mb-1">ຊື່</label><input type="text" wire:model="ei.{{ $it->id }}.item_name" class="w-full rounded-md border-gray-300 text-sm" /></div>
                                <div><label class="block text-xs text-gray-500 mb-1">ຈຳນວນ</label><input type="number" min="1" wire:model="ei.{{ $it->id }}.qty" class="w-full rounded-md border-gray-300 text-sm" /></div>
                                <div><label class="block text-xs text-gray-500 mb-1">ໜ່ວຍ</label><input type="text" wire:model="ei.{{ $it->id }}.unit" class="w-full rounded-md border-gray-300 text-sm" /></div>
                                <div><label class="block text-xs text-gray-500 mb-1">ມູນຄ່າ</label><input type="number" step="0.01" wire:model="ei.{{ $it->id }}.estimated_value" class="w-full rounded-md border-gray-300 text-sm" /></div>
                                <div class="sm:col-span-3"><label class="block text-xs text-gray-500 mb-1">ລາຍລະອຽດ</label><input type="text" wire:model="ei.{{ $it->id }}.description" class="w-full rounded-md border-gray-300 text-sm" /></div>
                                <div class="sm:col-span-2"><label class="block text-xs text-gray-500 mb-1">ສະພາບฝาก</label><input type="text" wire:model="ei.{{ $it->id }}.condition_on_deposit" class="w-full rounded-md border-gray-300 text-sm" /></div>
                                <div><label class="block text-xs text-gray-500 mb-1">ສະກຸນ</label><select wire:model="ei.{{ $it->id }}.currency" class="w-full rounded-md border-gray-300 text-sm"><option value="">—</option><option value="LAK">LAK</option><option value="THB">THB</option><option value="USD">USD</option></select></div>
                                <div class="sm:col-span-6">
                                    <label class="block text-xs text-gray-500 mb-1">ເພີ່ມຮູບ (deposit)</label>
                                    <input type="file" wire:model="ep.deposit.{{ $it->id }}" multiple accept="image/*" class="{{ $fileCls }}" />
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button wire:click="$set('showEdit', false)" class="border border-gray-300 rounded px-4 py-2 text-sm">ຍົກເລີກ</button>
                        <button wire:click="saveEdit" wire:loading.attr="disabled" wire:target="saveEdit,ep" class="bg-amber-600 text-white rounded px-4 py-2 text-sm disabled:opacity-50">ບັນທຶກ</button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
