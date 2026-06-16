@php
    // 21 menus grouped — labels (Lao) + permission key. Routes are '#' until each module is built.
    $groups = [
        'ຫຼັກ' => [
            ['menu' => 'dashboard', 'label' => 'ໜ້າຫຼັກ', 'route' => route('dashboard')],
        ],
        'ການເຮັດວຽກ' => [
            ['menu' => 'inventory', 'label' => 'ສາງເຄື່ອງ'],
            ['menu' => 'borrow', 'label' => 'ການຢືມ'],
            ['menu' => 'deposit', 'label' => 'ການຝາກ'],
            ['menu' => 'request', 'label' => 'ການສັ່ງຊື້'],
            ['menu' => 'da', 'label' => 'DA — ແຈ້ງຄວາມຜິດປົກກະຕິ'],
            ['menu' => 'oga', 'label' => 'OGA — ໃບສົ່ງເຄື່ອງອອກ'],
            ['menu' => 'expo', 'label' => 'Expo Info'],
        ],
        'ສິນຄ້າ' => [
            ['menu' => 'catalog', 'label' => 'ສິນຄ້າ (Materials)'],
            ['menu' => 'supplier', 'label' => 'ຜູ້ສະໜອງ'],
        ],
        'ໂຄງສ້າງອົງກອນ' => [
            ['menu' => 'units', 'label' => 'ໜ່ວຍງານ'],
            ['menu' => 'departments', 'label' => 'ພະແນກ'],
            ['menu' => 'locations', 'label' => 'ສະຖານທີ່'],
            ['menu' => 'buildings', 'label' => 'ອາຄານ'],
            ['menu' => 'rooms', 'label' => 'ຫ້ອງ'],
        ],
        'ບໍລິຫານ' => [
            ['menu' => 'users', 'label' => 'ຜູ້ໃຊ້ງານ'],
            ['menu' => 'roles', 'label' => 'ບົດບາດ & ສິດ'],
            ['menu' => 'settings', 'label' => 'ຕັ້ງຄ່າ'],
            ['menu' => 'reports', 'label' => 'ລາຍງານ'],
            ['menu' => 'audit', 'label' => 'ບັນທຶກການກະທຳ'],
            ['menu' => 'notifications', 'label' => 'ການແຈ້ງເຕືອນ'],
        ],
    ];
@endphp

<div
    x-data="{ open: false }"
    @open-sidebar.window="open = true"
    @keydown.escape.window="open = false"
>
    {{-- Mobile overlay --}}
    <div
        x-show="open"
        x-transition.opacity
        @click="open = false"
        class="fixed inset-0 z-30 bg-black/40 md:hidden"
        style="display:none"
    ></div>

    {{-- Sidebar panel --}}
    <aside
        :class="open ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
        class="fixed inset-y-0 left-0 z-40 w-60 bg-gray-900 text-gray-100 overflow-y-auto transition-transform duration-200 ease-in-out md:translate-x-0"
    >
        {{-- Brand --}}
        <div class="flex items-center justify-between h-16 px-4 border-b border-gray-800">
            <a href="{{ route('dashboard') }}" class="text-lg font-semibold text-white" wire:navigate>WIS</a>
            <button @click="open = false" class="md:hidden p-2 min-h-[44px] min-w-[44px] text-gray-400 hover:text-white" aria-label="Close menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        {{-- Menu groups --}}
        <nav class="px-2 py-4 space-y-6">
            @foreach ($groups as $groupLabel => $items)
                @php
                    $visible = collect($items)->filter(fn ($i) => auth()->user()->can($i['menu'].'.view'));
                @endphp
                @if ($visible->isNotEmpty())
                    <div>
                        <p class="px-3 mb-1 text-xs font-semibold uppercase tracking-wider text-gray-500">{{ $groupLabel }}</p>
                        <ul class="space-y-1">
                            @foreach ($visible as $item)
                                @php
                                    $href = $item['route'] ?? '#';
                                    $isActive = isset($item['route']) && request()->url() === $item['route'];
                                @endphp
                                <li>
                                    <a href="{{ $href }}"
                                       @class([
                                           'flex items-center min-h-[44px] px-3 rounded-md text-sm transition-colors',
                                           'bg-gray-800 text-white font-medium' => $isActive,
                                           'text-gray-300 hover:bg-gray-800 hover:text-white' => ! $isActive,
                                       ])>
                                        {{ $item['label'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endforeach
        </nav>
    </aside>
</div>
