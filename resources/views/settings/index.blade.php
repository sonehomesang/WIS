<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Settings</h2>
    </x-slot>

    @php
        // Consolidated admin sections (gated by permission). Links are '#' until each is built.
        $cards = [
            ['label' => 'Users', 'desc' => 'ຜູ້ໃຊ້ · approve · lock', 'perm' => 'users.view', 'route' => 'settings.users',
                'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0Zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0Z'],
            ['label' => 'Roles & permissions', 'desc' => 'matrix 21×6 + scope', 'perm' => 'roles.view', 'route' => 'settings.roles',
                'icon' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.249-8.25-3.286z'],
            ['label' => 'Organization', 'desc' => 'Units · Depts · Locations · Buildings · Rooms', 'perm' => 'units.view', 'route' => 'settings.organization',
                'icon' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21'],
            ['label' => 'Units of measure', 'desc' => 'pcs · kg · m …', 'perm' => 'units.view', 'route' => 'settings.uom',
                'icon' => 'M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 01-2.031.352 5.989 5.989 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 01-2.031.352 5.989 5.989 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971Z'],
            ['label' => 'ສະຖານະພາບ ເຄື່ອງ', 'desc' => 'Condition catalogue · ໃຊ້ ຮ່ວມ ທຸກ ໂມດູລ', 'perm' => 'settings.view', 'route' => 'settings.condition-statuses',
                'icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
            ['label' => 'Suppliers', 'desc' => 'ຮ້ານຄ້າ supplier', 'perm' => 'supplier.view', 'route' => 'settings.suppliers',
                'icon' => 'M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12'],
            ['label' => 'Audit log', 'desc' => 'ບັນທຶກການກະທຳ ທຸກ module', 'perm' => 'audit.view', 'route' => 'settings.audit',
                'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['label' => 'Reports', 'desc' => 'ລາຍງານ + export', 'perm' => 'reports.view', 'route' => 'settings.reports',
                'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
            ['label' => 'Notifications', 'desc' => 'templates + toggles', 'perm' => 'settings.view', 'route' => 'settings.notifications',
                'icon' => 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0'],
            ['label' => 'Email (SMTP)', 'desc' => 'ຕັ້ງ ຕົວ ສົ່ງ email + ທົດສອບ', 'perm' => 'settings.view', 'route' => 'settings.email',
                'icon' => 'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75'],
            ['label' => 'Notification log', 'desc' => 'log ການແຈ້ງເຕືອນ + CSV', 'perm' => 'settings.view', 'route' => 'settings.notification-log',
                'icon' => 'M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z'],
            ['label' => 'ແປ / ຄຳສັບ', 'desc' => 'ແກ້ຄຳ ລາວ-ໄທ ເອງ (replace + term)', 'perm' => 'settings.view', 'route' => 'settings.translations',
                'icon' => 'm10.5 21 5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802'],
            ['label' => 'Backup / Restore', 'desc' => 'backup ຖານ ຂໍ້ມູນ + restore (admin)', 'perm' => 'settings.view', 'route' => 'settings.backup',
                'icon' => 'M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125'],
            ['label' => 'System', 'desc' => 'General · currency · VAT · letterhead', 'perm' => 'settings.view', 'route' => 'settings.system',
                'icon' => 'M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75'],
        ];
    @endphp

    <div class="py-6">
        <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($cards as $card)
                    @can($card['perm'])
                        <a href="{{ isset($card['route']) ? route($card['route']) : '#' }}" @if(isset($card['route'])) wire:navigate @endif class="block bg-white shadow-sm rounded-lg p-5 border border-gray-100 hover:border-gray-300 hover:shadow transition">
                            <svg class="w-6 h-6 text-sky-600" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" /></svg>
                            <p class="mt-3 font-medium text-gray-800">{{ $card['label'] }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ $card['desc'] }}</p>
                            @unless (isset($card['route']))<p class="mt-1 text-[11px] text-gray-400">ກຳລັງພັດທະນາ (phase ຕໍ່ໄປ)</p>@endunless
                        </a>
                    @endcan
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
