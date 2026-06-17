@php
    $badge = fn ($s) => match ($s) {
        'active' => 'bg-green-50 text-green-700',
        'pending' => 'bg-amber-50 text-amber-700',
        'locked' => 'bg-red-50 text-red-700',
        default => 'bg-gray-100 text-gray-600',
    };
@endphp

<div class="py-6 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @include('settings._tabs')
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Users</h2>
                <p class="text-sm text-gray-500">ຈັດການຜູ້ໃຊ້ · ກຳນົດ role + ໜ່ວຍງານ · approve / lock</p>
            </div>
            <div class="flex items-center gap-2">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ ຊື່/email…"
                       class="rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                @can('users.create')
                    <button wire:click="newUser" class="inline-flex items-center gap-1 text-sm text-white bg-sky-600 rounded-md px-3 py-2 min-h-[40px] hover:bg-sky-700 whitespace-nowrap">+ Create user</button>
                @endcan
            </div>
        </div>

        <div x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)" x-show="show" style="display:none"
             class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-1">ບັນທຶກແລ້ວ ✓</div>

        {{-- Desktop table --}}
        <div class="hidden md:block bg-white border border-gray-100 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500">
                    <tr>
                        <th class="text-left font-medium px-4 py-2">User</th>
                        <th class="text-left font-medium px-4 py-2">Role</th>
                        <th class="text-left font-medium px-4 py-2">Unit / Dept</th>
                        <th class="text-left font-medium px-4 py-2">Status</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr wire:key="u-{{ $user->id }}" class="border-t border-gray-100">
                            <td class="px-4 py-2">
                                <div class="font-medium text-gray-800">{{ $user->display_name }}
                                    @if ($user->is_super_admin)<span class="text-[10px] text-amber-700 bg-amber-50 rounded px-1.5 py-0.5 ml-1">super</span>@endif
                                </div>
                                <div class="text-xs text-gray-400">{{ $user->email }}</div>
                            </td>
                            <td class="px-4 py-2"><span class="text-xs bg-gray-100 text-gray-600 rounded px-2 py-0.5">{{ $user->roles->first()?->name ?? '—' }}</span></td>
                            <td class="px-4 py-2 text-gray-600">{{ $user->unit?->name ?? '—' }}@if ($user->department) / {{ $user->department->name }}@endif</td>
                            <td class="px-4 py-2"><span class="text-xs rounded px-2 py-0.5 {{ $badge($user->status) }}">{{ $user->status }}</span></td>
                            <td class="px-4 py-2 text-right whitespace-nowrap text-gray-500">
                                @if ($user->status === 'pending') @can('users.activate')
                                    <button wire:click="approve({{ $user->id }})" class="hover:text-green-600 p-1" aria-label="Approve" title="Approve">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    </button>
                                @endcan @endif
                                @can('users.deactivate')
                                    <button wire:click="toggleLock({{ $user->id }})" class="hover:text-red-600 p-1" aria-label="Lock/unlock" title="Lock / unlock">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 0h10.5a2.25 2.25 0 0 1 2.25 2.25v6.75a2.25 2.25 0 0 1-2.25 2.25H6.75a2.25 2.25 0 0 1-2.25-2.25v-6.75a2.25 2.25 0 0 1 2.25-2.25Z" /></svg>
                                    </button>
                                @endcan
                                @can('users.edit')
                                    <button wire:click="editUser({{ $user->id }})" class="hover:text-gray-800 p-1" aria-label="Edit" title="Edit">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /></svg>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">ບໍ່ມີຜູ້ໃຊ້</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="md:hidden space-y-2">
            @forelse ($users as $user)
                <div wire:key="m-{{ $user->id }}" class="bg-white border border-gray-100 rounded-lg p-3">
                    <div class="flex items-center justify-between">
                        <div class="font-medium text-gray-800">{{ $user->display_name }}</div>
                        <span class="text-xs rounded px-2 py-0.5 {{ $badge($user->status) }}">{{ $user->status }}</span>
                    </div>
                    <div class="text-xs text-gray-400">{{ $user->email }}</div>
                    <div class="text-xs text-gray-600 mt-1">{{ $user->roles->first()?->name ?? '—' }} · {{ $user->unit?->name ?? '—' }}@if ($user->department) / {{ $user->department->name }}@endif</div>
                    <div class="flex gap-2 mt-2">
                        @if ($user->status === 'pending') @can('users.activate')<button wire:click="approve({{ $user->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px]">Approve</button>@endcan @endif
                        @can('users.deactivate')<button wire:click="toggleLock({{ $user->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px]">{{ $user->status === 'locked' ? 'Unlock' : 'Lock' }}</button>@endcan
                        @can('users.edit')<button wire:click="editUser({{ $user->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px]">Edit</button>@endcan
                    </div>
                </div>
            @empty
                <div class="text-center text-gray-400 py-6">ບໍ່ມີຜູ້ໃຊ້</div>
            @endforelse
        </div>
    </div>

    {{-- Create / Edit modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="user-modal">
            <div class="bg-white w-full md:max-w-lg rounded-t-lg md:rounded-lg p-5 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-800">{{ $editingId ? 'ແກ້ໄຂຜູ້ໃຊ້' : 'ສ້າງຜູ້ໃຊ້ໃໝ່' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-700 p-1" aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ຊື່ (display name) <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="display_name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                        @error('display_name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Email <span class="text-red-500">*</span></label>
                        <input type="email" wire:model="email" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                        @error('email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Password @if ($editingId)<span class="text-xs text-gray-400">(ວ່າງ = ບໍ່ປ່ຽນ)</span>@else<span class="text-red-500">*</span>@endif</label>
                        <input type="password" wire:model="password" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                        @error('password')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Role <span class="text-red-500">*</span></label>
                        <select wire:model="role" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm">
                            <option value="">— ເລືອກ —</option>
                            @foreach ($roles as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach
                        </select>
                        @error('role')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Status</label>
                        <select wire:model="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm">
                            <option value="active">active</option>
                            <option value="pending">pending</option>
                            <option value="locked">locked</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ໜ່ວຍງານ (Unit)</label>
                        <select wire:model.live="unit_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm">
                            <option value="">—</option>
                            @foreach ($units as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Department</label>
                        <select wire:model="department_id" @disabled(! $unit_id) class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm disabled:bg-gray-50">
                            <option value="">—</option>
                            @foreach ($formDepartments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button wire:click="$set('showModal', false)" class="text-sm text-gray-700 border border-gray-300 rounded-md px-4 py-2 min-h-[40px] hover:bg-gray-50">ຍົກເລີກ</button>
                    <button wire:click="save" class="text-sm text-white bg-sky-600 rounded-md px-4 py-2 min-h-[40px] hover:bg-sky-700">ບັນທຶກ</button>
                </div>
            </div>
        </div>
    @endif
</div>
