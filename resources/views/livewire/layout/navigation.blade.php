<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

@php
    $u = auth()->user();
    $role = $u->is_super_admin ? 'Super Admin' : ($u->getRoleNames()->first() ?? '—');
    $initials = \Illuminate\Support\Str::of($u->display_name ?? $u->email)
        ->explode(' ')->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
@endphp

<div class="flex items-center gap-2" x-data="{ open: false }">
    {{-- role badge --}}
    <span class="hidden sm:inline-flex text-xs font-medium text-rose-700 bg-rose-50 border border-rose-200 rounded-full px-2.5 py-1">{{ $role }}</span>

    {{-- user dropdown --}}
    <div class="relative" x-on:click.outside="open = false">
        <button type="button" x-on:click="open = !open" class="flex items-center gap-2 rounded-md px-1.5 py-1 hover:bg-gray-50 min-h-[40px]">
            <span class="w-8 h-8 rounded-full bg-sky-600 text-white text-xs font-semibold flex items-center justify-center uppercase">{{ $initials ?: 'U' }}</span>
            <span class="hidden sm:block text-sm text-gray-700"
                  x-data="{{ json_encode(['name' => $u->display_name ?? $u->email]) }}"
                  x-text="name" x-on:profile-updated.window="name = $event.detail.name"></span>
            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
        </button>
        <div x-show="open" x-cloak x-transition class="absolute right-0 z-30 mt-1 w-44 bg-white border border-gray-200 rounded-md shadow-lg py-1">
            <a href="{{ route('profile') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">{{ __('Profile') }}</a>
            <button wire:click="logout" class="w-full text-start px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">{{ __('Log Out') }}</button>
        </div>
    </div>
</div>
