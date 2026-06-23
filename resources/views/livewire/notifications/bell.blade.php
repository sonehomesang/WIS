@php
    $dot = ['info' => 'bg-sky-400', 'success' => 'bg-emerald-400', 'warning' => 'bg-amber-400', 'error' => 'bg-red-400'];
@endphp

<div x-data="{ open: false }" class="relative" wire:poll.30s>
    <button @click="open = !open" class="relative p-2 text-gray-500 hover:text-gray-800 rounded-md hover:bg-gray-50" aria-label="Notifications">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
        @if ($unread > 0)
            <span class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-1 rounded-full bg-red-600 text-white text-[10px] leading-4 text-center">{{ $unread > 99 ? '99+' : $unread }}</span>
        @endif
    </button>

    <div x-show="open" x-cloak @click.outside="open = false" x-transition
         class="absolute right-0 mt-2 w-80 max-w-[90vw] bg-white border border-gray-200 rounded-lg shadow-lg z-50">
        <div class="flex items-center justify-between px-3 py-2 border-b border-gray-100">
            <span class="text-sm font-medium text-gray-700">ການແຈ້ງເຕືອນ</span>
            @if ($unread > 0)<button wire:click="markAllRead" class="text-xs text-sky-600 hover:underline">ອ່ານໝົດ</button>@endif
        </div>
        <div class="max-h-96 overflow-y-auto divide-y divide-gray-50">
            @forelse ($items as $n)
                <button wire:click="markRead({{ $n->id }})" class="w-full text-left px-3 py-2.5 hover:bg-gray-50 flex gap-2 {{ $n->read_at ? 'opacity-60' : '' }}">
                    <span class="mt-1.5 w-2 h-2 rounded-full shrink-0 {{ $dot[$n->type] ?? 'bg-gray-300' }}"></span>
                    <span class="min-w-0">
                        <span class="block text-sm text-gray-800 truncate">{{ $n->title }}</span>
                        @if ($n->message)<span class="block text-xs text-gray-500 line-clamp-2">{{ $n->message }}</span>@endif
                        <span class="block text-[11px] text-gray-400 mt-0.5">{{ $n->created_at?->diffForHumans() }}</span>
                    </span>
                </button>
            @empty
                <div class="px-3 py-8 text-center text-sm text-gray-400">ບໍ່ມີ ການແຈ້ງເຕືອນ</div>
            @endforelse
        </div>
    </div>
</div>
