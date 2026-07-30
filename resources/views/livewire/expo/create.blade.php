<div class="pb-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        <x-page-subheader :back="route('expo')" back-label="ລາຍການ Expo" />

        @include('partials._form-errors')

        <div class="bg-white border border-gray-100 rounded-lg p-5 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            <div class="md:col-span-2"><label class="block text-gray-600 mb-1">ຊື່ງານ <span class="text-red-500">*</span></label><input type="text" wire:model="title" class="w-full rounded-md border-gray-300 text-sm" />@error('title')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror</div>
            <div><label class="block text-gray-600 mb-1">ປະເພດ / ຫົວຂໍ້</label><input type="text" wire:model="topic" class="w-full rounded-md border-gray-300 text-sm" /></div>
            <div><label class="block text-gray-600 mb-1">ປະຫວັດ / theme</label><input type="text" wire:model="background" class="w-full rounded-md border-gray-300 text-sm" /></div>
            <div><label class="block text-gray-600 mb-1">ສະຖານທີ່ (venue)</label><input type="text" wire:model="venue" class="w-full rounded-md border-gray-300 text-sm" /></div>
            <div class="grid grid-cols-2 gap-2">
                <div><label class="block text-gray-600 mb-1">ເມືອງ</label><input type="text" wire:model="city" class="w-full rounded-md border-gray-300 text-sm" /></div>
                <div><label class="block text-gray-600 mb-1">ປະເທດ</label><input type="text" wire:model="country" class="w-full rounded-md border-gray-300 text-sm" /></div>
            </div>
            <div class="md:col-span-2"><label class="block text-gray-600 mb-1">ທີ່ຢູ່ ລະອຽດ</label><input type="text" wire:model="address" class="w-full rounded-md border-gray-300 text-sm" /></div>
            <div><label class="block text-gray-600 mb-1">ວັນທີເລີ່ມ <span class="text-red-500">*</span></label><input type="date" wire:model="start_date" class="w-full rounded-md border-gray-300 text-sm" />@error('start_date')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror</div>
            <div><label class="block text-gray-600 mb-1">ວັນທີສິ້ນສຸດ</label><input type="date" wire:model="end_date" class="w-full rounded-md border-gray-300 text-sm" />@error('end_date')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror</div>
            <div><label class="block text-gray-600 mb-1">ມີຈັກບໍລິສັດ ເຂົ້າຮ່ວມ (ທັງໝົດ)</label><input type="number" min="0" wire:model="total_companies_at_expo" class="w-full rounded-md border-gray-300 text-sm" /></div>
        </div>

        <div class="bg-white border border-gray-100 rounded-lg p-5 space-y-3">
            <label class="block text-sm font-medium text-gray-700">ພະນັກງານທີ່ມອບໝາຍໃຫ້ໄປ</label>

            @php $picked = $users->whereIn('id', $attendee_ids); $avail = $users->whereNotIn('id', $attendee_ids); @endphp
            <div class="md:max-w-sm">
                <select wire:model.live="pickUser" class="w-full rounded-md border-gray-300 text-sm">
                    <option value="">+ ເລືອກ ພະນັກງານ ເພີ່ມ…</option>
                    @foreach ($avail as $u)<option value="{{ $u->id }}">{{ $u->display_name ?? $u->email }}</option>@endforeach
                </select>
            </div>

            @if ($picked->count())
                <div class="flex flex-wrap gap-2">
                    @foreach ($picked as $u)
                        <span wire:key="att-{{ $u->id }}" class="inline-flex items-center gap-1.5 bg-sky-50 text-sky-700 rounded-full px-3 py-1 text-sm">
                            {{ $u->display_name ?? $u->email }}
                            <button type="button" wire:click="removeAttendee({{ $u->id }})" class="text-sky-400 hover:text-red-600">✕</button>
                        </span>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-gray-400">ຍັງບໍ່ໄດ້ເລືອກ ພະນັກງານ</p>
            @endif
        </div>

        <div class="flex justify-end gap-2">
            <button wire:click="save" wire:loading.attr="disabled" class="text-sm text-white bg-sky-600 rounded-md px-4 py-2 min-h-[40px] hover:bg-sky-700 disabled:opacity-50">ສ້າງ Expo</button>
        </div>
    </div>
</div>
