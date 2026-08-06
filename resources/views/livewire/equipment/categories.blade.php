<div class="pb-6">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        <div x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)" x-show="show" style="display:none"
             class="fixed bottom-4 right-4 z-50 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 shadow-lg">ບັນທຶກແລ້ວ ✓</div>

        <div class="flex flex-wrap items-center gap-2 sticky top-16 z-30 bg-gray-100 py-1">
            <a href="{{ route('equipment') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">← ກັບ ໄປ ທະບຽນ ເຄື່ອງ</a>
            <div class="flex-1"></div>
            @if ($canManageDeleted)
                <button wire:click="toggleDeleted" class="text-sm border rounded-md px-3 py-2 min-h-[40px] whitespace-nowrap {{ $showDeleted ? 'bg-gray-700 text-white border-gray-700' : 'text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                    {{ $showDeleted ? '← ລາຍການ ປົກກະຕິ' : '🗑 ບັນທຶກ ການ ລຶບ' }}
                </button>
            @endif
            <button wire:click="newCategory" class="text-sm text-white bg-sky-600 rounded-md px-3 py-2 min-h-[40px] hover:bg-sky-700" @if ($showDeleted) style="display:none" @endif>+ ປະເພດ ໃໝ່</button>
        </div>

        <div class="bg-white border border-gray-100 rounded-lg overflow-x-hidden overflow-y-auto max-h-[calc(100vh-16rem)]">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 bg-gray-50 text-gray-600 text-xs border-b border-gray-200 shadow-sm">
                    <tr>
                        <th class="text-left px-3 py-2 font-semibold w-16">ລຳດັບ</th>
                        <th class="text-left px-3 py-2 font-semibold">ຊື່ ປະເພດ</th>
                        <th class="text-left px-3 py-2 font-semibold w-20">ໃຊ້</th>
                        <th class="px-3 py-2 w-20"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($categories as $c)
                        <tr wire:key="cat-{{ $c->id }}">
                            <td class="px-3 py-2 text-gray-400">{{ $c->sort_order }}</td>
                            <td class="px-3 py-2 font-medium text-gray-800">
                                {{ $c->name }}
                                @if ($showDeleted)
                                    <div class="text-[11px] font-normal text-red-600 mt-0.5">🗑 ລຶບ: {{ $c->deleted_at?->format('d/m/Y H:i') }} · ໂດຍ {{ $c->deletedBy?->display_name ?? '—' }}@if ($c->deleted_reason) · ເຫດຜົນ: {{ $c->deleted_reason }}@endif</div>
                                @endif
                            </td>
                            <td class="px-3 py-2"><span class="text-xs rounded px-2 py-0.5 {{ $c->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ $c->is_active ? 'ເປີດ' : 'ປິດ' }}</span></td>
                            <td class="px-3 py-2 pr-5 text-right whitespace-nowrap text-gray-500">
                                @if ($showDeleted)
                                    <button wire:click="restore({{ $c->id }})" class="text-xs text-emerald-700 border border-emerald-200 rounded px-2 py-1 hover:bg-emerald-50">↩ ກູ້ຄືນ</button>
                                @else
                                    <button wire:click="editCategory({{ $c->id }})" class="hover:text-gray-800 p-1" title="ແກ້ໄຂ">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /></svg>
                                    </button>
                                    <button wire:click="openDelete({{ $c->id }})" class="hover:text-red-600 p-1" title="ລຶບ">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-6 text-center text-gray-400">{{ $showDeleted ? 'ບໍ່ ມີ ປະເພດ ທີ່ ຖືກ ລຶບ' : 'ຍັງບໍ່ມີ ປະເພດ — ກົດ "+ ປະເພດ ໃໝ່"' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="text-xs text-gray-400">ປະເພດ ທີ່ "ເປີດ" ຈະ ຂຶ້ນ ໃນ dropdown ຕອນ ເພີ່ມ/ແກ້ ເຄື່ອງ. ລຳດັບ ໜ້ອຍ ຂຶ້ນ ກ່ອນ.</p>
    </div>

    {{-- ຢືນຢັນ ລຶບ + ເຫດຜົນ (shared partial + trait SoftDeletesWithReason) --}}
    @include('partials._delete-modal', ['title' => 'ລຶບ ປະເພດ ນີ້?', 'subtitle' => $this->deletingRecord?->name])

    {{-- Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="cat-modal">
            <div class="bg-white w-full md:max-w-md rounded-t-lg md:rounded-lg p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-800">{{ $editingId ? 'ແກ້ໄຂ ປະເພດ' : 'ປະເພດ ໃໝ່' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-700 p-1" aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                @include('partials._form-errors')

                <div>
                    <label class="block text-sm text-gray-600 mb-1">ຊື່ ປະເພດ <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="cName" placeholder="Generator · Vehicle · Power tool…" class="w-full rounded-md border-gray-300 text-sm" />
                    @error('cName')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ລຳດັບ</label>
                        <input type="number" min="0" wire:model="cSort" class="w-full rounded-md border-gray-300 text-sm" />
                    </div>
                    <div class="flex items-end">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" wire:model="cActive" class="rounded border-gray-300 text-sky-600"> ເປີດ ໃຊ້
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t">
                    <button wire:click="$set('showModal', false)" class="text-sm text-gray-700 border border-gray-300 rounded-md px-4 py-2 min-h-[40px] hover:bg-gray-50">ຍົກເລີກ</button>
                    <button wire:click="save" class="text-sm text-white bg-sky-600 rounded-md px-4 py-2 min-h-[40px] hover:bg-sky-700">ບັນທຶກ</button>
                </div>
            </div>
        </div>
    @endif
</div>
