<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            {{-- Welcome card --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold">ສະບາຍດີ, {{ auth()->user()->display_name }} 👋</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        ບົດບາດ:
                        <span class="font-medium">{{ auth()->user()->getRoleNames()->implode(', ') ?: '—' }}</span>
                        @if (auth()->user()->is_super_admin)
                            <span class="ml-2 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">super_admin</span>
                        @endif
                    </p>
                    <p class="mt-2 text-sm text-gray-500">ເລືອກເມນູຈາກແຖບດ້ານຊ້າຍເພື່ອເລີ່ມຕົ້ນ.</p>
                </div>
            </div>

            {{-- Quick menu grid (responsive: 1 col mobile → 3 col desktop) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ([
                    ['menu' => 'users', 'label' => 'ຜູ້ໃຊ້ງານ'],
                    ['menu' => 'roles', 'label' => 'ບົດບາດ & ສິດ'],
                    ['menu' => 'units', 'label' => 'ໜ່ວຍງານ / ພະແນກ'],
                    ['menu' => 'inventory', 'label' => 'ສາງເຄື່ອງ'],
                    ['menu' => 'settings', 'label' => 'ຕັ້ງຄ່າ'],
                    ['menu' => 'audit', 'label' => 'ບັນທຶກການກະທຳ'],
                ] as $card)
                    @can($card['menu'].'.view')
                        <div class="bg-white shadow-sm sm:rounded-lg p-5">
                            <p class="font-medium text-gray-800">{{ $card['label'] }}</p>
                            <p class="mt-1 text-xs text-gray-400">ກຳລັງພັດທະນາ (phase ຕໍ່ໄປ)</p>
                        </div>
                    @endcan
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
