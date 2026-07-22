{{-- ກ່ອງແຈ້ງເຕືອນສີເຫຼືອງ: ຂຶ້ນເມື່ອ validate fail, ບອກຊ່ອງທີ່ຂາດ (ພາສາລາວ ຈາກ lang/lo).
     ໃຊ້: @include('partials._form-errors')  ຫຼື  @include('partials._form-errors', ['wrapClass' => 'md:col-span-2'])
     ວາງໄວ້ ເທິງສຸດ ຂອງ form/modal. auto-scroll ຂຶ້ນມາໃຫ້ເຫັນ. --}}
@if ($errors->any())
    <div class="{{ $wrapClass ?? '' }} flex items-start gap-2.5 rounded-md border border-amber-300 bg-amber-50 px-3 py-2.5" x-data x-init="$el.scrollIntoView({ behavior: 'smooth', block: 'nearest' })">
        <svg class="w-5 h-5 shrink-0 mt-0.5 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
        <div class="text-sm text-amber-800">
            <p class="font-medium">ຍັງບັນທຶກບໍ່ໄດ້ — ກະລຸນາ ຕື່ມ/ແກ້ ຊ່ອງລຸ່ມນີ້:</p>
            <ul class="mt-1 list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    </div>
@endif
