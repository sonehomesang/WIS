<div class="flex gap-2">
    <a href="{{ route('settings.organization') }}" wire:navigate
       class="px-3 py-1.5 rounded-md text-sm {{ request()->routeIs('settings.organization') ? 'bg-sky-50 text-sky-700 font-medium' : 'text-gray-500 hover:bg-gray-100' }}">Org units</a>
    <a href="{{ route('settings.facilities') }}" wire:navigate
       class="px-3 py-1.5 rounded-md text-sm {{ request()->routeIs('settings.facilities') ? 'bg-sky-50 text-sky-700 font-medium' : 'text-gray-500 hover:bg-gray-100' }}">Facilities</a>
</div>
