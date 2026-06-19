@php
    $badge = fn ($s) => match ($s) {
        'draft' => 'bg-gray-100 text-gray-600', 'acknowledged' => 'bg-blue-100 text-blue-700',
        'approved' => 'bg-sky-100 text-sky-700', 'active' => 'bg-indigo-100 text-indigo-700',
        'overdue' => 'bg-red-100 text-red-700', 'returned' => 'bg-emerald-100 text-emerald-700',
        'cancelled' => 'bg-gray-100 text-gray-400', default => 'bg-gray-100 text-gray-600',
    };
    $typeLabel = match ($record->borrow_type) {
        'new_inventory' => 'ຢືມເຄື່ອງໃໝ່ ຢູ່ໃນ Inventory Item', 'tools_equipment' => 'ເຄື່ອງມື/ອຸປະກອນ (ບໍ່ມີໃນລະບົບ)',
        'deposited_tools' => 'ເຄື່ອງຝາກຄືນ', 'others' => 'ອື່ນໆ', default => $record->borrow_type,
    };
    $st = $record->display_status;
    $od = $record->days_left !== null && $record->days_left < 0 ? abs($record->days_left) : 0;
    $itemNames = $record->items->pluck('item_name')->implode(', ');
    $reminding = "{$record->borrower_name} ກະລຸນາສົ່ງ {$record->items->count()} ລາຍການ ({$itemNames}) ກັບคืน ໃນວັນທີ ".($record->planned_return_date?->format('M d, Y') ?? '—').'.';
@endphp

