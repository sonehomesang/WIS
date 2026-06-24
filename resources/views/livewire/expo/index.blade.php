@php
    $statusMeta = fn ($s) => match ($s) {
        'finalized' => ['ສຳເລັດແລ້ວ', 'bg-emerald-100 text-emerald-800'],
        default => ['ຮ່າງ', 'bg-amber-100 text-amber-700'],
    };
@endphp

<div class="pb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="sticky top-16 z-20 bg-gray-100 flex flex-col gap-2 py-3 lg:flex-row lg:items-center lg:justify-between lg:gap-3 lg:flex-nowrap">
            <div class="flex flex-wrap items-center gap-2 min-w-0">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ EXP/ຊື່ງານ/ປະເທດ…" class="w-56 rounded-md border-gray-300 shadow-sm text-sm" />
                <select wire:model.live="statusFilter" class="w-36 rounded-md border-gray-300 text-sm">
                    <option value="">ທຸກ ສະຖານະ</option><option value="draft">ຮ່າງ</option><option value="finalized">ສຳເລັດແລ້ວ</option>
                </select>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if ($canManageDeleted)<button wire:click="toggleDeleted" class="text-sm rounded-md px-2.5 py-2 min-h-[40px] border whitespace-nowrap {{ $showDeleted ? 'bg-red-600 text-white border-red-600' : 'text-red-700 bg-red-50 border-red-200 hover:bg-red-100' }}">🗑 {{ $showDeleted ? 'ກັບคืน' : 'Deleted' }}</button>@endif
                @can('expo.create')<a href="{{ route('expo.create') }}" wire:navigate class="text-sm text-white bg-indigo-600 rounded-md px-2.5 py-2 min-h-[40px] inline-flex items-center hover:bg-indigo-700 whitespace-nowrap">+ ສ້າງ Expo</a>@endcan
            </div>
        </div>

        @include('partials._status-chips', ['chips' => $chips, 'current' => $statusFilter])

        <p class="text-sm text-gray-500 mb-2">{{ number_format($records->total()) }} records</p>
        @if (session('ok'))<div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 mb-2">{{ session('ok') }}</div>@endif

        <div class="hidden md:block bg-white border border-gray-100 rounded-lg">
            <table class="w-full text-sm">
                <thead class="sticky top-[116px] z-10 bg-gray-100 text-gray-700 border-b border-gray-200 shadow-sm">
                    <tr>
                        <th class="text-left font-semibold px-4 py-2 whitespace-nowrap">ໄອດີ <span class="text-gray-400">(EXP)</span></th>
                        <th class="text-left font-semibold px-4 py-2 w-full">ຊື່ງານ</th>
                        <th class="text-left font-semibold px-4 py-2">ສະຖານທີ່</th>
                        <th class="text-left font-semibold px-4 py-2 whitespace-nowrap">ວັນທີ</th>
                        <th class="text-left font-semibold px-4 py-2">ບໍລິສັດ/ຜູ້ໄປ</th>
                        <th class="text-left font-semibold px-4 py-2 whitespace-nowrap">ສະຖานะ</th>
                        <th class="text-left font-semibold px-4 py-2 whitespace-nowrap">ລາຍລະອຽດ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($records as $r)
                        @php [$lbl, $cls] = $statusMeta($r->status); @endphp
                        <tr wire:key="exp-{{ $r->id }}" class="hover:bg-gray-50">
                            <td class="px-4 py-2 align-top whitespace-nowrap"><a href="{{ route('expo.show', $r) }}" wire:navigate class="font-mono text-sm text-indigo-600 hover:underline">{{ $r->expo_number }}</a></td>
                            <td class="px-4 py-2 align-top w-full"><div class="font-medium text-gray-800">{{ $r->title }}</div><div class="text-xs text-gray-400">{{ Str::limit($r->topic, 40) }}</div></td>
                            <td class="px-4 py-2 align-top text-xs text-gray-600">{{ collect([$r->city, $r->country])->filter()->implode(', ') ?: '—' }}</td>
                            <td class="px-4 py-2 align-top text-xs whitespace-nowrap">{{ $r->start_date?->format('d/m/Y') }}@if ($r->end_date)–{{ $r->end_date->format('d/m/Y') }}@endif</td>
                            <td class="px-4 py-2 align-top text-xs text-gray-600">{{ $r->companies_count }} ບໍລິສັດ · {{ $r->attendees_count }} ຄົน</td>
                            <td class="px-4 py-2 align-top whitespace-nowrap"><span class="inline-flex items-center gap-1 text-xs font-medium rounded-full px-2.5 py-1 {{ $cls }}">{{ $lbl }}</span></td>
                            <td class="px-4 py-2 align-top whitespace-nowrap">
                                @if ($showDeleted)
                                    <button wire:click="restore({{ $r->id }})" wire:confirm="ກູ້คืน?" class="text-xs text-emerald-700 border border-emerald-300 rounded-md px-3 py-1.5 hover:bg-emerald-50">↩ ກູ້คืน</button>
                                @else
                                    <a href="{{ route('expo.show', $r) }}" wire:navigate class="text-xs text-gray-700 border border-gray-300 rounded-md px-3 py-1.5 hover:bg-gray-50 inline-block">View</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">ຍັງບໍ່ມີ Expo</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="md:hidden space-y-2">
            @forelse ($records as $r)
                @php [$lbl, $cls] = $statusMeta($r->status); $tag = $showDeleted ? 'div' : 'a'; @endphp
                <{{ $tag }} @if (! $showDeleted) href="{{ route('expo.show', $r) }}" wire:navigate @endif wire:key="mexp-{{ $r->id }}" class="block bg-white border border-gray-100 rounded-lg p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0"><div class="font-mono text-xs text-indigo-600">{{ $r->expo_number }}</div><div class="font-semibold text-gray-800">{{ $r->title }}</div><div class="text-xs text-gray-500">{{ collect([$r->city, $r->country])->filter()->implode(', ') }} · {{ $r->start_date?->format('d/m/Y') }}</div></div>
                        <span class="text-xs font-medium rounded-full px-2 py-0.5 {{ $cls }} shrink-0">{{ $lbl }}</span>
                    </div>
                    @if ($showDeleted)<div class="mt-2 text-right"><button wire:click="restore({{ $r->id }})" wire:confirm="ກູ້คืน?" class="text-xs text-emerald-700 border border-emerald-300 rounded-md px-3 py-1.5">↩ ກູ້คืน</button></div>@endif
                </{{ $tag }}>
            @empty
                <div class="text-center text-gray-400 py-6">ຍັງບໍ່ມີ Expo</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $records->links() }}</div>
    </div>
</div>
