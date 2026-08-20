@php
    $statusMeta = fn ($s) => $s === 'finalized' ? ['ສຳເລັດແລ້ວ', 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'] : ['ຮ່າງ (ກຳລັງເຮັດ)', 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'];
    [$slbl, $scls] = $statusMeta($record->status);
    $lvl = ['hot' => ['hot', 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'], 'warm' => ['warm', 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'], 'cold' => ['cold', 'bg-sky-50 text-sky-700 ring-1 ring-sky-200']];
    $roleLbl = ['agent' => 'ຕົວແທນ', 'representative' => 'representative', 'direct_employee' => 'direct employee', 'other' => 'other'];
    $kindLbl = ['product' => 'product', 'booth' => 'booth', 'brochure' => 'brochure'];
    $fileCls = 'block w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-2 file:rounded file:border-0 file:bg-sky-50 file:text-sky-700';
    $hot = $record->companies->where('interest_level', 'hot')->count();
@endphp

<div class="pb-6" x-data="{ tab: 'companies' }">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        {{-- ══ ONE frozen identity row (back · icon · record# · status · facts · actions) ══ --}}
        <div class="sticky top-16 z-30 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-2.5 bg-gradient-to-b from-sky-100 to-white border-b border-sky-200/70 backdrop-blur flex items-center gap-3 flex-wrap">
            <a href="{{ route('expo') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                <span class="hidden sm:inline">ລາຍການ Expo</span>
            </a>
            <span class="w-px h-5 bg-sky-200 shrink-0"></span>
            <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-sky-400 text-white grid place-items-center text-lg shadow-sm shrink-0">🎪</span>
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-base font-bold text-gray-900">{{ $record->title }}</span>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full {{ $scls }}">{{ $slbl }}</span>
                </div>
                <div class="hidden md:flex items-center gap-x-3 gap-y-0.5 flex-wrap text-[11px] text-gray-500 mt-0.5">
                    <span class="inline-flex items-center gap-1 font-mono">{{ $record->expo_number }}</span>
                    <span class="inline-flex items-center gap-1">📍 {{ collect([$record->venue, $record->city, $record->country])->filter()->implode(', ') ?: '—' }}</span>
                    <span class="inline-flex items-center gap-1">🗓 {{ $record->start_date?->format('d/m/Y') }}@if ($record->end_date)–{{ $record->end_date->format('d/m/Y') }}@endif</span>
                    @if ($record->topic)<span class="inline-flex items-center gap-1">{{ $record->topic }}</span>@endif
                </div>
            </div>
            <div class="ml-auto flex items-center gap-2 shrink-0">
                @if ($editable)
                    <button wire:click="openEvent" class="inline-flex items-center gap-1.5 text-sm text-sky-700 border border-sky-200 rounded-md px-3 py-1.5 min-h-[36px] hover:bg-sky-50">✏️ ແກ້ໄຂ ຂໍ້ມູນງານ</button>
                    @if ($record->status === 'draft')<button wire:click="finalize" title="ລັອກ — ບໍ່ໃຫ້ແກ້ໄຂຕໍ່" class="inline-flex items-center gap-1.5 text-sm text-white bg-emerald-600 rounded-md px-3 py-1.5 min-h-[36px] hover:bg-emerald-700">✓ ສະຫຼຸບ/ປິດງານ</button>
                    @else<button wire:click="reopen" class="inline-flex items-center gap-1.5 text-sm text-gray-700 border border-gray-300 rounded-md px-3 py-1.5 min-h-[36px] hover:bg-gray-50">↩ ເປີດແກ້ໄຂຄືນ</button>@endif
                @endif
                <a href="{{ route('expo.pdf', $record) }}" class="inline-flex items-center gap-1.5 text-sm text-gray-700 border border-gray-300 rounded-md px-3 py-1.5 min-h-[36px] hover:bg-gray-50">📄 PDF report</a>
                @if ($deletable)<button wire:click="openDelete" class="inline-flex items-center gap-1.5 text-sm text-red-600 border border-red-200 rounded-md px-3 py-1.5 min-h-[36px] hover:bg-red-50">🗑</button>@endif
            </div>
        </div>

        @if (session('ok'))<div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2">{{ session('ok') }}</div>@endif

        {{-- KPI tiles --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 flex items-center gap-3"><span class="w-9 h-9 rounded-xl bg-gray-50 text-gray-600 grid place-items-center shrink-0">🏢</span><div class="min-w-0"><div class="text-2xl font-bold tabular-nums text-gray-800">{{ $record->total_companies_at_expo ?? '—' }}</div><div class="text-[11px] text-gray-500">ບໍລິສັດໃນງານ</div></div></div>
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 flex items-center gap-3"><span class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 grid place-items-center shrink-0">⭐</span><div class="min-w-0"><div class="text-2xl font-bold tabular-nums text-amber-700">{{ $record->companies->count() }}</div><div class="text-[11px] text-gray-500">ໜ້າສົນໃຈ</div></div></div>
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 flex items-center gap-3"><span class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 grid place-items-center shrink-0">🔥</span><div class="min-w-0"><div class="text-2xl font-bold tabular-nums text-rose-600">{{ $hot }}</div><div class="text-[11px] text-gray-500">hot</div></div></div>
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 flex items-center gap-3"><span class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 grid place-items-center shrink-0">👤</span><div class="min-w-0"><div class="text-2xl font-bold tabular-nums text-sky-700">{{ $record->attendees->count() }}</div><div class="text-[11px] text-gray-500">ຜູ້ໄປ</div></div></div>
        </div>

        {{-- tabs --}}
        <div class="bg-white border border-gray-100 rounded-lg overflow-hidden">
            <div class="flex gap-1 border-b border-gray-200 px-2 text-sm">
                <button @click="tab='companies'" :class="tab==='companies' ? 'border-sky-600 text-gray-800 font-medium' : 'border-transparent text-gray-500'" class="px-3 py-2.5 border-b-2">ບໍລິສັດໜ້າສົນໃຈ ({{ $record->companies->count() }})</button>
                <button @click="tab='attendees'" :class="tab==='attendees' ? 'border-sky-600 text-gray-800 font-medium' : 'border-transparent text-gray-500'" class="px-3 py-2.5 border-b-2">ຜູ້ໄປ + ຄວາມຄິດເຫັນ</button>
                <button @click="tab='overview'" :class="tab==='overview' ? 'border-sky-600 text-gray-800 font-medium' : 'border-transparent text-gray-500'" class="px-3 py-2.5 border-b-2">ພາບລວມ + ຄັ້ງຕໍ່ໄປ</button>
            </div>

            {{-- COMPANIES --}}
            <div x-show="tab==='companies'" class="p-4 space-y-3">
                @if ($editable)<div class="flex justify-end"><button wire:click="openCompany" class="text-sm text-sky-700 border border-sky-200 bg-sky-50 rounded-md px-3 py-1.5">+ ເພີ່ມ ບໍລິສັດ</button></div>@endif
                @forelse ($record->companies as $c)
                    @php [$llbl, $lcls] = $lvl[$c->interest_level] ?? $lvl['warm']; @endphp
                    <div wire:key="co-{{ $c->id }}" class="border border-gray-200 rounded-md p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="font-medium text-gray-800">{{ $c->name }} <span class="text-xs rounded-full px-2 py-0.5 {{ $lcls }}">{{ $llbl }}</span></div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ collect([$c->country, $c->website, $c->email, $c->phone])->filter()->implode(' · ') }}</div>
                            </div>
                            <div class="text-right whitespace-nowrap">
                                <div class="text-amber-500">{!! str_repeat('★', (int) ($c->score ?? 0)) !!}<span class="text-gray-300">{!! str_repeat('★', 5 - (int) ($c->score ?? 0)) !!}</span></div>
                                @if ($editable)<div class="mt-1 flex gap-1 justify-end"><button wire:click="openCompany({{ $c->id }})" class="text-xs text-gray-500 hover:text-gray-800">✏️</button><button wire:click="deleteCompany({{ $c->id }})" wire:confirm="ລຶບ ບໍລິສັດ?" class="text-xs text-gray-400 hover:text-red-600">🗑</button></div>@endif
                            </div>
                        </div>
                        @if ($c->products)<div class="text-sm mt-2"><span class="text-gray-500">ສິນຄ້າ:</span> {{ $c->products }}</div>@endif
                        @if ($c->benefit)<div class="text-sm"><span class="text-gray-500">ເໝາະກັບເຮົາ:</span> {{ $c->benefit }}</div>@endif
                        @if ($c->files->count())
                            <div class="flex gap-2 flex-wrap mt-2">
                                @foreach ($c->files as $f)
                                    <div class="relative"><img src="{{ $f->url }}" alt="" class="w-14 h-14 rounded object-cover border border-gray-200" /><span class="absolute top-0 left-0 bg-black/60 text-white text-[9px] px-1 rounded-br">{{ $kindLbl[$f->kind] ?? $f->kind }}</span>@if ($editable)<button wire:click="removeCompanyFile({{ $f->id }})" wire:confirm="ລຶບຮູບ?" class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-red-600 text-white rounded-full text-[10px] leading-none">×</button>@endif</div>
                                @endforeach
                            </div>
                        @endif
                        {{-- contacts --}}
                        <div class="border-t border-gray-100 mt-2 pt-2">
                            <div class="flex items-center justify-between mb-1"><span class="text-xs text-gray-500">ຜູ້ຕິດຕໍ່ ({{ $c->contacts->count() }})</span>@if ($editable)<button wire:click="openContact({{ $c->id }})" class="text-xs text-sky-600">+ ເພີ່ມ</button>@endif</div>
                            <div class="grid md:grid-cols-2 gap-2">
                                @foreach ($c->contacts as $k)
                                    <div wire:key="ct-{{ $k->id }}" class="bg-gray-50 rounded-md p-2 text-sm">
                                        <div class="flex items-start justify-between"><div class="font-medium">{{ $k->name }} @if ($k->title)<span class="text-xs text-gray-400">· {{ $k->title }}</span>@endif</div>@if ($editable)<span class="whitespace-nowrap"><button wire:click="openContact({{ $c->id }}, {{ $k->id }})" class="text-xs text-gray-400 hover:text-gray-700">✏️</button> <button wire:click="deleteContact({{ $k->id }})" wire:confirm="ລຶບ?" class="text-xs text-gray-400 hover:text-red-600">×</button></span>@endif</div>
                                        <div class="text-xs text-gray-500">{{ collect([$k->email, $k->phone, $k->app_contact])->filter()->implode(' · ') }}</div>
                                        <span class="text-[10px] rounded px-1.5 py-0.5 bg-indigo-50 text-indigo-700">{{ $roleLbl[$k->role] ?? $k->role }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-400 py-6 text-sm">ຍັງບໍ່ມີ ບໍລິສັດ</div>
                @endforelse
            </div>

            {{-- ATTENDEES --}}
            <div x-show="tab==='attendees'" x-cloak class="p-4 space-y-3">
                @if ($editable)
                    <div class="md:max-w-sm">
                        <label class="block text-xs text-gray-500 mb-1">ເພີ່ມ ພະນັກງານ ຜູ້ໄປ</label>
                        <select wire:model.live="pickUser" class="w-full rounded-md border-gray-300 text-sm">
                            <option value="">+ ເລືອກ ພະນັກງານ ເພີ່ມ…</option>
                            @foreach ($availableUsers as $u)<option value="{{ $u->id }}">{{ $u->display_name ?? $u->email }}</option>@endforeach
                        </select>
                    </div>
                @endif
                @forelse ($record->attendees as $a)
                    <div wire:key="at-{{ $a->id }}" class="border-b border-gray-50 pb-2">
                        <div class="flex items-center justify-between gap-2">
                            <div class="text-sm font-medium text-gray-700">{{ $a->user_name }}</div>
                            @if ($editable)<button wire:click="removeAttendee({{ $a->id }})" wire:confirm="ລຶບ {{ $a->user_name }} ອອກຈາກຜູ້ໄປ?" class="text-xs text-red-500 hover:text-red-700">✕ ລຶບ</button>@endif
                        </div>
                        @if ($editable)
                            <textarea wire:model="opinions.{{ $a->id }}" rows="2" placeholder="ຄວາມຄິດເຫັນ…" class="w-full rounded-md border-gray-300 text-sm mt-1"></textarea>
                        @else
                            <div class="text-sm text-gray-600 border-l-2 border-sky-300 pl-2">{{ $a->opinion ?: '—' }}</div>
                        @endif
                    </div>
                @empty
                    <div class="text-center text-gray-400 py-6 text-sm">ບໍ່ມີ ຜູ້ໄປ</div>
                @endforelse
                @if ($editable && $record->attendees->count())<div class="flex justify-end"><button wire:click="saveOpinions" class="text-sm text-white bg-sky-600 rounded-md px-4 py-2">ບັນທຶກ ຄວາມຄິດເຫັນ</button></div>@endif
            </div>

            {{-- OVERVIEW --}}
            <div x-show="tab==='overview'" x-cloak class="p-4 space-y-3">
                <div><label class="block text-sm text-gray-600 mb-1">ພາບລວມ / ຄວາມໜ້າສົນໃຈ</label>
                    @if ($editable)<textarea wire:model="feedback" rows="3" class="w-full rounded-md border-gray-300 text-sm"></textarea>@else<p class="text-sm text-gray-600">{{ $record->feedback ?: '—' }}</p>@endif
                </div>
                <div><label class="block text-sm text-gray-600 mb-1">ຄັ້ງຕໍ່ໄປ ຈັດຢູ່ໃສ</label>
                    @if ($editable)<input type="text" wire:model="nextLocation" class="w-full rounded-md border-gray-300 text-sm" />@else<p class="text-sm text-gray-600">{{ $record->next_event_location ?: '—' }}</p>@endif
                </div>
                <div><label class="block text-sm text-gray-600 mb-1">ຂໍ້ສະເໜີ ຄັ້ງຕໍ່ໄປ</label>
                    @if ($editable)<textarea wire:model="nextProposal" rows="3" class="w-full rounded-md border-gray-300 text-sm"></textarea>@else<p class="text-sm text-gray-600">{{ $record->next_proposal ?: '—' }}</p>@endif
                </div>
                @if ($editable)<div class="flex justify-end"><button wire:click="saveOverview" class="text-sm text-white bg-sky-600 rounded-md px-4 py-2">ບັນທຶກ</button></div>@endif
            </div>
        </div>

        {{-- ── company modal ── --}}
        {{-- ── edit event meta modal ── --}}
        @if ($showEvent)
            <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4">
                <div class="bg-white w-full md:max-w-lg rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-sky-200 to-sky-100 shrink-0">
                        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-sky-400 text-white grid place-items-center text-lg shadow-sm shrink-0">🎪</span>
                        <h3 class="text-base font-semibold text-gray-800">✏️ ແກ້ໄຂ ຂໍ້ມູນງານ</h3>
                        <button wire:click="$set('showEvent', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                    </div>
                    <div class="p-5 space-y-3 overflow-y-auto">
                    <div><label class="block text-xs text-gray-500 mb-1">ຊື່ງານ *</label><input type="text" wire:model="ef.title" class="w-full rounded-md border-gray-300 text-sm" />@error('ef.title')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div class="grid grid-cols-2 gap-2">
                        <div><label class="block text-xs text-gray-500 mb-1">ປະເພດ / ຫົວຂໍ້</label><input type="text" wire:model="ef.topic" class="w-full rounded-md border-gray-300 text-sm" /></div>
                        <div><label class="block text-xs text-gray-500 mb-1">ປະຫວັດ / theme</label><input type="text" wire:model="ef.background" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    </div>
                    <div><label class="block text-xs text-gray-500 mb-1">ສະຖານທີ່ (venue)</label><input type="text" wire:model="ef.venue" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div class="grid grid-cols-2 gap-2">
                        <div><label class="block text-xs text-gray-500 mb-1">ເມືອງ</label><input type="text" wire:model="ef.city" class="w-full rounded-md border-gray-300 text-sm" /></div>
                        <div><label class="block text-xs text-gray-500 mb-1">ປະເທດ</label><input type="text" wire:model="ef.country" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    </div>
                    <div><label class="block text-xs text-gray-500 mb-1">ທີ່ຢູ່ ລະອຽດ</label><input type="text" wire:model="ef.address" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div class="grid grid-cols-2 gap-2">
                        <div><label class="block text-xs text-gray-500 mb-1">ວັນທີເລີ່ມ *</label><input type="date" wire:model="ef.start_date" class="w-full rounded-md border-gray-300 text-sm" />@error('ef.start_date')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div><label class="block text-xs text-gray-500 mb-1">ວັນທີສິ້ນສຸດ</label><input type="date" wire:model="ef.end_date" class="w-full rounded-md border-gray-300 text-sm" />@error('ef.end_date')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    </div>
                    <div><label class="block text-xs text-gray-500 mb-1">ມີຈັກບໍລິສັດ ເຂົ້າຮ່ວມ (ທັງໝົດ)</label><input type="number" min="0" wire:model="ef.total_companies_at_expo" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    </div>
                    <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showEvent', false)" class="bg-white border rounded-lg px-4 py-2 text-sm min-h-[40px]">ປິດ</button><button wire:click="saveEvent" wire:loading.attr="disabled" wire:target="saveEvent" class="bg-sky-600 hover:bg-sky-700 text-white rounded-lg px-4 py-2 text-sm min-h-[40px] shadow-sm disabled:opacity-50">ບັນທຶກ</button></div>
                </div>
            </div>
        @endif

        @if ($showCompany)
            <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4">
                <div class="bg-white w-full md:max-w-lg rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-sky-200 to-sky-100 shrink-0">
                        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-sky-400 text-white grid place-items-center text-lg shadow-sm shrink-0">🎪</span>
                        <h3 class="text-base font-semibold text-gray-800">{{ $companyId ? 'ແກ້ໄຂ' : 'ເພີ່ມ' }} ບໍລິສັດ</h3>
                        <button wire:click="$set('showCompany', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                    </div>
                    <div class="p-5 space-y-3 overflow-y-auto">
                    <div><label class="block text-xs text-gray-500 mb-1">ຊື່ບໍລິສັດ *</label><input type="text" wire:model="cf.name" class="w-full rounded-md border-gray-300 text-sm" />@error('cf.name')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div class="grid grid-cols-2 gap-2">
                        <div><label class="block text-xs text-gray-500 mb-1">ປະເທດ</label><input type="text" wire:model="cf.country" class="w-full rounded-md border-gray-300 text-sm" /></div>
                        <div><label class="block text-xs text-gray-500 mb-1">Website</label><input type="text" wire:model="cf.website" class="w-full rounded-md border-gray-300 text-sm" /></div>
                        <div><label class="block text-xs text-gray-500 mb-1">Email</label><input type="text" wire:model="cf.email" class="w-full rounded-md border-gray-300 text-sm" /></div>
                        <div><label class="block text-xs text-gray-500 mb-1">ໂທ</label><input type="text" wire:model="cf.phone" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    </div>
                    <div><label class="block text-xs text-gray-500 mb-1">ສິນຄ້າ / ບໍລິການ</label><textarea wire:model="cf.products" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea></div>
                    <div><label class="block text-xs text-gray-500 mb-1">ປະໂຫຍດ / ເໝາະກັບເຮົາ</label><textarea wire:model="cf.benefit" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea></div>
                    <div class="grid grid-cols-2 gap-2">
                        <div><label class="block text-xs text-gray-500 mb-1">ລະດັບຄວາມສົນໃຈ</label><select wire:model="cf.interest_level" class="w-full rounded-md border-gray-300 text-sm"><option value="hot">hot</option><option value="warm">warm</option><option value="cold">cold</option></select></div>
                        <div><label class="block text-xs text-gray-500 mb-1">ຄະແນນ (1–5)</label><select wire:model="cf.score" class="w-full rounded-md border-gray-300 text-sm"><option value="">—</option>@for ($i = 1; $i <= 5; $i++)<option value="{{ $i }}">{{ $i }} ★</option>@endfor</select></div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">ເພີ່ມ ຮູບ (kind)</label>
                        <div class="flex gap-2">
                            <select wire:model="companyFileKind" class="rounded-md border-gray-300 text-sm"><option value="product">product</option><option value="booth">booth</option><option value="brochure">brochure</option></select>
                            <input type="file" wire:model="companyFiles" multiple accept="image/*" class="{{ $fileCls }} flex-1" />
                        </div>
                        <div wire:loading wire:target="companyFiles" class="text-xs text-gray-400">ກຳລັງອັບ…</div>
                    </div>
                    </div>
                    <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showCompany', false)" class="bg-white border rounded-lg px-4 py-2 text-sm min-h-[40px]">ປິດ</button><button wire:click="saveCompany" wire:loading.attr="disabled" wire:target="saveCompany,companyFiles" class="bg-sky-600 hover:bg-sky-700 text-white rounded-lg px-4 py-2 text-sm min-h-[40px] shadow-sm disabled:opacity-50">ບັນທຶກ</button></div>
                </div>
            </div>
        @endif

        {{-- ── contact modal ── --}}
        @if ($showContact)
            <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4">
                <div class="bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-sky-200 to-sky-100 shrink-0">
                        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-sky-400 text-white grid place-items-center text-lg shadow-sm shrink-0">🎪</span>
                        <h3 class="text-base font-semibold text-gray-800">{{ $contactId ? 'ແກ້ໄຂ' : 'ເພີ່ມ' }} ຜູ້ຕິດຕໍ່</h3>
                        <button wire:click="$set('showContact', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                    </div>
                    <div class="p-5 space-y-3 overflow-y-auto">
                    <div><label class="block text-xs text-gray-500 mb-1">ຊື່ *</label><input type="text" wire:model="kf.name" class="w-full rounded-md border-gray-300 text-sm" />@error('kf.name')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div class="grid grid-cols-2 gap-2">
                        <div><label class="block text-xs text-gray-500 mb-1">ປະເພດ</label><select wire:model="kf.role" class="w-full rounded-md border-gray-300 text-sm"><option value="direct_employee">direct employee</option><option value="agent">ຕົວແທນ (agent)</option><option value="representative">representative</option><option value="other">other</option></select></div>
                        <div><label class="block text-xs text-gray-500 mb-1">ຕຳແໜ່ງ</label><input type="text" wire:model="kf.title" class="w-full rounded-md border-gray-300 text-sm" /></div>
                        <div><label class="block text-xs text-gray-500 mb-1">Email</label><input type="text" wire:model="kf.email" class="w-full rounded-md border-gray-300 text-sm" /></div>
                        <div><label class="block text-xs text-gray-500 mb-1">ໂທ</label><input type="text" wire:model="kf.phone" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    </div>
                    <div><label class="block text-xs text-gray-500 mb-1">ເບີ app (WhatsApp/WeChat)</label><input type="text" wire:model="kf.app_contact" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div><label class="block text-xs text-gray-500 mb-1">ໝາຍເຫດ</label><textarea wire:model="kf.notes" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea></div>
                    <div><label class="block text-xs text-gray-500 mb-1">ນາມບັດ (business card)</label><input type="file" wire:model="businessCard" accept="image/*" class="{{ $fileCls }}" /><div wire:loading wire:target="businessCard" class="text-xs text-gray-400">ກຳລັງອັບ…</div></div>
                    </div>
                    <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showContact', false)" class="bg-white border rounded-lg px-4 py-2 text-sm min-h-[40px]">ປິດ</button><button wire:click="saveContact" wire:loading.attr="disabled" wire:target="saveContact,businessCard" class="bg-sky-600 hover:bg-sky-700 text-white rounded-lg px-4 py-2 text-sm min-h-[40px] shadow-sm disabled:opacity-50">ບັນທຶກ</button></div>
                </div>
            </div>
        @endif

        @if ($showDelete)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="bg-white w-full md:max-w-sm rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-sky-200 to-sky-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-sky-400 text-white grid place-items-center text-lg shadow-sm shrink-0">🗑</span>
                    <h3 class="text-base font-semibold text-red-700">🗑 ລຶບ Expo</h3>
                    <button wire:click="$set('showDelete', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">
                    <textarea wire:model="deleteReason" rows="3" placeholder="ເຫດຜົນ…" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                    @error('deleteReason')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showDelete', false)" class="bg-white border rounded-lg px-3 py-1.5 text-sm min-h-[40px]">ປິດ</button><button wire:click="deleteRecord" class="bg-red-600 text-white rounded-lg px-3 py-1.5 text-sm min-h-[40px]">ຢືນຢັນລຶບ</button></div>
            </div></div>
        @endif
    </div>
</div>
