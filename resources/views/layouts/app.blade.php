<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            <!-- Left sidebar (responsive: fixed on desktop, slide-in on mobile) -->
            <x-layout.sidebar />

            <!-- Main column (shifted right of the sidebar on desktop) -->
            <div class="md:pl-60">
                @php
                    // Page title ໄດນາມິກ ຕາມ route ປັດຈຸບັນ (ແທນ logo). Fallback = headline ຂອງ route name.
                    $routeName = request()->route()?->getName();
                    $titleMap = [
                        'dashboard' => 'Dashboard',
                        'inventory' => 'WH Inventories',
                        'borrow' => 'Borrowing & Tracking',
                        'borrow.create' => 'New Borrow Request',
                        'borrow.show' => 'Borrowing Record Details',
                        'settings' => 'Settings',
                        'settings.users' => 'Users',
                        'settings.roles' => 'Roles & permissions',
                        'settings.organization' => 'Organization',
                        'settings.facilities' => 'Organization',
                        'settings.uom' => 'Units of measure',
                        'settings.suppliers' => 'Suppliers',
                        'settings.suppliers.show' => 'Supplier detail',
                        'settings.system' => 'System',
                        'profile' => 'Profile',
                    ];
                    $pageTitle = $titleMap[$routeName]
                        ?? \Illuminate\Support\Str::of($routeName ?? config('app.name'))->afterLast('.')->headline();

                    // ຄຳອະທິບາຍສັ້ນຂອງແຕ່ລະໜ້າ — ສະແດງຕໍ່ກັບ title (ແທນທີ່ຈະຢູ່ໃນ toolbar).
                    $subtitleMap = [
                        'inventory' => 'ສາງເຄື່ອງ & ວັດສະດຸ · ຄົ້ນຫາ / ນຳເຂົ້າ',
                        'borrow' => 'Monitor active loans and return schedules',
                        'settings.users' => 'ຈັດການຜູ້ໃຊ້ · role + ໜ່ວຍງານ · approve / lock',
                        'settings.roles' => '21 menus × 6 actions + scope · ແກ້ໄດ້ໂດຍ super_admin',
                        'settings.uom' => 'ໜ່ວຍວັດ (pcs · kg · m …) · ໃຊ້ໃນ Inventory / Materials',
                        'settings.suppliers' => 'ຮ້ານຄ້າ supplier · ໃຊ້ໃນ Materials / supplier users',
                        'settings.system' => 'ຄ່າລະບົບ — VAT (letterhead ຄ່ອຍເພີ່ມ)',
                    ];
                    $pageSubtitle = $subtitleMap[$routeName] ?? null;
                @endphp

                <!-- Global app header: title (left) + user menu (right) — ທຸກໜ້າ -->
                <header class="sticky top-0 z-30 bg-white border-b border-gray-200 shadow-sm">
                    <div class="px-4 sm:px-6 lg:px-8">
                        <div class="h-16 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2 min-w-0">
                                <button x-data @click="$dispatch('open-sidebar')" class="md:hidden p-2 -ml-2 min-h-[44px] min-w-[44px] text-gray-600 hover:text-gray-900" aria-label="Open menu">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                                </button>
                                <h1 class="text-lg font-semibold text-gray-800 truncate shrink-0">{{ $pageTitle }}</h1>
                                @if ($pageSubtitle)
                                    <span class="text-gray-300 hidden lg:inline">·</span>
                                    <span class="text-sm text-gray-400 truncate hidden lg:inline">{{ $pageSubtitle }}</span>
                                @endif
                            </div>
                            <livewire:layout.navigation />
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
