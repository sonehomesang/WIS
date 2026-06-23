@php
    $tones = [
        'indigo' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
        'sky' => 'bg-sky-50 text-sky-700 border-sky-100',
        'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'violet' => 'bg-violet-50 text-violet-700 border-violet-100',
        'amber' => 'bg-amber-50 text-amber-700 border-amber-100',
        'gray' => 'bg-gray-50 text-gray-700 border-gray-100',
    ];
@endphp

<div class="pb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        {{-- welcome --}}
        <div class="bg-white border border-gray-100 rounded-lg p-5">
            <h3 class="text-lg font-semibold text-gray-800">ສະບາຍດີ, {{ auth()->user()->display_name }} 👋</h3>
            <p class="mt-1 text-sm text-gray-500">
                ບົດບາດ: <span class="font-medium text-gray-700">{{ auth()->user()->getRoleNames()->implode(', ') ?: '—' }}</span>
                @if (auth()->user()->is_super_admin)<span class="ml-2 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">super_admin</span>@endif
                · {{ now()->format('l, d M Y') }}
            </p>
        </div>

        {{-- summary cards --}}
        @if (count($cards))
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach ($cards as $c)
                    <a href="{{ route($c['route']) }}" wire:navigate class="block bg-white border border-gray-100 rounded-lg p-4 hover:border-gray-200 hover:shadow-sm transition">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="text-sm font-medium text-gray-700">{{ $c['label'] }}</div>
                                <div class="mt-2 flex items-baseline gap-1.5">
                                    <span class="text-3xl font-bold text-gray-800">{{ number_format($c['big']) }}</span>
                                    <span class="text-xs text-gray-400">{{ $c['big_label'] }}</span>
                                </div>
                            </div>
                            <span class="text-xs rounded-full px-2 py-1 border {{ $tones[$c['tone']] ?? $tones['gray'] }}">›</span>
                        </div>
                        <div class="mt-2 text-xs {{ $c['alert'] ? 'text-red-600 font-medium' : 'text-gray-400' }}">{{ $c['sub'] }}</div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="bg-white border border-gray-100 rounded-lg p-6 text-center text-gray-400">ເລືອກເມນູຈາກແຖບດ້ານຊ້າຍເພື່ອເລີ່ມຕົ້ນ.</div>
        @endif
    </div>
</div>
