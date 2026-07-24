@php
    $svgEdit = 'm16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z';
    $svgTrash = 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16';
    $cBadge = fn ($s) => match ($s) {
        'active' => 'bg-green-50 text-green-700',
        'draft' => 'bg-amber-50 text-amber-700',
        'cancelled' => 'bg-red-50 text-red-700',
        default => 'bg-gray-100 text-gray-500',
    };
    $fmt = fn ($r) => $r === null ? 'global' : rtrim(rtrim(number_format($r, 2), '0'), '.').'%';
@endphp

<div class="pb-6">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <a href="{{ route('settings.suppliers') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg> Suppliers
        </a>

        <div class="flex items-start justify-between flex-wrap gap-2">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">{{ $supplier->name }}</h2>
                <p class="text-sm text-gray-500">{{ $supplier->default_currency }} @if ($supplier->contact_person)· {{ $supplier->contact_person }}@endif @if ($supplier->contact_phone)· {{ $supplier->contact_phone }}@endif</p>
            </div>
            <div x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)" x-show="show" style="display:none"
                 class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-1">ບັນທຶກແລ້ວ ✓</div>
        </div>

        {{-- Resolved VAT --}}
        <div class="flex items-center gap-3 bg-sky-50 border border-sky-100 rounded-lg px-4 py-3">
            <svg class="w-5 h-5 text-sky-700 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21l-7-2-7 2V5a2 2 0 012-2h10a2 2 0 012 2v16z" /></svg>
            <div class="text-sm text-sky-800">
                <span class="font-medium">VAT: {{ $vat['enabled'] ? $fmt($vat['rate']) : 'ປິດ' }}</span>
                — @if ($vat['source'] === 'supplier') ສະເພາະ supplier ນີ້ @else global (Settings → System) @endif
                · ແກ້ໄດ້ທີ່ <a href="{{ route('settings.suppliers') }}" wire:navigate class="underline">supplier edit</a>
            </div>
        </div>

        {{-- VAT change history --}}
        @if ($vatChanges->isNotEmpty())
            <div class="bg-white border border-gray-100 rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 text-sm font-medium text-gray-700">ປະຫວັດການປ່ຽນ VAT</div>
                <ul class="text-sm divide-y divide-gray-100">
                    @foreach ($vatChanges as $ch)
                        <li class="px-4 py-2">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-700">{{ $fmt($ch->old_rate) }} → <span class="font-medium">{{ $fmt($ch->new_rate) }}</span></span>
                                <span class="text-xs text-gray-400">{{ $ch->changed_at?->format('Y-m-d H:i') }} · {{ $ch->changedBy?->display_name ?? '—' }}</span>
                            </div>
                            @if ($ch->reason)<div class="text-xs text-gray-500 mt-0.5">ເຫດຜົນ: {{ $ch->reason }}</div>@endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Contracts --}}
        <div class="bg-white border border-gray-100 rounded-lg overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                <span class="font-medium text-sm text-gray-700">Contracts</span>
                <div class="flex items-center gap-2">
                    @if ($canManageDeleted)
                        <button wire:click="toggleDeleted" class="text-sm border rounded-md px-3 py-1.5 min-h-[36px] whitespace-nowrap {{ $showDeleted ? 'bg-gray-700 text-white border-gray-700' : 'text-gray-600 border-gray-300 bg-white hover:bg-gray-50' }}">
                            {{ $showDeleted ? '← ລາຍການ ປົກກະຕິ' : '🗑 ບັນທຶກ ການ ລຶບ' }}
                        </button>
                    @endif
                    @can('supplier.create')<button wire:click="newContract" class="text-sm text-white bg-sky-600 rounded-md px-3 py-1.5 min-h-[36px] hover:bg-sky-700" @if ($showDeleted) style="display:none" @endif>+ Add contract</button>@endcan
                </div>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500">
                    <tr>
                        <th class="text-left font-medium px-4 py-2">Contract #</th>
                        <th class="text-left font-medium px-4 py-2">Period</th>
                        <th class="text-left font-medium px-4 py-2">Status</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contracts as $c)
                        <tr wire:key="ct-{{ $c->id }}" class="border-t border-gray-100">
                            <td class="px-4 py-2 font-medium text-gray-800">
                                {{ $c->contract_number }}
                                @if ($showDeleted)
                                    <div class="text-[11px] font-normal text-red-600 mt-0.5">🗑 ລຶບ: {{ $c->deleted_at?->format('d/m/Y H:i') }} · ໂດຍ {{ $c->deletedBy?->display_name ?? '—' }}@if ($c->deleted_reason) · ເຫດຜົນ: {{ $c->deleted_reason }}@endif</div>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-gray-600 text-xs">{{ $c->effective_date?->toDateString() ?? '—' }} → {{ $c->expiry_date?->toDateString() ?? '—' }}</td>
                            <td class="px-4 py-2"><span class="text-xs rounded px-2 py-0.5 {{ $cBadge($c->status) }}">{{ $c->status }}</span></td>
                            <td class="px-4 py-2 text-right whitespace-nowrap text-gray-500">
                                @if ($showDeleted)
                                    <button wire:click="restore({{ $c->id }})" class="text-xs text-emerald-700 border border-emerald-200 rounded px-2 py-1 hover:bg-emerald-50">↩ ກູ້ຄືນ</button>
                                @else
                                    @can('supplier.edit')<button wire:click="editContract({{ $c->id }})" class="p-1 hover:text-gray-800" aria-label="Edit"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgEdit }}" /></svg></button>@endcan
                                    @can('supplier.delete')<button wire:click="openDelete({{ $c->id }})" class="p-1 hover:text-red-600" aria-label="Delete"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgTrash }}" /></svg></button>@endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">{{ $showDeleted ? 'ບໍ່ ມີ contract ທີ່ ຖືກ ລຶບ' : 'ຍັງບໍ່ມີ contract' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ຢືນຢັນ ລຶບ + ເຫດຜົນ (shared partial + trait SoftDeletesWithReason) --}}
    @include('partials._delete-modal', ['title' => 'ລຶບ contract ນີ້?', 'subtitle' => $this->deletingRecord?->contract_number])

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="ct-modal">
            <div class="bg-white w-full md:max-w-lg rounded-t-lg md:rounded-lg p-5 space-y-3 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-800">{{ $editingId ? 'ແກ້ໄຂ contract' : 'ເພີ່ມ contract' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-700 p-1" aria-label="Close"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @include('partials._form-errors', ['wrapClass' => 'md:col-span-2'])
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">Contract number <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="contract_number" placeholder="WIS-2026-SUP01" class="w-full rounded-md border-gray-300 text-sm" />
                        @error('contract_number')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div><label class="block text-sm text-gray-600 mb-1">Sign date</label><input type="date" wire:model="sign_date" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div><label class="block text-sm text-gray-600 mb-1">Status</label><select wire:model="status" class="w-full rounded-md border-gray-300 text-sm"><option value="draft">draft</option><option value="active">active</option><option value="expired">expired</option><option value="superseded">superseded</option><option value="cancelled">cancelled</option></select></div>
                    <div><label class="block text-sm text-gray-600 mb-1">Effective date</label><input type="date" wire:model="effective_date" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div><label class="block text-sm text-gray-600 mb-1">Expiry date</label><input type="date" wire:model="expiry_date" class="w-full rounded-md border-gray-300 text-sm" />@error('expiry_date')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror</div>
                    <div><label class="block text-sm text-gray-600 mb-1">Renewal date</label><input type="date" wire:model="renewal_date" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div class="md:col-span-2"><label class="block text-sm text-gray-600 mb-1">ໝາຍເຫດ</label><textarea wire:model="notes" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea></div>
                </div>
                <p class="text-xs text-gray-400">VAT ບໍ່ໄດ້ກຳນົດທີ່ contract — ໃຊ້ VAT ຂອງ supplier (ຫຼື global). ສັນຍາໃຊ້ເປັນ "ເຫດຜົນ" ຕອນປ່ຽນ VAT.</p>
                <div class="flex justify-end gap-2 pt-2">
                    <button wire:click="$set('showModal', false)" class="text-sm text-gray-700 border border-gray-300 rounded-md px-4 py-2 min-h-[40px] hover:bg-gray-50">ຍົກເລີກ</button>
                    <button wire:click="saveContract" class="text-sm text-white bg-sky-600 rounded-md px-4 py-2 min-h-[40px] hover:bg-sky-700">ບັນທຶກ</button>
                </div>
            </div>
        </div>
    @endif
</div>
