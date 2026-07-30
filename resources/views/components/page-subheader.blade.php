@props([
    'back' => null,          // route URL ກັບ ໄປ ລາຍການ
    'backLabel' => 'ລາຍການ', // ຂໍ້ຄວາມ ປຸ່ມ ກັບ
    'record' => null,        // ເລກ ໃບ (mono) — ໜ້າ detail
    'status' => null,        // ຂໍ້ຄວາມ ສະຖານະ (evaluate ຢູ່ tag — ບໍ່ ໃສ່ ໃນ slot ຍ້ອນ Livewire ແຍກ scope)
    'statusClass' => 'bg-gray-100 text-gray-600',
])

{{-- ແຖບ ຍ່ອຍ ມາດຕະຖານ ໃຕ້ app-header: ← ກັບ · ເລກ ໃບ · status · actions.
     ວາງ ໄວ້ ພາຍໃນ container ຂອງ ໜ້າ (ຈັດ ຊ້າຍ ຕົງ ກັບ ເນື້ອໃນ). --}}
<div {{ $attributes->merge(['class' => 'flex items-center justify-between gap-3 py-3 flex-wrap']) }}>
    <div class="flex items-center gap-3 min-w-0">
        @if ($back)
            <a href="{{ $back }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                {{ $backLabel }}
            </a>
        @endif
        @if ($record)
            <span class="w-px h-4 bg-gray-300 shrink-0"></span>
            <span class="font-mono text-sm font-medium text-gray-800 truncate">{{ $record }}</span>
        @endif
        @if ($status)
            <span class="text-xs px-2 py-0.5 rounded-full {{ $statusClass }}">{{ $status }}</span>
        @endif
    </div>
    @if (isset($actions) || isset($hint))
        <div class="flex items-center gap-2 shrink-0">
            @isset($actions){{ $actions }}@endisset
            @isset($hint)<span class="text-xs text-gray-400 hidden sm:inline">{{ $hint }}</span>@endisset
        </div>
    @endif
</div>
