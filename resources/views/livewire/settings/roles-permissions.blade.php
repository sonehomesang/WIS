<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @include('settings._tabs')
        <div x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)" x-show="show" style="display:none"
             class="fixed bottom-4 right-4 z-50 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 shadow-lg">ບັນທຶກແລ້ວ ✓</div>

        <div class="flex flex-col md:flex-row gap-4 items-start">
            {{-- Roles list --}}
            <div class="w-full md:w-48 flex-shrink-0 bg-white border border-gray-100 rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 text-sm font-medium text-gray-700">Roles</div>
                <ul class="text-sm">
                    @foreach ($roles as $role)
                        <li wire:key="role-{{ $role->id }}">
                            <button wire:click="selectRole({{ $role->id }})"
                                    class="w-full text-left px-4 py-2 min-h-[40px] border-b border-gray-100 flex items-center justify-between {{ $selectedRoleId === $role->id ? 'bg-sky-50 text-sky-700 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">
                                {{ $role->name }}
                                @if ($role->name === 'super_admin')<span class="text-xs text-gray-400" title="read-only">🔒</span>@endif
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Selected role detail --}}
            <div class="flex-1 min-w-0 space-y-4">
                @unless ($canEdit)
                    <div class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">
                        {{ $selectedRoleName === 'super_admin' ? 'super_admin = read-only (ມີສິດທຸກຢ່າງສະເໝີ)' : 'ເບິ່ງຢ່າງດຽວ — ແກ້ໄດ້ໂດຍ super_admin ເທົ່ານັ້ນ' }}
                    </div>
                @endunless

                {{-- Scope --}}
                <div class="bg-white border border-gray-100 rounded-lg p-4">
                    <div class="text-sm font-medium text-gray-700 mb-2">Scope rules — {{ $selectedRoleName }}</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">transactionScope</label>
                            <select wire:model="scope.transactionScope" @disabled(! $canEdit) class="w-full rounded-md border-gray-300 text-sm disabled:bg-gray-50">
                                <option value="all">all</option>
                                <option value="department">department</option>
                                <option value="assigned">assigned</option>
                                <option value="own">own</option>
                                <option value="own_orders">own_orders</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">inventoryScope</label>
                            <select wire:model="scope.inventoryScope" @disabled(! $canEdit) class="w-full rounded-md border-gray-300 text-sm disabled:bg-gray-50">
                                <option value="all">all</option>
                                <option value="none">none</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">catalogScope</label>
                            <select wire:model="scope.catalogScope" @disabled(! $canEdit) class="w-full rounded-md border-gray-300 text-sm disabled:bg-gray-50">
                                <option value="all">all</option>
                                <option value="own_supplier">own_supplier</option>
                                <option value="none">none</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">equipmentScope</label>
                            <select wire:model="scope.equipmentScope" @disabled(! $canEdit) class="w-full rounded-md border-gray-300 text-sm disabled:bg-gray-50">
                                <option value="all">all</option>
                                <option value="department">department</option>
                                <option value="none">none</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Permission matrix --}}
                <div class="bg-white border border-gray-100 rounded-lg overflow-auto max-h-[calc(100vh-18rem)]">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 z-10 bg-gray-50 text-gray-700 border-b border-gray-200 shadow-sm">
                            <tr>
                                <th class="text-left font-semibold px-3 py-2">Menu</th>
                                @foreach ($actions as $a)
                                    <th class="font-medium px-2 py-2 text-center capitalize" style="width:64px;">{{ $a }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($menus as $m)
                                <tr wire:key="row-{{ $m }}" class="border-t border-gray-100">
                                    <td class="px-3 py-1.5 text-gray-700">{{ $m }}</td>
                                    @foreach ($actions as $a)
                                        <td class="px-2 py-1.5 text-center">
                                            <input type="checkbox" wire:model="grants.{{ $m }}.{{ $a }}" @disabled(! $canEdit)
                                                   class="rounded border-gray-300 text-sky-600 focus:ring-sky-500 disabled:opacity-50" />
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($canEdit)
                    <div class="flex justify-end">
                        <button wire:click="save" class="text-sm text-white bg-sky-600 rounded-md px-5 py-2 min-h-[40px] hover:bg-sky-700">Save</button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
