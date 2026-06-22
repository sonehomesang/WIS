@php
    // Settings section tabs — shown on every settings sub-page for quick switching.
    // Built sections have a route; not-yet-built ones render greyed.
    $sections = [
        ['label' => 'Users', 'perm' => 'users.view', 'route' => 'settings.users'],
        ['label' => 'Roles & permissions', 'perm' => 'roles.view', 'route' => 'settings.roles'],
        ['label' => 'Organization', 'perm' => 'units.view', 'route' => 'settings.organization', 'match' => ['settings.organization', 'settings.facilities']],
        ['label' => 'Units of measure', 'perm' => 'units.view', 'route' => 'settings.uom'],
        ['label' => 'Suppliers', 'perm' => 'supplier.view', 'route' => 'settings.suppliers'],
        ['label' => 'Audit log', 'perm' => 'audit.view'],
        ['label' => 'Reports', 'perm' => 'reports.view'],
        ['label' => 'Notifications', 'perm' => 'settings.view'],
        ['label' => 'System', 'perm' => 'settings.view', 'route' => 'settings.system'],
    ];
@endphp

<div class="sticky top-16 z-20 bg-gray-100 flex flex-wrap items-center gap-1 border-b border-gray-200 py-2 sm:py-0 sm:h-[52px]">
    <a href="{{ route('settings') }}" wire:navigate class="px-2.5 py-1.5 rounded-md text-sm text-gray-500 hover:bg-gray-100" title="ໜ້າລວມ Settings">⌂</a>
    @foreach ($sections as $s)
        @can($s['perm'])
            @if (isset($s['route']))
                @php $active = collect($s['match'] ?? [$s['route']])->contains(fn ($r) => request()->routeIs($r)); @endphp
                <a href="{{ route($s['route']) }}" wire:navigate
                   class="px-3 py-1.5 rounded-md text-sm {{ $active ? 'bg-sky-600 text-white font-medium' : 'text-gray-600 hover:bg-gray-100' }}">{{ $s['label'] }}</a>
            @else
                <span class="px-3 py-1.5 rounded-md text-sm text-gray-300 cursor-not-allowed" title="phase ຕໍ່ໄປ">{{ $s['label'] }}</span>
            @endif
        @endcan
    @endforeach
</div>
