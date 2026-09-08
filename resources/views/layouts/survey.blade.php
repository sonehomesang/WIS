<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'ແບບສອບຖາມຄວາມເພິ່ງພໍໃຈ · WH' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
    @include('partials._pwa-head')
</head>
<body class="font-sans text-gray-900 antialiased bg-gray-100 min-h-screen">
    <div class="min-h-screen py-6 px-4">
        <div class="max-w-3xl mx-auto">
            {{-- Brand header --}}
            <div class="flex items-center gap-3 mb-5">
                <div class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-sky-700 shadow-sm shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 21v-13l9 -4l9 4v13" /><path d="M13 13h4v8h-10v-6h6" /><path d="M13 21v-9a1 1 0 0 0 -1 -1h-2a1 1 0 0 0 -1 1v3" />
                    </svg>
                </div>
                <div class="leading-tight">
                    <div class="text-base font-semibold text-gray-800">WH · Warehouse</div>
                    <div class="text-xs text-gray-500">Nam Theun 2 Power Company</div>
                </div>
            </div>

            {{ $slot }}

            <p class="text-center text-xs text-gray-400 mt-6">© {{ date('Y') }} Nam Theun 2 · Warehouse Dept.</p>
        </div>
    </div>
</body>
</html>
