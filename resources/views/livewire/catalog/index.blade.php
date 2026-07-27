@php $fileCls = 'block w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-2 file:rounded file:border-0 file:bg-sky-50 file:text-sky-700'; @endphp

<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8">
        {{-- toolbar --}}
        <div class="sticky top-16 z-20 bg-gray-100 flex flex-col gap-2 py-3 lg:flex-row lg:items-center lg:justify-between lg:gap-3 lg:flex-nowrap">
            <div class="flex flex-wrap items-center gap-2 min-w-0">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ ລະຫັດ/ລາຍລະອຽດ/ປະເພດ…" class="w-52 rounded-md border-gray-300 shadow-sm text-sm" />
                <select wire:model.live="supplierFilter" class="w-40 rounded-md border-gray-300 text-sm">
                    <option value="">ທຸກ Supplier</option>
                    @foreach ($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                </select>
                <select wire:model.live="categoryFilter" class="w-36 rounded-md border-gray-300 text-sm">
                    <option value="">ທຸກ ປະເພດ</option>
                    @foreach ($categories as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                </select>
                <select wire:model.live="statusFilter" class="w-28 rounded-md border-gray-300 text-sm">
                    <option value="">ທຸກ status</option>
                    <option value="active">active</option>
                    <option value="inactive">inactive</option>
                </select>
                <span class="text-xs text-gray-400 whitespace-nowrap">{{ number_format($materials->total()) }} ລາຍການ</span>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if ($canManageDeleted)
                    <button wire:click="toggleDeleted" class="text-sm border rounded-md px-3 py-2 min-h-[40px] whitespace-nowrap {{ $showDeleted ? 'bg-gray-700 text-white border-gray-700' : 'text-gray-600 border-gray-300 bg-white hover:bg-gray-50' }}">
                        {{ $showDeleted ? '← ລາຍການ ປົກກະຕິ' : '🗑 ບັນທຶກ ການ ລຶບ' }}
                    </button>
                @endif
                @can('catalog.create')<button wire:click="newItem" class="text-sm text-white bg-sky-600 rounded-md px-3 py-2 min-h-[40px] hover:bg-sky-700 whitespace-nowrap" @if ($showDeleted) style="display:none" @endif>+ Add</button>@endcan
            </div>
        </div>

        @if (session('ok'))<div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 mb-2">{{ session('ok') }}</div>@endif

        @include('partials._status-chips', ['chips' => $chips, 'current' => $statusFilter])

        {{-- Desktop table --}}
        <div class="hidden md:block bg-white border border-gray-100 rounded-lg">
            <table class="w-full text-sm">
                <thead class="sticky top-[116px] z-10 bg-gray-100 text-gray-700 border-b border-gray-200 shadow-sm">
                    <tr>
                        <th class="text-left font-semibold px-4 py-2.5">ລະຫັດ <span class="text-gray-400">(Material No.)</span></th>
                        <th class="text-left font-semibold px-4 py-2.5">ສິນຄ້າ <span class="text-gray-400">(Item)</span></th>
                        <th class="text-left font-semibold px-4 py-2.5">Supplier</th>
                        <th class="text-left font-semibold px-4 py-2.5">ລາຄາ <span class="text-gray-400">(Price)</span></th>
                        <th class="text-left font-semibold px-4 py-2.5">ສັນຍາ / ອັບເດດ</th>
                        <th class="text-left font-semibold px-4 py-2.5">Status</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($materials as $m)
                        @php $img = $m->images->first(); @endphp
                        <tr wire:key="mat-{{ $m->id }}" class="hover:bg-gray-50">
                            <td class="px-4 py-2.5 font-mono text-xs text-gray-500">{{ $m->material_nbr ?? '—' }}</td>
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-2">
                                    @if ($img)<img src="{{ $img->url }}" alt="" class="w-9 h-9 rounded object-cover border border-gray-200 shrink-0" />
                                    @else<div class="w-9 h-9 rounded bg-gray-100 border border-gray-200 shrink-0 flex items-center justify-center text-gray-300 text-xs">🏷</div>@endif
                                    <div class="min-w-0">
                                        <div class="font-medium text-gray-800 truncate max-w-xs {{ $m->is_active ? '' : 'opacity-50' }}">{{ Str::limit($m->description, 60) }}</div>
                                        <div class="text-xs text-gray-400">{{ $m->category }}@if ($m->unit) · {{ $m->unit }}@endif</div>
                                        @if ($showDeleted)
                                            <div class="text-[11px] text-red-600 mt-0.5">🗑 ລຶບ: {{ $m->deleted_at?->format('d/m/Y H:i') }} · ໂດຍ {{ $m->deletedBy?->display_name ?? '—' }}@if ($m->deleted_reason) · ເຫດຜົນ: {{ $m->deleted_reason }}@endif</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-2.5 text-xs text-gray-600">{{ $m->supplier?->name ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-gray-700">
                                @if ($m->unit_price !== null)<span class="font-medium">{{ number_format($m->unit_price, 2) }}</span> <span class="text-xs text-gray-400">{{ $m->currency }}</span>@else—@endif
                            </td>
                            <td class="px-4 py-2.5 text-xs text-gray-500">
                                <div>{{ $m->contract_number ?? '—' }}</div>
                                <div class="text-gray-400">{{ $m->last_price_update?->format('d/m/Y') ?? '' }}</div>
                            </td>
                            <td class="px-4 py-2.5"><span class="text-xs rounded px-2 py-0.5 {{ $m->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ $m->is_active ? 'active' : 'inactive' }}</span></td>
                            <td class="px-4 py-2.5 text-right whitespace-nowrap text-gray-500">
                                @if ($showDeleted)
                                    <button wire:click="restore({{ $m->id }})" class="text-xs text-emerald-700 border border-emerald-200 rounded px-2 py-1 hover:bg-emerald-50">↩ ກູ້ຄືນ</button>
                                @else
                                    <button wire:click="openHistory({{ $m->id }})" class="p-1 hover:text-sky-600" title="ປະຫວັດລາຄາ">🕑</button>
                                    @canany(['catalog.activate', 'catalog.deactivate'])<button wire:click="toggle({{ $m->id }})" class="p-1 {{ $m->is_active ? 'text-green-600 hover:text-gray-400' : 'text-gray-300 hover:text-green-600' }}" title="{{ $m->is_active ? 'Disable' : 'Enable' }}">⏻</button>@endcanany
                                    @can('catalog.edit')<button wire:click="editItem({{ $m->id }})" class="p-1 hover:text-gray-800" title="Edit">✏️</button>@endcan
                                    @can('catalog.delete')<button wire:click="openDelete({{ $m->id }})" class="p-1 hover:text-red-600" title="Delete">🗑</button>@endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">{{ $showDeleted ? 'ບໍ່ ມີ ສິນຄ້າ ທີ່ ຖືກ ລຶບ' : 'ຍັງບໍ່ມີສິນຄ້າ' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="md:hidden space-y-2">
            @forelse ($materials as $m)
                @php $img = $m->images->first(); @endphp
                <div wire:key="mmat-{{ $m->id }}" class="bg-white border border-gray-100 rounded-lg p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex gap-2 min-w-0">
                            @if ($img)<img src="{{ $img->url }}" alt="" class="w-10 h-10 rounded object-cover border border-gray-200 shrink-0" />@endif
                            <div class="min-w-0">
                                <div class="font-mono text-xs text-gray-400">{{ $m->material_nbr ?? '—' }}</div>
                                <div class="font-medium text-gray-800 truncate">{{ Str::limit($m->description, 50) }}</div>
                                <div class="text-xs text-gray-500">{{ $m->supplier?->name }} · {{ $m->unit_price !== null ? number_format($m->unit_price, 2).' '.$m->currency : '—' }}</div>
                            </div>
                        </div>
                        <span class="text-xs rounded px-2 py-0.5 shrink-0 {{ $m->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ $m->is_active ? 'active' : 'inactive' }}</span>
                    </div>
                    @if ($showDeleted)
                        <div class="text-[11px] text-red-600 mt-1">🗑 ລຶບ: {{ $m->deleted_at?->format('d/m/Y H:i') }} · ໂດຍ {{ $m->deletedBy?->display_name ?? '—' }}@if ($m->deleted_reason) · {{ $m->deleted_reason }}@endif</div>
                        <div class="flex gap-2 mt-2">
                            <button wire:click="restore({{ $m->id }})" class="text-xs text-emerald-700 border border-emerald-200 rounded px-2 py-1 min-h-[36px] hover:bg-emerald-50">↩ ກູ້ຄືນ</button>
                        </div>
                    @else
                        <div class="flex gap-2 mt-2">
                            <button wire:click="openHistory({{ $m->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px]">ປະຫວັດລາຄາ</button>
                            @can('catalog.edit')<button wire:click="editItem({{ $m->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px]">Edit</button>@endcan
                            @canany(['catalog.activate', 'catalog.deactivate'])<button wire:click="toggle({{ $m->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px]">{{ $m->is_active ? 'Disable' : 'Enable' }}</button>@endcanany
                            @can('catalog.delete')<button wire:click="openDelete({{ $m->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px]">Delete</button>@endcan
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center text-gray-400 py-6">{{ $showDeleted ? 'ບໍ່ ມີ ສິນຄ້າ ທີ່ ຖືກ ລຶບ' : 'ຍັງບໍ່ມີສິນຄ້າ' }}</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $materials->links() }}</div>
    </div>

    {{-- ຢືນຢັນ ລຶບ + ເຫດຜົນ (shared partial + trait SoftDeletesWithReason) --}}
    @include('partials._delete-modal', ['title' => 'ລຶບ ສິນຄ້າ ນີ້?', 'subtitle' => $this->deletingRecord?->description])

    {{-- Create / Edit modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="mat-modal">
            <div class="bg-white w-full md:max-w-2xl rounded-t-lg md:rounded-lg p-5 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-800">{{ $editingId ? 'ແກ້ໄຂສິນຄ້າ' : 'ເພີ່ມສິນຄ້າ' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-700 p-1">✕</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    @include('partials._form-errors', ['wrapClass' => 'md:col-span-2'])
                    <div>
                        <label class="block text-gray-600 mb-1">Supplier <span class="text-red-500">*</span></label>
                        <select wire:model="supplier_id" class="w-full rounded-md border-gray-300 text-sm" @disabled($this->isSupplierScoped())>
                            <option value="">— ເລືອກ —</option>
                            @foreach ($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                        </select>
                        @error('supplier_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-gray-600 mb-1">ລະຫັດສິນຄ້າ (Material No.)</label>
                        <input type="text" wire:model="material_nbr" class="w-full rounded-md border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-gray-600 mb-1">ປະເພດ (Category) <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="category" class="w-full rounded-md border-gray-300 text-sm" />
                        @error('category')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-gray-600 mb-1">ໜ່ວຍ (UoM)</label>
                        <select wire:model="unit" class="w-full rounded-md border-gray-300 text-sm">
                            <option value="">—</option>
                            @foreach ($uoms as $u)<option value="{{ $u->name }}">{{ $u->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-600 mb-1">ລາຍລະອຽດ (Description) <span class="text-red-500">*</span></label>
                        <textarea wire:model="description" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                        @error('description')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-gray-600 mb-1">ລາຄາ (net, ບໍ່ລວມ VAT)</label>
                        <input type="number" step="0.01" min="0" wire:model="unit_price" class="w-full rounded-md border-gray-300 text-sm" />
                        @error('unit_price')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-gray-600 mb-1">ສະກຸນເງິນ</label>
                        <select wire:model="currency" class="w-full rounded-md border-gray-300 text-sm">
                            <option value="THB">THB (ບາດ)</option>
                            <option value="LAK">LAK (ກີບ)</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-600 mb-1">Lead time</label>
                        <input type="text" wire:model="lead_time" placeholder="ເຊັ່ນ: 7-14 ມື້" class="w-full rounded-md border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-gray-600 mb-1">ເລກສັນຍາ (Contract No.)</label>
                        <input type="text" wire:model="contract_number" class="w-full rounded-md border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-gray-600 mb-1">ວັນທີສັນຍາ</label>
                        <input type="date" wire:model="contract_date" class="w-full rounded-md border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-gray-600 mb-1">ສັນຍາ ເລີ່ມ</label>
                        <input type="date" wire:model="contract_start_date" class="w-full rounded-md border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-gray-600 mb-1">ສັນຍາ ສິ້ນສຸດ</label>
                        <input type="date" wire:model="contract_end_date" class="w-full rounded-md border-gray-300 text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-600 mb-1">ໝາຍເຫດ</label>
                        <textarea wire:model="remark" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                    </div>
                    <label class="flex items-center gap-2 text-gray-700">
                        <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-sky-600" /> ເປີດໃຊ້ (active)
                    </label>
                </div>

                {{-- photos --}}
                <div>
                    <label class="block text-sm text-gray-600 mb-1">ຮູບສິນຄ້າ (≤10)</label>
                    @if ($editingId)
                        @php $existing = \App\Models\Material::find($editingId)?->images ?? collect(); @endphp
                        @if ($existing->count())
                            <div class="flex gap-1.5 flex-wrap mb-2">
                                @foreach ($existing as $p)
                                    <div class="relative"><img src="{{ $p->url }}" alt="" class="w-14 h-14 rounded object-cover border border-gray-200" />
                                        @can('catalog.edit')<button wire:click="removePhoto({{ $p->id }})" wire:confirm="ລຶບຮູບນີ້?" class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-red-600 text-white rounded-full text-[10px] leading-none">×</button>@endcan
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endif
                    <input type="file" wire:model="newPhotos" multiple accept="image/*" class="{{ $fileCls }}" />
                    <div wire:loading wire:target="newPhotos" class="text-xs text-gray-400">ກຳລັງອັບ…</div>
                    @error('newPhotos.*')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    @if (! empty($newPhotos))<div class="flex gap-1 flex-wrap mt-1">@foreach ($newPhotos as $f)@if ($f->isPreviewable())<img src="{{ $f->temporaryUrl() }}" alt="" class="w-12 h-12 rounded object-cover border border-sky-200" />@endif @endforeach</div>@endif
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button wire:click="$set('showModal', false)" class="text-sm text-gray-700 border border-gray-300 rounded-md px-4 py-2 min-h-[40px] hover:bg-gray-50">ຍົກເລີກ</button>
                    <button wire:click="save" wire:loading.attr="disabled" wire:target="save,newPhotos" class="text-sm text-white bg-sky-600 rounded-md px-4 py-2 min-h-[40px] hover:bg-sky-700 disabled:opacity-50">ບັນທຶກ</button>
                </div>
            </div>
        </div>
    @endif

    {{-- price history modal --}}
    @if ($showHistory && $historyMaterial)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-lg p-5 w-full max-w-md space-y-3 max-h-[80vh] overflow-y-auto">
                <div class="flex items-center justify-between">
                    <h3 class="font-medium text-gray-800">ປະຫວັດລາຄາ — {{ Str::limit($historyMaterial->description, 40) }}</h3>
                    <button wire:click="$set('showHistory', false)" class="text-gray-400 hover:text-gray-700 p-1">✕</button>
                </div>
                @forelse ($historyMaterial->priceHistory as $h)
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 text-sm">
                        <div>
                            <span class="font-medium text-gray-800">{{ number_format($h->unit_price, 2) }} {{ $h->currency }}</span>
                            @if ($h->contract_number)<span class="text-xs text-gray-400">· {{ $h->contract_number }}</span>@endif
                        </div>
                        <div class="text-xs text-gray-400">{{ $h->update_date?->format('d/m/Y') }}</div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">ຍັງບໍ່ມີປະຫວັດ</p>
                @endforelse
            </div>
        </div>
    @endif
</div>