<div class="pb-6">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        {{-- header (X close → back) --}}
        <div class="flex items-center justify-between">
            <div><h2 class="text-xl font-bold text-gray-800">Borrowing Record Details</h2>
                <div class="text-sm text-gray-400"><span class="font-mono">{{ $record->request_number }}</span> · <span class="text-xs px-2 py-0.5 rounded-full {{ $badge($st) }}">{{ strtoupper($st) }}</span></div></div>
            <a href="{{ route('borrow') }}" wire:navigate class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</a>
        </div>

        @if (session('ok'))<div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2">{{ session('ok') }}</div>@endif
        @error('action')<div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">{{ $message }}</div>@enderror

        <div class="bg-white rounded-lg border border-gray-100 p-6 grid md:grid-cols-2 gap-x-10 gap-y-8 text-sm">
            {{-- ① USER INFORMATION --}}
            <div>
                <div class="text-xs font-bold text-indigo-600 tracking-wide mb-3">1. USER INFORMATION</div>
                <div class="grid grid-cols-2 gap-y-3 gap-x-4">
                    <div><div class="text-xs text-gray-400">NAME</div><div class="text-gray-800">{{ $record->borrower_name }}</div></div>
                    <div><div class="text-xs text-gray-400">EMAIL</div><div class="text-gray-800">{{ $record->borrower_email }}</div></div>
                    <div><div class="text-xs text-gray-400">UNIT</div><div class="text-gray-800">{{ $record->unit?->name ?? '—' }}</div></div>
                    <div><div class="text-xs text-gray-400">DEPARTMENT</div><div class="text-gray-800">{{ $record->department?->name ?? '—' }}</div></div>
                </div>
            </div>

            {{-- ③ PURPOSES & APPROVAL --}}
            <div>
                <div class="text-xs font-bold text-indigo-600 tracking-wide mb-3">3. PURPOSES & APPROVAL</div>
                <div class="grid grid-cols-2 gap-y-3 gap-x-4">
                    <div class="col-span-2"><div class="text-xs text-gray-400">BORROWING TYPE</div><div class="text-gray-800">{{ $typeLabel }}</div></div>
                    @if ($record->purpose)<div class="col-span-2"><div class="text-xs text-gray-400">PURPOSE</div><div class="text-gray-800">{{ $record->purpose }}</div></div>@endif
                    <div><div class="text-xs text-gray-400">BORROW DATE</div><div class="text-gray-800">{{ $record->borrow_date?->format('M d, Y') }}</div></div>
                    <div><div class="text-xs text-gray-400">PLANNED RETURN</div><div class="text-gray-800">{{ $record->planned_return_date?->format('M d, Y') }}</div></div>
                    <div><div class="text-xs text-gray-400">MANAGER ACKNOWLEDGE</div><div class="text-gray-800">{{ $record->acknowledge_name ?? ($record->requires_acknowledge ? 'ລໍຖ້າ' : '—') }}</div></div>
                    <div><div class="text-xs text-gray-400">WH APPROVER</div><div class="text-gray-800">{{ $record->approver_name ?? '—' }}</div></div>
                </div>
            </div>

            {{-- ② MATERIAL INFORMATION --}}
            <div>
                <div class="text-xs font-bold text-indigo-600 tracking-wide mb-3">2. MATERIAL INFORMATION</div>
                <div class="space-y-3">
                    @foreach ($record->items as $it)
                        @php $photo = $it->inventoryItem?->primaryPhoto?->first(); $take = $it->photos->where('kind', 'take'); @endphp
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="grid grid-cols-2 gap-2">
                                <div><div class="text-xs text-gray-400">MATERIAL ID</div><div class="font-mono text-gray-800">{{ $it->inventoryItem?->slug ?? '—' }}</div></div>
                                <div><div class="text-xs text-gray-400">QUANTITY</div><div class="text-gray-800">{{ $it->qty }}</div></div>
                            </div>
                            <div class="mt-2"><div class="text-xs text-gray-400">DESCRIPTION</div><div class="text-gray-700">{{ $it->item_name }}</div></div>
                            @if ($photo || $take->count())
                                <div class="mt-3 bg-indigo-50/40 rounded-lg p-2">
                                    <div class="text-xs font-semibold text-indigo-600 mb-1">📦 BORROWING PHOTOS</div>
                                    <div class="flex gap-1.5 flex-wrap">
                                        @if ($photo)<img src="{{ $photo->url }}" alt="" class="w-14 h-14 rounded-lg object-cover border border-gray-200" />@endif
                                        @foreach ($take as $p)<img src="{{ $p->url }}" alt="" class="w-14 h-14 rounded-lg object-cover border border-gray-200" />@endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="space-y-8">
                {{-- ⑤ FOLLOW UP / TRACKING --}}
                <div>
                    <div class="text-xs font-bold text-indigo-600 tracking-wide mb-3">5. FOLLOW UP / TRACKING</div>
                    <div><div class="text-xs text-gray-400">OVERDUE DAYS</div><div class="font-semibold {{ $od > 0 ? 'text-red-600' : 'text-gray-800' }}">{{ $od }} Days</div></div>
                    <div class="mt-2"><div class="text-xs text-gray-400">REMINDING MESSAGE</div><div class="text-gray-500 italic text-xs leading-relaxed">{{ $reminding }}</div></div>
                </div>

                {{-- ⑥ TIME EXTENSION REQUEST --}}
                <div>
                    <div class="text-xs font-bold text-indigo-600 tracking-wide mb-3">6. TIME EXTENSION REQUEST</div>
                    @if ($record->extension_status === 'pending')
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 space-y-1 text-xs">
                            <div class="font-medium text-amber-800">⏳ ຂໍຂະຫຍາຍ → {{ $record->extension_proposed_date?->format('M d, Y') }}</div>
                            @if ($record->extension_reason)<div class="text-gray-600">{{ $record->extension_reason }}</div>@endif
                            @if (in_array($record->status, ['active', 'overdue']))
                                <div class="flex gap-2 pt-1">
                                    <button wire:click="approveExtension" class="text-white bg-emerald-600 rounded px-2 py-1">Approve</button>
                                    <button wire:click="rejectExtension" class="text-white bg-red-600 rounded px-2 py-1">Reject</button>
                                </div>
                            @endif
                        </div>
                    @elseif ($record->extension_status === 'approved')
                        <div class="bg-emerald-50 rounded-lg p-3 text-xs text-emerald-700">✓ ຂະຫຍາຍແລ້ວ → {{ $record->extension_proposed_date?->format('M d, Y') }}</div>
                    @elseif ($record->extension_status === 'rejected')
                        <div class="bg-gray-50 rounded-lg p-3 text-xs text-gray-500">✗ ປະຕິເສດການຂະຫຍາຍ</div>
                    @else
                        <div class="bg-gray-50 rounded-lg p-3 text-center text-gray-400 text-xs">
                            No extension requested
                            @if (in_array($record->status, ['active', 'overdue']))<div class="mt-2"><button wire:click="openExtension" class="text-indigo-600 border border-indigo-200 rounded px-2 py-1">ຂໍຂະຫຍາຍເວລາ</button></div>@endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- ④ TAKING CONFIRMATION --}}
            <div>
                <div class="text-xs font-bold text-indigo-600 tracking-wide mb-3">4. TAKING CONFIRMATION</div>
                <div class="bg-gray-50 rounded-lg p-3"><div class="text-xs text-gray-400">STATUS</div><div class="font-semibold {{ $record->taken_at ? 'text-emerald-600' : 'text-gray-500' }}">{{ $record->taken_at ? 'Confirmed' : 'Pending' }}</div>
                    @if ($record->taken_at)<div class="text-xs text-gray-400 mt-1">{{ $record->taken_at?->format('M d, Y H:i') }} · {{ $record->warehouse_staff_name }}</div>@endif</div>
            </div>

            {{-- ⑦ RETURNING AND CLOSURE --}}
            <div>
                <div class="text-xs font-bold text-indigo-600 tracking-wide mb-3">7. RETURNING AND CLOSURE</div>
                @if ($record->returned_at)
                    @php $rqty = $record->items->sum(fn ($i) => $i->return_qty ?? 0); $cond = $record->items->pluck('condition_on_return')->filter()->implode(' · ') ?: 'Good'; @endphp
                    <div class="bg-emerald-50 rounded-lg p-3 space-y-2">
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div><div class="text-gray-400">FINAL RETURN DATE</div><div class="text-emerald-800 font-medium">{{ $record->returned_at?->format('M d, Y H:i') }}</div></div>
                            <div><div class="text-gray-400">RETURN QTY</div><div class="text-emerald-800 font-medium">{{ $rqty }}</div></div>
                            <div class="col-span-2"><div class="text-gray-400">CONDITION</div><div class="text-emerald-800 font-medium">{{ $cond }}</div></div>
                        </div>
                        <div class="border-t border-emerald-200 pt-2">
                            <div class="text-center text-xs font-semibold text-emerald-700 mb-1">CONDITION COMPARISON (BEFORE vs AFTER)</div>
                            <div class="grid grid-cols-2 gap-2">
                                <div><div class="text-[10px] text-center text-gray-500 mb-1">BEFORE (BORROWED)</div><div class="flex gap-1 justify-center flex-wrap">@foreach ($record->items as $it)@foreach ($it->photos->where('kind', 'take') as $p)<img src="{{ $p->url }}" alt="" class="w-12 h-12 rounded object-cover border" />@endforeach @endforeach</div></div>
                                <div><div class="text-[10px] text-center text-gray-500 mb-1">AFTER (RETURNED)</div><div class="flex gap-1 justify-center flex-wrap">@php $any = false; @endphp @foreach ($record->items as $it)@foreach ($it->photos->where('kind', 'return') as $p)<img src="{{ $p->url }}" alt="" class="w-12 h-12 rounded object-cover border" />@php $any = true; @endphp @endforeach @endforeach @if (! $any)<span class="text-xs text-gray-400">No photos</span>@endif</div></div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-gray-50 rounded-lg p-3 text-center text-gray-400 text-xs">ຍັງບໍ່ໄດ້ສົ່ງคืน</div>
                @endif
                @if ($record->cancel_reason)<div class="text-red-600 text-xs mt-2">ເຫດຍົກເລີກ: {{ $record->cancel_reason }}</div>@endif
            </div>
        </div>

        {{-- actions --}}
        <div class="bg-white rounded-lg border border-gray-100 px-5 py-3 flex flex-wrap gap-2 text-sm items-center">
            <span class="text-gray-400 mr-1">Actions:</span>
            @if ($record->status === 'draft')
                <button wire:click="submit" class="text-white bg-indigo-600 rounded px-3 py-1.5">ສົ່ງຂໍອະນຸມັດ</button>
                <button wire:click="$set('showCancel', true)" class="border rounded px-3 py-1.5">ຍົກເລີກ</button>
            @elseif ($record->status === 'acknowledged')
                @if ($steps['acknowledge'] && ! $record->acknowledged_at)<button wire:click="acknowledge" class="text-white bg-blue-600 rounded px-3 py-1.5">Acknowledge</button>@endif
                @if ($steps['approve'])<button wire:click="approve" class="text-white bg-sky-600 rounded px-3 py-1.5">Approve</button>@endif
                <button wire:click="$set('showCancel', true)" class="border rounded px-3 py-1.5">Reject/ຍົກເລີກ</button>
            @elseif ($record->status === 'approved')
                <button wire:click="openTake" class="text-white bg-emerald-600 rounded px-3 py-1.5">ມອບເຄື່ອງ (confirmTake)</button>
                <button wire:click="$set('showCancel', true)" class="border rounded px-3 py-1.5">ຍົກເລີກ</button>
            @elseif (in_array($record->status, ['active', 'overdue']))
                <button wire:click="openReturn" class="text-white bg-sky-600 rounded px-3 py-1.5">ຮັບคืน (confirmReturn)</button>
            @else
                <span class="text-gray-400">— ບໍ່ມີ action ({{ $record->status }})</span>
            @endif
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

        @php $fileCls = 'block w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-2 file:rounded file:border-0 file:bg-sky-50 file:text-sky-700'; @endphp

        {{-- confirmTake modal --}}
        @if ($showTake)
            <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4">
                <div class="bg-white w-full md:max-w-lg rounded-t-lg md:rounded-lg p-5 space-y-3 max-h-[90vh] overflow-y-auto">
                    <h3 class="text-lg font-medium text-gray-800">ມອບເຄື່ອງ — ຖ່າຍຮູບສະພາບ (ບັງຄັບ)</h3>
                    @error('takePhotos')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    @foreach ($record->items as $it)
                        <div class="border border-gray-200 rounded-md p-3 space-y-2">
                            <div class="text-sm font-medium text-gray-700">{{ $it->item_name }} <span class="text-gray-400">×{{ $it->qty }}</span></div>
                            <input type="file" wire:model="takePhotos.{{ $it->id }}" multiple accept="image/*" capture="environment" class="{{ $fileCls }}" />
                            <div wire:loading wire:target="takePhotos.{{ $it->id }}" class="text-xs text-gray-400">ກຳລັງອັບ…</div>
                            @if (! empty($takePhotos[$it->id]))<div class="flex gap-1 flex-wrap">@foreach ($takePhotos[$it->id] as $f)@if ($f->isPreviewable())<img src="{{ $f->temporaryUrl() }}" alt="" class="w-12 h-12 rounded object-cover border border-sky-200" />@endif @endforeach</div>@endif
                            <textarea wire:model="takeCondition.{{ $it->id }}" rows="1" placeholder="ໝາຍເຫດສະພາບ (optional)…" class="w-full rounded-md border-gray-300 text-xs"></textarea>
                        </div>
                    @endforeach
                    <div class="flex justify-end gap-2 pt-2">
                        <button wire:click="$set('showTake', false)" class="border border-gray-300 rounded px-4 py-2 text-sm">ປິດ</button>
                        <button wire:click="confirmTake" wire:loading.attr="disabled" wire:target="confirmTake,takePhotos" class="bg-emerald-600 text-white rounded px-4 py-2 text-sm disabled:opacity-50">ຢືนยันມອບ</button>
                    </div>
                </div>
            </div>
        @endif

        {{-- confirmReturn modal --}}
        @if ($showReturn)
            <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4">
                <div class="bg-white w-full md:max-w-lg rounded-t-lg md:rounded-lg p-5 space-y-3 max-h-[90vh] overflow-y-auto">
                    <h3 class="text-lg font-medium text-gray-800">ຮັບคืน — ຈຳນວນ + ຖ່າຍຮູບ (ບັງຄັບ)</h3>
                    @error('returnPhotos')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    @foreach ($record->items as $it)
                        <div class="border border-gray-200 rounded-md p-3 space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-medium text-gray-700">{{ $it->item_name }} <span class="text-gray-400">/ ຢືມ {{ $it->qty }}</span></div>
                                <label class="text-xs text-gray-500 flex items-center gap-1">ຄືน <input type="number" min="0" max="{{ $it->qty }}" wire:model="returnQty.{{ $it->id }}" class="w-16 rounded-md border-gray-300 text-xs" /></label>
                            </div>
                            <input type="file" wire:model="returnPhotos.{{ $it->id }}" multiple accept="image/*" capture="environment" class="{{ $fileCls }}" />
                            <div wire:loading wire:target="returnPhotos.{{ $it->id }}" class="text-xs text-gray-400">ກຳລັງອັບ…</div>
                            @if (! empty($returnPhotos[$it->id]))<div class="flex gap-1 flex-wrap">@foreach ($returnPhotos[$it->id] as $f)@if ($f->isPreviewable())<img src="{{ $f->temporaryUrl() }}" alt="" class="w-12 h-12 rounded object-cover border border-sky-200" />@endif @endforeach</div>@endif
                            <textarea wire:model="returnCondition.{{ $it->id }}" rows="1" placeholder="ໝາຍເຫດສະພາບ (optional)…" class="w-full rounded-md border-gray-300 text-xs"></textarea>
                        </div>
                    @endforeach
                    <div class="flex justify-end gap-2 pt-2">
                        <button wire:click="$set('showReturn', false)" class="border border-gray-300 rounded px-4 py-2 text-sm">ປິດ</button>
                        <button wire:click="confirmReturn" wire:loading.attr="disabled" wire:target="confirmReturn,returnPhotos" class="bg-sky-600 text-white rounded px-4 py-2 text-sm disabled:opacity-50">ຢືนยันຮັບคืน</button>
                    </div>
                </div>
            </div>
        @endif

        {{-- extension request modal --}}
        @if ($showExtension)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="bg-white rounded-lg p-5 w-full max-w-sm space-y-3">
                    <h3 class="font-medium text-gray-800">ຂໍຂະຫຍາຍເວລາ</h3>
                    @error('action')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    <div><label class="block text-sm text-gray-600 mb-1">ວັນທີສົ່ງໃໝ່</label><input type="date" wire:model="extProposedDate" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div><label class="block text-sm text-gray-600 mb-1">ເຫດຜົນ</label><textarea wire:model="extReason" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea></div>
                    <div class="flex justify-end gap-2"><button wire:click="$set('showExtension', false)" class="border rounded px-3 py-1.5 text-sm">ປິດ</button><button wire:click="requestExtension" class="bg-indigo-600 text-white rounded px-3 py-1.5 text-sm">ສົ່ງຄຳຂໍ</button></div>
                </div>
            </div>
        @endif
    </div>
</div>
