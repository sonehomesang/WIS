@php
    $badge = fn ($s) => match ($s) {
        'draft' => 'bg-gray-100 text-gray-600',
        'in_review', 'committee_review', 'technical_review', 'manager_review', 'executive_review' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
        'approved' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200',
        'disposed' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
        'rejected' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
        'cancelled' => 'bg-gray-100 text-gray-400',
        default => 'bg-gray-100 text-gray-600',
    };
    // ໃບ ຍັງ ດຳເນີນ ຢູ່ (ຮ່າງ/ກຳລັງ ຮັບຮອງ/ອະນຸມັດ) = ແກ້ໄຂ ໄດ້ · ສຳເລັດ ແລ້ວ (ຈຳໜ່າຍ/ຍົກເລີກ/ຕີ ກັບ) = ລັອກ
    $canEdit = fn ($r) => ! in_array($r->status, ['disposed', 'cancelled', 'rejected'])
        && (auth()->user()->can('disposal.edit') || $r->prepared_by_user_id === auth()->id() || auth()->user()->is_super_admin);
@endphp

<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="sticky top-16 z-30 bg-gray-100/95 backdrop-blur">
            <div class="flex flex-col gap-3 py-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3">
                    <span class="w-11 h-11 rounded-xl bg-gradient-to-br from-rose-400 to-red-500 text-white flex items-center justify-center text-xl shadow-sm shrink-0">🗑️</span>
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">ຈຳໜ່າຍ ເຄື່ອງ <span class="text-gray-400 text-sm font-normal">· Disposal</span></h2>
                        <p class="text-sm text-gray-400">{{ number_format($records->total()) }} ໃບ</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔎</span>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ DS/ຫົວຂໍ້/ຜູ້ເຮັດ…" class="w-52 pl-8 rounded-lg border-gray-300 text-sm" />
                    </div>
                    <select wire:model.live="statusFilter" class="w-36 rounded-lg border-gray-300 text-sm">
                        <option value="">ທຸກ ສະຖານະ</option>
                        @foreach ($statusLabels as $k => $lbl)<option value="{{ $k }}">{{ $lbl }}</option>@endforeach
                    </select>
                    <a href="{{ route('disposal.summary') }}" wire:navigate class="text-sm text-gray-600 bg-white border border-gray-200 rounded-lg px-3 py-2 min-h-[40px] inline-flex items-center hover:bg-gray-50 transition whitespace-nowrap">📊 ລິສ ລວມ</a>
                    @if ($canManageDeleted)<button wire:click="toggleDeleted" class="text-sm rounded-lg px-3 py-2 min-h-[40px] border transition whitespace-nowrap {{ $showDeleted ? 'bg-rose-600 text-white border-rose-600' : 'text-rose-700 bg-rose-50 border-rose-200 hover:bg-rose-100' }}">🗑 {{ $showDeleted ? 'ກັບຄືນ' : 'Deleted' }}</button>@endif
                    @can('disposal.create')<a href="{{ route('disposal.create') }}" wire:navigate class="text-sm font-medium text-white bg-indigo-600 rounded-lg px-3.5 py-2 min-h-[40px] inline-flex items-center gap-1 hover:bg-indigo-700 transition shadow-sm whitespace-nowrap">+ ຈຳໜ່າຍ</a>@endcan
                </div>
            </div>
        </div>

        @if (session('ok'))<div class="text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-2.5 mb-3">{{ session('ok') }}</div>@endif

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-auto max-h-[calc(100vh-14rem)]">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 z-10 bg-slate-50 text-slate-500 border-b border-gray-200">
                        <tr class="text-[11px] uppercase tracking-wide">
                            <th class="text-left font-semibold px-4 py-2.5 whitespace-nowrap">ໄອດີ (DS)</th>
                            <th class="text-left font-semibold px-4 py-2.5 w-full">ເຄື່ອງ (Items)</th>
                            <th class="text-left font-semibold px-4 py-2.5 whitespace-nowrap">ຈຳນວນ</th>
                            <th class="text-left font-semibold px-4 py-2.5 whitespace-nowrap">ຜູ້ ເຮັດລິສ</th>
                            <th class="text-left font-semibold px-4 py-2.5 whitespace-nowrap">ວັນທີ</th>
                            <th class="text-left font-semibold px-4 py-2.5 whitespace-nowrap">ສະຖານະ</th>
                            <th class="text-right font-semibold px-4 py-2.5 whitespace-nowrap">ຈັດການ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($records as $r)
                            <tr wire:key="ds-{{ $r->id }}" class="hover:bg-sky-50/40 transition">
                                <td class="px-4 py-2.5 align-top whitespace-nowrap"><a href="{{ route('disposal.show', $r) }}" wire:navigate class="font-mono font-medium text-indigo-600 hover:underline">{{ $r->request_number }}</a></td>
                                <td class="px-4 py-2.5 align-top w-full">
                                    @php $fi = $r->items->first(); $ph = $fi->photos[0] ?? null; @endphp
                                    <div class="flex gap-2.5">
                                        @if ($ph)<img src="{{ \Illuminate\Support\Facades\Storage::url($ph) }}" alt="" class="w-10 h-10 rounded-lg object-cover border border-gray-200 shrink-0" />
                                        @else<div class="w-10 h-10 rounded-lg bg-gray-50 border border-gray-200 shrink-0 flex items-center justify-center text-gray-300 text-lg">🗑️</div>@endif
                                        <div class="min-w-0">
                                            <div class="font-medium text-gray-800 truncate max-w-sm">{{ $fi?->item_name ?: ($r->title ?: '—') }}@if ($r->items_count > 1) <span class="text-gray-400 text-xs font-normal">+{{ $r->items_count - 1 }}</span>@endif</div>
                                            <div class="text-xs text-gray-400 truncate max-w-sm flex items-center gap-1.5">
                                                @if ($fi?->asset_code)<span class="font-mono bg-gray-50 border border-gray-200 rounded px-1 py-0.5 text-gray-500">{{ $fi->asset_code }}</span>@endif
                                                @if ($r->department)<span>{{ $r->department->name }}</span>@elseif ($r->title && $fi)<span>{{ Str::limit($r->title, 30) }}</span>@endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-2.5 align-top whitespace-nowrap text-gray-700"><span class="font-semibold tabular-nums">{{ $r->items->sum('qty') }}</span> <span class="text-xs text-gray-400">({{ $r->items_count }} ລາຍການ)</span></td>
                                <td class="px-4 py-2.5 align-top whitespace-nowrap text-gray-600">{{ $r->prepared_by_name ?? '—' }}</td>
                                <td class="px-4 py-2.5 align-top whitespace-nowrap text-gray-500 text-xs tabular-nums">{{ $r->created_at?->format('d/m/Y') }}</td>
                                <td class="px-4 py-2.5 align-top whitespace-nowrap"><span class="inline-flex text-xs font-semibold rounded-full px-2.5 py-1 {{ $badge($r->status) }}">{{ $statusLabels[$r->status] ?? $r->status }}</span></td>
                                <td class="px-4 py-2.5 align-top whitespace-nowrap text-right">
                                    @if ($showDeleted)
                                        <button wire:click="restore({{ $r->id }})" class="text-xs font-medium text-emerald-700 border border-emerald-200 rounded-lg px-3 py-1.5 hover:bg-emerald-50 transition">↩ ກູ້ຄືນ</button>
                                        @if ($r->deleted_reason)<div class="text-xs text-gray-400 mt-1 max-w-[12rem] truncate ml-auto" title="{{ $r->deleted_reason }}">{{ $r->deleted_reason }}</div>@endif
                                    @else
                                        @if ($canEdit($r))<a href="{{ route('disposal.show', [$r, 'edit' => 1]) }}" wire:navigate class="text-xs font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-1.5 hover:bg-amber-100 transition inline-block mr-1">✏️ ແກ້ໄຂ</a>@endif
                                        <a href="{{ route('disposal.show', $r) }}" wire:navigate class="text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg px-3 py-1.5 hover:bg-gray-50 transition inline-block">ເບິ່ງ</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-14 text-center text-gray-400">
                                <div class="text-4xl mb-2">🗑️</div>
                                {{ $showDeleted ? 'ບໍ່ ມີ ໃບ ຈຳໜ່າຍ ທີ່ ຖືກ ລຶບ' : 'ຍັງ ບໍ່ ມີ ໃບ ຈຳໜ່າຍ — ກົດ “+ ຈຳໜ່າຍ” ເພື່ອ ສ້າງ' }}
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @include('partials._delete-modal', ['title' => 'ລຶບ ໃບ ຈຳໜ່າຍ ນີ້?', 'subtitle' => $this->deletingRecord?->request_number])

        <div class="mt-4">{{ $records->links() }}</div>
    </div>
</div>
