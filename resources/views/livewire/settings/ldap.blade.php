<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @include('settings._tabs')

        <div x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)" x-show="show" style="display:none"
             class="fixed bottom-4 right-4 z-50 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 shadow-lg">ບັນທຶກແລ້ວ ✓</div>

        {{-- ── Connection ─────────────────────────────────────────── --}}
        <div class="bg-white border border-gray-100 rounded-lg p-5 space-y-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="font-medium text-gray-800">🗂 Active Directory (LDAP) · ເຊື່ອມຕໍ່ໂດເມນ</h3>
                    <p class="text-xs text-gray-500">ດຶງລາຍຊື່ຜູ້ໃຊ້ ຈາກ AD ຂອງ namtheun2.com → ສ້າງບັນຊີໄວ້ລ່ວງໜ້າ (Sync only · login ຄືເກົ່າ).</p>
                </div>
                <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 ring-1 ring-amber-200 shrink-0">Sync only</span>
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" wire:model.live="enabled" class="rounded border-gray-300 text-sky-600 focus:ring-sky-500" />
                ເປີດ ໃຊ້ AD
            </label>

            <div class="grid md:grid-cols-2 gap-4 border-t border-gray-100 pt-4">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Domain Controller (host)</label>
                    <input type="text" wire:model="host" placeholder="dc01.namtheun2.com" class="w-full rounded-md border-gray-300 text-sm" />
                    @error('host')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Port</label>
                        <input type="number" wire:model="port" class="w-full rounded-md border-gray-300 text-sm" />
                        @error('port')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Encryption</label>
                        <select wire:model="encryption" class="w-full rounded-md border-gray-300 text-sm">
                            <option value="ssl">SSL / LDAPS (636)</option>
                            <option value="tls">StartTLS (389)</option>
                            <option value="none">None (389)</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Base DN</label>
                    <input type="text" wire:model="base_dn" placeholder="DC=namtheun2,DC=com" class="w-full rounded-md border-gray-300 text-sm" />
                    @error('base_dn')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Users OU / Filter <span class="text-gray-400">(ທາງເລືອກ)</span></label>
                    <input type="text" wire:model="user_ou" placeholder="OU=Staff,DC=namtheun2,DC=com" class="w-full rounded-md border-gray-300 text-sm" />
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Bind username (authorize) 🔑</label>
                    <input type="text" wire:model="bind_username" placeholder="svc-wh@namtheun2.com" class="w-full rounded-md border-gray-300 text-sm" />
                    @error('bind_username')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">
                        Bind password (authorize) 🔒
                        @if ($hasPassword)<span class="text-green-600">— ຕັ້ງໄວ້ແລ້ວ (ວ່າງ = ຮັກສາເກົ່າ)</span>@endif
                    </label>
                    <input type="password" wire:model="password" placeholder="{{ $hasPassword ? '••••••••' : '' }}" autocomplete="new-password" class="w-full rounded-md border-gray-300 text-sm" />
                </div>
            </div>

            <label class="flex items-start gap-2 text-sm text-gray-700 border-t border-gray-100 pt-3">
                <input type="checkbox" wire:model="tlsSkipVerify" class="mt-0.5 rounded border-gray-300 text-amber-600 focus:ring-amber-500" />
                <span>
                    ຂ້າມການກວດໃບຮັບຮອງ TLS ⚠️
                    <span class="block text-xs text-gray-500">ເປີດເມື່ອ DC ໃຊ້ <b>CA ພາຍໃນ</b> ຫຼື ໃບຮັບຮອງ <b>ກະແຈອ່ອນ</b> (<code>key too weak</code>) ຫຼື ຕໍ່ດ້ວຍ <b>IP</b>. ການເຊື່ອມຕໍ່ <b>ຍັງເຂົ້າລະຫັດຢູ່</b> — ພຽງບໍ່ກວດ CA/ຄວາມແຮງກະແຈ.</span>
                </span>
            </label>

            <div class="flex flex-wrap items-center gap-3 pt-1">
                <button type="button" wire:click="test" wire:loading.attr="disabled"
                        class="h-10 px-4 rounded-lg bg-white border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-60">
                    <span wire:loading.remove wire:target="test">⚡ Test Connection</span>
                    <span wire:loading wire:target="test">ກຳລັງ ທົດສອບ…</span>
                </button>
                <button type="button" wire:click="save" wire:loading.attr="disabled"
                        class="h-10 px-5 rounded-lg bg-sky-600 text-white text-sm font-semibold hover:bg-sky-700 disabled:opacity-60">ບັນທຶກ</button>

                @if ($result)
                    <span class="text-sm px-3 py-1.5 rounded-md {{ $resultType === 'ok' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' }}">{{ $result }}</span>
                @endif
            </div>
        </div>

        {{-- ── Attribute mapping ─────────────────────────────────── --}}
        <div class="bg-white border border-gray-100 rounded-lg p-5">
            <h3 class="font-medium text-gray-800 mb-3">Attribute mapping · AD → WH</h3>
            <div class="grid md:grid-cols-2 gap-x-8 gap-y-1.5 text-sm">
                @foreach ([
                    'sAMAccountName' => 'username',
                    'mail / userPrincipalName' => 'email',
                    'displayName / cn' => 'display_name',
                    'telephoneNumber' => 'phone_number',
                    'objectGUID' => 'ad_guid (ຕົວຈັບຄູ່ຄົງທີ່)',
                    'userAccountControl' => 'enabled? (skip disabled)',
                ] as $src => $dst)
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5">
                        <code class="text-sky-700">{{ $src }}</code>
                        <span class="text-gray-400">→</span>
                        <span class="font-medium text-gray-700">{{ $dst }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ── Sync / import ─────────────────────────────────────── --}}
        <div class="bg-white border border-gray-100 rounded-lg overflow-hidden">
            <div class="p-5 flex flex-wrap items-center justify-between gap-3 border-b border-gray-100">
                <h3 class="font-medium text-gray-800">Sync / Import users</h3>
                <div class="flex items-center gap-3">
                    <label class="inline-flex items-center gap-1.5 text-xs text-gray-600">
                        <input type="checkbox" wire:model="enabledOnly" class="rounded border-gray-300 text-sky-600" /> ສະເພາະ enabled
                    </label>
                    <label class="inline-flex items-center gap-1.5 text-xs {{ $linkExisting ? 'text-amber-700 font-semibold' : 'text-gray-600' }}" title="ປິດໄວ້ = ບໍ່ແຕະ user ເກົ່າ (matched → skip). ເປີດ = ຕື່ມ ad_guid/ຊື່ ໃສ່ user ເກົ່າທີ່ຊ້ຳ.">
                        <input type="checkbox" wire:model.live="linkExisting" class="rounded border-gray-300 text-amber-600" /> Link existing ⚠️
                    </label>
                    <button type="button" wire:click="preview" wire:loading.attr="disabled"
                            class="h-9 px-3 rounded-lg bg-white border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-60">
                        <span wire:loading.remove wire:target="preview">↻ Preview from AD</span>
                        <span wire:loading wire:target="preview">ກຳລັງ ດຶງ…</span>
                    </button>
                </div>
            </div>

            @if ($rows)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs">
                            <tr>
                                <th class="text-left font-semibold px-4 py-2 w-8"></th>
                                <th class="text-left font-semibold px-4 py-2">Username</th>
                                <th class="text-left font-semibold px-4 py-2">Display name</th>
                                <th class="text-left font-semibold px-4 py-2">Email</th>
                                <th class="text-left font-semibold px-4 py-2">Department</th>
                                <th class="text-left font-semibold px-4 py-2">ສະຖານະ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($rows as $r)
                                <tr class="{{ ! $r['enabled'] ? 'opacity-55' : '' }}">
                                    <td class="px-4 py-2">
                                        <input type="checkbox" value="{{ $r['guid'] }}" wire:model="selected" @disabled(! $r['guid']) class="rounded border-gray-300 text-sky-600" />
                                    </td>
                                    <td class="px-4 py-2 font-medium text-gray-800">{{ $r['username'] ?? '—' }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $r['display_name'] ?? '—' }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ $r['email'] ?? '—' }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ $r['department'] ?? '—' }}</td>
                                    <td class="px-4 py-2">
                                        @if (! $r['enabled'])
                                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-rose-50 text-rose-600 ring-1 ring-rose-200">disabled</span>
                                        @elseif ($r['exists'])
                                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $linkExisting ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200' }} ring-1">ມີແລ້ວ · {{ $linkExisting ? 'link' : 'ແຍກໄວ້' }}</span>
                                        @else
                                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-sky-50 text-sky-700 ring-1 ring-sky-200">ໃໝ່ · New</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-5 flex flex-wrap items-center justify-between gap-3 bg-gray-50/60 border-t border-gray-100">
                    <div class="flex flex-wrap gap-2 text-xs">
                        @if ($summary)
                            <span class="px-2.5 py-0.5 rounded-full bg-sky-50 text-sky-700 ring-1 ring-sky-200">Created {{ $summary['created'] }}</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 ring-1 ring-amber-200">Matched · ແຍກໄວ້ {{ $summary['matched'] }}</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">Updated {{ $summary['updated'] }}</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-600 ring-1 ring-gray-200">Unchanged {{ $summary['unchanged'] }}</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-600 ring-1 ring-rose-200">Skipped {{ $summary['skipped'] }}</span>
                        @else
                            <span class="text-gray-500">ເລືອກ {{ count($selected) }} / {{ count($rows) }} — ຫວ່າງ = import ທັງໝົດ</span>
                        @endif
                    </div>
                    <button type="button" wire:click="importSelected" wire:confirm="ຢືນຢັນ import ບັນຊີເຂົ້າ WH?" wire:loading.attr="disabled"
                            class="h-10 px-5 rounded-lg bg-sky-600 text-white text-sm font-semibold hover:bg-sky-700 disabled:opacity-60">
                        <span wire:loading.remove wire:target="importSelected">Import selected → pre-create</span>
                        <span wire:loading wire:target="importSelected">ກຳລັງ import…</span>
                    </button>
                </div>
            @else
                <p class="px-5 py-8 text-center text-sm text-gray-400">ຍັງບໍ່ໄດ້ດຶງ — ກົດ "Preview from AD" (ຕ້ອງ run ຢູ່ server namtheun2 ທີ່ເຫັນ DC).</p>
            @endif
        </div>

        {{-- ── Kill switch / rollback ────────────────────────────── --}}
        <div class="bg-white border border-rose-100 rounded-lg p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="font-medium text-gray-800">🧯 Kill switch — imported accounts</h3>
                    <p class="text-xs text-gray-500">ບັນຊີ import ມາ (domain · pre-created): <b class="text-gray-700">{{ $importedCount }}</b> ໂຕ. ໃຊ້ຕອນຢາກຍົກເລີກ — <b>ບໍ່ແຕະ user ຈິງ</b>.</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" wire:click="disableImported" wire:confirm="ປິດ (lock) ບັນຊີ imported ທັງໝົດ?" @disabled($importedCount === 0)
                            class="h-9 px-3 rounded-lg bg-white border border-amber-300 text-xs font-semibold text-amber-700 hover:bg-amber-50 disabled:opacity-40">⏸ Disable all imported</button>
                    <button type="button" wire:click="removeImported" wire:confirm="ລຶບ ບັນຊີ imported ທັງໝົດ? (soft-delete, ກູ້ຄືນໄດ້)" @disabled($importedCount === 0)
                            class="h-9 px-3 rounded-lg bg-white border border-rose-300 text-xs font-semibold text-rose-700 hover:bg-rose-50 disabled:opacity-40">🗑 Remove all imported</button>
                </div>
            </div>
        </div>

        {{-- ── Last sync + security ──────────────────────────────── --}}
        <div class="grid md:grid-cols-2 gap-4">
            @if ($lastSync)
                <div class="bg-white border border-gray-100 rounded-lg p-5 text-sm">
                    <h3 class="font-medium text-gray-800 mb-2">Sync ຫຼ້າສຸດ</h3>
                    <p class="text-gray-600">🕓 {{ $lastSync['at'] ?? '—' }}
                        @if (! empty($lastSync['by']['display_name'])) · ໂດຍ {{ $lastSync['by']['display_name'] }} @endif</p>
                    @if (! empty($lastSync['summary']))
                        <p class="text-xs text-gray-500 mt-1">Created {{ $lastSync['summary']['created'] ?? 0 }} · Matched {{ $lastSync['summary']['matched'] ?? 0 }} · Updated {{ $lastSync['summary']['updated'] ?? 0 }} · Unchanged {{ $lastSync['summary']['unchanged'] ?? 0 }} · Skipped {{ $lastSync['summary']['skipped'] ?? 0 }}</p>
                    @endif
                </div>
            @endif
            <div class="bg-white border border-gray-100 rounded-lg p-5">
                <h3 class="font-medium text-gray-800 mb-2">🔐 ຄວາມປອດໄພ</h3>
                <ul class="text-xs text-gray-600 space-y-1 list-disc pl-5">
                    <li>Bind password <b>encrypt ໃນ DB</b> — ບໍ່ສະແດງຄືນ.</li>
                    <li>ໃຊ້ service account <b>read-only</b> · LDAPS (636).</li>
                    <li>ບັນຊີ sync = <code>domain</code> / <code>pending</code> — ບໍ່ເກັບ password ຂອງ user.</li>
                    <li><b>Keep separate</b> (default): user ຊ້ຳ = <b>ບໍ່ແຕະ</b> (matched → review). ເປີດ "Link existing" ເອງ ຕອນໝັ້ນໃຈ.</li>
                    <li>Kill switch: ຍົກເລີກ imported ໄດ້ 1 ຄລິກ (ບໍ່ແຕະ user ຈິງ).</li>
                    <li>Test / Import ຈຳກັດ <code>settings.edit</code>.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
