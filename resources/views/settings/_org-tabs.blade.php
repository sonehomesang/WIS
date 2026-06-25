<div class="flex gap-2">
    @can('units.view')
        <a href="{{ route('settings.organization') }}" wire:navigate
           class="px-3 py-1.5 rounded-md text-sm {{ request()->routeIs('settings.organization') ? 'bg-sky-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100' }}">Org units</a>
    @endcan
    @can('locations.view')
        <a href="{{ route('settings.facilities') }}" wire:navigate
           class="px-3 py-1.5 rounded-md text-sm {{ request()->routeIs('settings.facilities') ? 'bg-sky-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100' }}">Facilities</a>
    @endcan
</div>
