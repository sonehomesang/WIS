@php
    $badge = fn ($s) => match ($s) {
        'draft' => 'bg-gray-100 text-gray-600',
        'committee_review', 'technical_review', 'manager_review', 'executive_review' => 'bg-amber-50 text-amber-700',
        'approved' => 'bg-sky-50 text-sky-700', 'disposed' => 'bg-emerald-100 text-emerald-800',
        'rejected' => 'bg-red-50 text-red-700', 'cancelled' => 'bg-gray-100 text-gray-400',
        default => 'bg-gray-100 text-gray-600',
    };
    $signs = $record->signoffs->groupBy('role_key');
    $dt = fn ($d) => $d?->format('d/m/Y') ?? '';
@endphp

<div class="pb-10">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        <x-page-subheader :back="route('disposal')" back-label="ລາຍການ" :record="$record->request_number" :status="$record->statusLabel()" :status-class="$badge($record->status)">
            <x-slot:actions>
                @if ($canEdit && ! $editing)<button wire:click="openEdit" class="inline-flex items-center gap-1 text-sm text-amber-700 border border-amber-300 rounded-md px-3 py-1.5 hover:bg-amber-50">✏️ ແກ້ໄຂ</button>@endif
                <a href="{{ route('disposal.pdf', $record) }}" target="_blank" class="inline-flex items-center gap-1 text-sm text-gray-700 border border-gray-300 rounded-md px-3 py-1.5 hover:bg-gray-50">📄 PDF</a>
            </x-slot>
        </x-page-subheader>
        <p class="text-sm text-gray-500 -mt-1">{{ $record->title ?: '—' }}@if ($record->department) · {{ $record->department->name }}@endif · {{ $record->items->count() }} ລາຍການ</p>

        @if (session('ok'))<div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2">{{ session('ok') }}</div>@endif
        @error('action')<div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">{{ $message }}</div>@enderror
        @if ($record->reject_reason && $record->status === 'draft')<div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">↩ ຖືກ ຕີ ກັບ: {{ $record->reject_reason }}</div>@endif

        @if (! $editing)
        {{-- ① ລາຍການ --}}
        <div class="bg-white border border-gray-100 rounded-lg divide-y divide-gray-100">
            @foreach ($record->items as $it)
                <div class="p-4 space-y-2">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="font-medium text-gray-800">{{ $loop->iteration }}. {{ $it->item_name }} <span class="text-gray-400 text-sm">×{{ $it->qty }} {{ $it->unit }}</span></div>
                            <div class="text-xs text-gray-400 font-mono">{{ $it->asset_code ?: '—' }}@if ($it->fixed_asset_no) · {{ $it->fixed_asset_no }}@endif · <span class="uppercase">{{ $it->source_type }}</span></div>
                        </div>
                        <div class="text-right text-xs">
                            <div><span class="text-red-600">{{ $it->reason ?: '—' }}</span>@if ($it->reason_detail) · {{ $it->reason_detail }}@endif</div>
                            <div class="text-gray-500">→ {{ $it->recommendation ?: '—' }}@if ($it->recommendation_detail) · {{ $it->recommendation_detail }}@endif</div>
                        </div>
                    </div>
                    @if ($it->condition)<div class="text-xs text-gray-600">ສະພາບ: {{ $it->condition }}</div>@endif
                    @if (! empty($it->history))
                        <div class="text-xs bg-sky-50/40 border border-sky-100 rounded p-2 space-y-0.5">
                            <div class="text-gray-500 font-medium">ປະຫວັດ ບັນຫາ/ສ້ອມ ({{ count($it->history) }}):</div>
                            @foreach ($it->history as $h)<div class="text-gray-600"><span class="font-mono text-gray-400">{{ $h['date'] ?? '' }}</span> · {{ $h['problem'] ?? '' }} → {{ $h['action'] ?? '' }}</div>@endforeach
                        </div>
                    @endif
                    @if (! empty($it->photos))<div class="flex gap-1 flex-wrap">@foreach ($it->photos as $p)<img src="{{ \Illuminate\Support\Facades\Storage::url($p) }}" class="w-14 h-14 rounded object-cover border border-gray-200" />@endforeach</div>@endif
                    <div><a href="{{ route('disposal.item.pdf', [$record, $it]) }}" target="_blank" class="inline-flex items-center gap-1 text-xs text-gray-700 border border-gray-300 rounded-md px-2.5 py-1 hover:bg-gray-50">📄 ໂປຣຟາຍ / Profile PDF</a></div>
                </div>
            @endforeach
        </div>

        {{-- ② ຂັ້ນ ເຊັນ ຮັບຮອງ --}}
        <div class="bg-white border border-gray-100 rounded-lg p-5">
            <div class="text-sm font-medium text-gray-600 mb-3">ຂັ້ນ ຮັບຮອງ ຈຳໜ່າຍ</div>
            @foreach ($stages as $key => $st)
                @php
                    $rows = $signs[$key] ?? collect();
                    $done = $rows->where('decision', 'approved')->count() > 0;
                    $isCurrent = $currentStage === $key;
                    $last = $loop->last;
                @endphp
                <div class="flex gap-3 items-stretch">
                    <div class="flex flex-col items-center w-7">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs {{ $done ? 'bg-emerald-100 text-emerald-700' : ($isCurrent ? 'bg-sky-600 text-white' : 'bg-gray-100 text-gray-400') }}">{{ $done ? '✓' : $st['order'] }}</div>
                        @unless ($last)<div class="flex-1 w-px border-l-2 border-gray-100 my-1"></div>@endunless
                    </div>
                    <div class="flex-1 pb-4">
                        <div class="text-sm font-medium {{ $isCurrent ? 'text-sky-700' : ($done ? 'text-gray-800' : 'text-gray-400') }}">{{ $st['order'] }} · {{ $st['label'] }}</div>
                        @if ($rows->count())
                            <div class="text-xs text-gray-500 mt-0.5 space-y-0.5">
                                @foreach ($rows as $s)<div>{{ $s->decision === 'rejected' ? '✗ ຕີ ກັບ' : '✓' }} {{ $s->name }}@if ($s->title) <span class="text-gray-400">({{ $s->title }})</span>@endif · {{ $dt($s->signed_at) }}@if ($s->comment) · {{ $s->comment }}@endif</div>@endforeach
                            </div>
                        @elseif ($isCurrent && $canSign)
                            <div class="mt-2 flex gap-2">
                                <button wire:click="openSign" class="text-sm text-white bg-emerald-600 rounded-md px-3 py-1.5 hover:bg-emerald-700">✍ ຢັ້ງຢືນ / ເຊັນ</button>
                                <button wire:click="openReject" class="text-sm text-red-600 border border-red-200 rounded-md px-3 py-1.5 hover:bg-red-50">ຕີ ກັບ</button>
                            </div>
                        @else
                            <div class="text-xs text-gray-400 mt-0.5">{{ $isCurrent ? 'ລໍ ຖ້າ ຜູ້ ມີ ສິດ ເຊັນ' : 'ລໍ ຖ້າ' }}</div>
                        @endif
                    </div>
                </div>
            @endforeach

            @if ($record->status === 'approved')
                <div class="mt-2 rounded-md bg-emerald-50 border border-emerald-200 p-3 flex items-center justify-between gap-2 flex-wrap">
                    <span class="text-sm text-emerald-800">✓ ອະນຸມັດ ຄົບ 5 ຝ່າຍ ແລ້ວ — ພ້ອມ ຈຳໜ່າຍ</span>
                    @can('disposal.activate')<button wire:click="openDispose" class="text-sm text-white bg-emerald-700 rounded-md px-3 py-1.5 hover:bg-emerald-800">ຢືນຢັນ ຈຳໜ່າຍ</button>@endcan
                </div>
            @elseif ($record->status === 'disposed')
                <div class="mt-2 text-sm text-emerald-700">✓ ຈຳໜ່າຍ ແລ້ວ{{ $record->registers_updated_at ? ' · ອັບເດດ ທະບຽນ ຕົ້ນທາງ '.$dt($record->registers_updated_at) : '' }}</div>
            @endif
        </div>

        {{-- actions --}}
        <div class="bg-white border border-gray-100 rounded-lg px-5 py-3 flex flex-wrap gap-2 items-center text-sm sticky bottom-4 shadow-lg">
            <span class="text-gray-400 mr-1">Actions:</span>
            @if ($record->status === 'draft')
                @if (auth()->user()->can('disposal.create'))<button wire:click="submit" class="text-white bg-indigo-600 rounded px-3 py-1.5">ສົ່ງ ຂໍ ອະນຸມັດ</button>@endif
                <button wire:click="openCancel" class="border rounded px-3 py-1.5">ຍົກເລີກ</button>
            @elseif (! in_array($record->status, ['disposed', 'cancelled', 'rejected']))
                <button wire:click="openCancel" class="border rounded px-3 py-1.5">ຍົກເລີກ</button>
            @else
                <span class="text-gray-400">— {{ $record->statusLabel() }}</span>
            @endif
            @can('disposal.delete')<span class="ml-auto"></span><button wire:click="openDelete" class="text-red-600 border border-red-200 rounded px-3 py-1.5 hover:bg-red-50">🗑 ລຶບ</button>@endcan
        </div>
        @else
        {{-- ══ EDIT FORM (ໃບ ຍັງ ດຳເນີນ ຢູ່) ══ --}}
        <div class="bg-amber-50/60 border border-amber-200 rounded-lg px-4 py-2 text-sm text-amber-800">✏️ ໂໝດ ແກ້ໄຂ — ໃບ ນີ້ ຍັງ ດຳເນີນ ຢູ່ ({{ $record->statusLabel() }}) ຈຶ່ງ ແກ້ໄຂ ໄດ້. ເມື່ອ ຈຳໜ່າຍ ສຳເລັດ ຈະ ຖືກ ລັອກ.</div>

        {{-- record header fields --}}
        <div class="bg-white border border-gray-100 rounded-lg p-4 grid gap-3 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="block text-xs text-gray-500 mb-1">ຫົວຂໍ້ / Title</label>
                <input type="text" wire:model="editTitle" class="w-full rounded-md border-gray-300 text-sm" placeholder="ຫົວຂໍ້ ໃບ ຈຳໜ່າຍ (optional)" />
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">ພະແນກ / Department</label>
                <select wire:model="editDept" class="w-full rounded-md border-gray-300 text-sm">
                    <option value="">—</option>
                    @foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">ໝາຍເຫດ / Note</label>
                <input type="text" wire:model="editNote" class="w-full rounded-md border-gray-300 text-sm" placeholder="optional" />
            </div>
        </div>

        <datalist id="edit-uoms">@foreach ($uoms as $u)<option value="{{ $u->name }}"></option>@endforeach</datalist>

        {{-- item rows --}}
        @foreach ($ef as $i => $row)
            <div class="bg-white border border-gray-100 rounded-lg p-4 space-y-3" wire:key="ef-{{ $i }}">
                <div class="flex items-center justify-between gap-2">
                    <div class="text-sm font-medium text-gray-700">ລາຍການ #{{ $i + 1 }}
                        <span class="text-xs font-normal rounded-full px-2 py-0.5 {{ ($row['source_type'] ?? 'new') === 'new' ? 'bg-gray-100 text-gray-500' : 'bg-sky-50 text-sky-700' }}">{{ ($row['source_type'] ?? 'new') === 'new' ? 'ໃໝ່ / new' : strtoupper($row['source_type']) }}</span>
                    </div>
                    <button wire:click="removeEditItem({{ $i }})" class="text-xs text-red-600 border border-red-200 rounded px-2 py-1 hover:bg-red-50">✕ ຖອດ ອອກ</button>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-xs text-gray-500 mb-1">ຊື່ ເຄື່ອງ / Item name <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="ef.{{ $i }}.item_name" class="w-full rounded-md border-gray-300 text-sm" />
                        @error("ef.$i.item_name")<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">ລະຫັດ ເຄື່ອງ / Asset code</label>
                        <input type="text" wire:model="ef.{{ $i }}.asset_code" class="w-full rounded-md border-gray-300 text-sm font-mono" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">ທະບຽນ ຊັບສິນ / Fixed asset no.</label>
                        <input type="text" wire:model="ef.{{ $i }}.fixed_asset_no" class="w-full rounded-md border-gray-300 text-sm font-mono" />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">ຈຳນວນ / Qty <span class="text-red-500">*</span></label>
                            <input type="number" min="1" wire:model="ef.{{ $i }}.qty" class="w-full rounded-md border-gray-300 text-sm" />
                            @error("ef.$i.qty")<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">ໜ່ວຍ / Unit</label>
                            <input type="text" list="edit-uoms" wire:model="ef.{{ $i }}.unit" class="w-full rounded-md border-gray-300 text-sm" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">ສະພາບ / Condition (ຂໍ້ຄວາມ)</label>
                        <input type="text" wire:model="ef.{{ $i }}.condition" class="w-full rounded-md border-gray-300 text-sm" placeholder="ເຊັ່ນ: ຮ່າງກາຍ ແຕກ, ບໍ່ ຕິດ ໄຟ…" />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">ເຫດຜົນ / Reason</label>
                            <select wire:model="ef.{{ $i }}.reason" class="w-full rounded-md border-gray-300 text-sm">
                                <option value="">—</option>
                                @foreach ($reasons as $rz)<option value="{{ $rz }}">{{ $rz }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">ລາຍລະອຽດ ເຫດຜົນ</label>
                            <input type="text" wire:model="ef.{{ $i }}.reason_detail" class="w-full rounded-md border-gray-300 text-sm" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">ຄຳ ແນະນຳ / Recommendation</label>
                            <select wire:model="ef.{{ $i }}.recommendation" class="w-full rounded-md border-gray-300 text-sm">
                                <option value="">—</option>
                                @foreach ($recommendations as $rc)<option value="{{ $rc }}">{{ $rc }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">ລາຍລະອຽດ ຄຳ ແນະນຳ</label>
                            <input type="text" wire:model="ef.{{ $i }}.recommendation_detail" class="w-full rounded-md border-gray-300 text-sm" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">ມູນຄ່າ ຄົງ ເຫຼືອ / Value</label>
                            <input type="number" step="0.01" min="0" wire:model="ef.{{ $i }}.estimated_value" class="w-full rounded-md border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">ສະກຸນ ເງິນ / Currency</label>
                            <select wire:model="ef.{{ $i }}.currency" class="w-full rounded-md border-gray-300 text-sm">
                                <option value="">—</option><option value="LAK">LAK</option><option value="THB">THB</option><option value="USD">USD</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- photos --}}
                <div>
                    <label class="block text-xs text-gray-500 mb-1">ຮູບ ຫຼັກຖານ / Photos</label>
                    <div class="flex flex-wrap gap-2 items-center">
                        @foreach (($row['photos'] ?? []) as $p => $path)
                            <div class="relative" wire:key="ef-{{ $i }}-ph-{{ $p }}">
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($path) }}" class="w-16 h-16 rounded object-cover border border-gray-200" />
                                <button wire:click="removeExistingPhoto({{ $i }}, {{ $p }})" type="button" class="absolute -top-1.5 -right-1.5 bg-red-600 text-white rounded-full w-5 h-5 text-xs leading-none">✕</button>
                            </div>
                        @endforeach
                        @if (isset($newPhotos[$i]))
                            @foreach ($newPhotos[$i] as $f)
                                <img src="{{ $f->temporaryUrl() }}" class="w-16 h-16 rounded object-cover border border-emerald-300" wire:key="ef-{{ $i }}-np-{{ $loop->index }}" />
                            @endforeach
                        @endif
                        <label class="w-16 h-16 rounded border border-dashed border-gray-300 flex items-center justify-center text-gray-400 text-xl cursor-pointer hover:bg-gray-50">
                            +<input type="file" wire:model="newPhotos.{{ $i }}" multiple accept="image/*" class="hidden" />
                        </label>
                    </div>
                    <div wire:loading wire:target="newPhotos.{{ $i }}" class="text-xs text-gray-400 mt-1">ກຳລັງ ອັບ ໂຫຼດ…</div>
                    @error("newPhotos.$i.*")<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        @endforeach

        <button wire:click="addEditItem" class="text-sm text-sky-700 border border-sky-200 rounded-md px-3 py-1.5 hover:bg-sky-50">+ ເພີ່ມ ລາຍການ ໃໝ່</button>

        {{-- save / cancel --}}
        <div class="bg-white border border-gray-100 rounded-lg px-5 py-3 flex flex-wrap gap-2 items-center text-sm sticky bottom-4 shadow-lg">
            <button wire:click="saveEdit" wire:loading.attr="disabled" class="text-white bg-emerald-600 rounded px-4 py-1.5 hover:bg-emerald-700">💾 ບັນທຶກ</button>
            <button wire:click="cancelEdit" class="border rounded px-4 py-1.5 hover:bg-gray-50">ຍົກເລີກ</button>
            <span wire:loading wire:target="saveEdit" class="text-xs text-gray-400">ກຳລັງ ບັນທຶກ…</span>
        </div>
        @endif
    </div>

    {{-- sign modal --}}
    @if ($showSign)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4">
            <div class="bg-white w-full md:max-w-md rounded-t-lg md:rounded-lg p-5 space-y-3 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-medium text-gray-800">✍ ຢັ້ງຢືນ / ເຊັນ — {{ $stages[$currentStage]['label'] ?? '' }}</h3>
                @if ($currentStage === 'committee')
                    <p class="text-xs text-gray-500">ໃສ່ ຄະນະກຳມະການ ຮ່ວມກວດ (ຫຼາຍ ຄົນ ໄດ້)</p>
                    @foreach ($committee as $ci => $cm)
                        <div class="flex gap-2" wire:key="cm-{{ $ci }}">
                            <input type="text" wire:model="committee.{{ $ci }}.name" placeholder="ຊື່ ຄະນະ" class="flex-1 rounded-md border-gray-300 text-sm" />
                            <input type="text" wire:model="committee.{{ $ci }}.title" placeholder="ຕຳແໜ່ງ" class="w-32 rounded-md border-gray-300 text-sm" />
                            @if (count($committee) > 1)<button wire:click="removeCommittee({{ $ci }})" class="text-gray-400 hover:text-red-600 px-1">✕</button>@endif
                        </div>
                    @endforeach
                    <button wire:click="addCommittee" class="text-xs text-sky-700">+ ເພີ່ມ ຄະນະ</button>
                @else
                    <div><label class="block text-sm text-gray-600 mb-1">ຕຳແໜ່ງ (optional)</label><input type="text" wire:model="signTitle" class="w-full rounded-md border-gray-300 text-sm" /></div>
                @endif
                <div><label class="block text-sm text-gray-600 mb-1">ໝາຍເຫດ (optional)</label><textarea wire:model="signComment" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea></div>
                <div class="flex justify-end gap-2"><button wire:click="$set('showSign', false)" class="border rounded px-3 py-1.5 text-sm">ປິດ</button><button wire:click="confirmSign" class="bg-emerald-600 text-white rounded px-3 py-1.5 text-sm">ຢືນຢັນ ເຊັນ</button></div>
            </div>
        </div>
    @endif

    {{-- reject modal --}}
    @if ($showReject)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-lg p-5 w-full max-w-sm space-y-3">
                <h3 class="font-medium text-red-700">ຕີ ກັບ ໃບ ຈຳໜ່າຍ</h3>
                <textarea wire:model="rejectReason" rows="3" placeholder="ເຫດຜົນ ຕີ ກັບ…" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                <div class="flex justify-end gap-2"><button wire:click="$set('showReject', false)" class="border rounded px-3 py-1.5 text-sm">ປິດ</button><button wire:click="confirmReject" class="bg-red-600 text-white rounded px-3 py-1.5 text-sm">ຢືນຢັນ ຕີ ກັບ</button></div>
            </div>
        </div>
    @endif

    {{-- cancel modal --}}
    @if ($showCancel)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-lg p-5 w-full max-w-sm space-y-3">
                <h3 class="font-medium">ຍົກເລີກ ໃບ ຈຳໜ່າຍ</h3>
                <textarea wire:model="cancelReason" rows="2" placeholder="ເຫດຜົນ (optional)" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                <div class="flex justify-end gap-2"><button wire:click="$set('showCancel', false)" class="border rounded px-3 py-1.5 text-sm">ປິດ</button><button wire:click="confirmCancel" class="bg-red-600 text-white rounded px-3 py-1.5 text-sm">ຢືນຢັນ</button></div>
            </div>
        </div>
    @endif

    {{-- dispose (confirm registers) modal --}}
    @if ($showDispose)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-lg p-5 w-full max-w-sm space-y-3">
                <h3 class="font-medium text-gray-800">ຢືນຢັນ ຈຳໜ່າຍ</h3>
                <label class="flex items-start gap-2 text-sm text-gray-700">
                    <input type="checkbox" wire:model="updateRegisters" class="mt-0.5 rounded border-gray-300 text-emerald-600" />
                    <span>ອັບເດດ ທະບຽນ ຕົ້ນທາງ ນຳ <span class="block text-xs text-gray-400">Equipment → retired · Inventory → ปิด ໃຊ້ · Deposit → disposed</span></span>
                </label>
                <div class="flex justify-end gap-2"><button wire:click="$set('showDispose', false)" class="border rounded px-3 py-1.5 text-sm">ປິດ</button><button wire:click="confirmDispose" class="bg-emerald-700 text-white rounded px-3 py-1.5 text-sm">ຢືນຢັນ ຈຳໜ່າຍ</button></div>
            </div>
        </div>
    @endif

    {{-- delete modal --}}
    @if ($showDelete)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-lg p-5 w-full max-w-sm space-y-3">
                <h3 class="font-medium text-red-700">🗑 ລຶບ ໃບ ຈຳໜ່າຍ</h3>
                <p class="text-xs text-gray-500">ຍ້າຍ ໄປ Deleted Log (ກູ້ຄືນ ໄດ້).</p>
                <textarea wire:model="deleteReason" rows="3" placeholder="ເຫດຜົນ ການ ລຶບ…" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                @error('deleteReason')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <div class="flex justify-end gap-2"><button wire:click="$set('showDelete', false)" class="border rounded px-3 py-1.5 text-sm">ປິດ</button><button wire:click="deleteRecord" class="bg-red-600 text-white rounded px-3 py-1.5 text-sm">ຢືນຢັນ ລຶບ</button></div>
            </div>
        </div>
    @endif
</div>
