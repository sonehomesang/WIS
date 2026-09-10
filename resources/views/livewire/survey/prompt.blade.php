<div>
    @if ($show)
        <div x-data="{ open: (() => { try { return sessionStorage.getItem('survey_prompt_{{ $campaignKey }}') !== '1'; } catch (e) { return true; } })() }"
             x-show="open" x-cloak
             class="fixed inset-0 z-[70] flex items-end sm:items-center justify-center p-4 bg-black/40">
            <div class="w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0">
                <div class="px-5 py-4 bg-gradient-to-b from-sky-100 to-sky-50 border-b border-sky-100 flex items-start gap-3">
                    <span class="w-9 h-9 rounded-xl bg-white/70 grid place-items-center text-xl shrink-0">📝</span>
                    <div>
                        <h2 class="font-bold text-gray-800 leading-tight">ແບບສອບຖາມ ຄວາມເພິ່ງພໍໃຈ</h2>
                        <p class="text-[11px] text-gray-500">Satisfaction survey · ~2 ນາທີ</p>
                    </div>
                </div>
                <div class="px-5 py-4 text-sm text-gray-600">
                    ຄຳຕອບ ຂອງ ທ່ານ ຊ່ວຍ ໃຫ້ ພວກເຮົາ ປັບປຸງ ການ ບໍລິການ ໃຫ້ ດີ ຂຶ້ນ. ຕອບ ດຽວນີ້ ເລີຍ ບໍ?
                </div>
                <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-2">
                    <button type="button"
                            @click="try { sessionStorage.setItem('survey_prompt_{{ $campaignKey }}', '1'); } catch (e) {} open = false"
                            class="h-9 px-4 rounded-lg bg-white border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        ໄວ້ ພາຍຫຼັງ
                    </button>
                    <a href="{{ route('survey') }}"
                       class="h-9 px-4 rounded-lg bg-sky-600 text-white text-sm font-semibold hover:bg-sky-700 grid place-items-center">
                        ຕອບ ເລີຍ
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
