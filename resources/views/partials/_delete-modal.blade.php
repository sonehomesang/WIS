{{-- Shared delete-with-reason confirm modal. Pair with the SoftDeletesWithReason
     trait. Include with: @include('partials._delete-modal', [
         'title' => 'ລຶບ ສິນຄ້າ ນີ້?',            // heading
         'subtitle' => $this->deletingRecord?->name, // record label (optional)
     ]) --}}
@if ($deletingId)
    <div class="fixed inset-0 z-[56] flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="del-modal">
        <div class="bg-white w-full md:max-w-sm rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden p-4 space-y-3">
            <div class="flex items-start gap-3">
                <div class="shrink-0 w-9 h-9 rounded-full bg-red-100 text-red-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base font-semibold text-gray-900">{{ $title ?? 'ລຶບ ລາຍການ ນີ້?' }}</h3>
                    @if (! empty($subtitle))<p class="text-sm text-gray-500 truncate">{{ $subtitle }}</p>@endif
                </div>
            </div>
            <div>
                <label class="block text-sm text-gray-600 mb-1">ເຫດຜົນ ການ ລຶບ <span class="text-red-500">*</span></label>
                <textarea wire:model="deleteReason" rows="3" class="w-full rounded-md border-gray-300 text-sm focus:border-red-400 focus:ring-red-400" placeholder="{{ $placeholder ?? 'ເຊັ່ນ: ບັນທຶກ ຊ້ຳ, ໃສ່ ຂໍ້ມູນ ຜິດ, ບໍ່ ໃຊ້ ແລ້ວ…' }}"></textarea>
                @error('deleteReason')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="text-xs text-gray-400">ຈະ ຍ້າຍ ໄປ ບັນທຶກ ການ ລຶບ (ກູ້ຄືນ ໄດ້) ພ້ອມ ຈົດ ຜູ້ລຶບ + ວັນ​ເວລາ.</div>
            <div class="flex justify-end gap-2 pt-2 border-t">
                <button wire:click="$set('deletingId', null)" class="text-sm text-gray-700 bg-white border border-gray-300 rounded-lg px-4 py-2 min-h-[40px] hover:bg-gray-50">ຍົກເລີກ</button>
                <button wire:click="deleteRecord" wire:loading.attr="disabled" wire:target="deleteRecord" class="text-sm text-white bg-red-600 rounded-lg px-4 py-2 min-h-[40px] hover:bg-red-700 disabled:opacity-50 shadow-sm">🗑 ຢືນຢັນ ລຶບ</button>
            </div>
        </div>
    </div>
@endif
