@php $idleMin = \App\Support\SecuritySettings::idleTimeoutMinutes(); @endphp
@if ($idleMin > 0)
    {{-- Auto-logout ຫຼັງ ບໍ່ ມີ ການ ເຄື່ອນໄຫວ {{ $idleMin }} ນາທີ · ເຕືອນ ນັບ ຖອຍຫຼັງ 30 ວິ ກ່ອນ.
         ຈັບ input ຈິງ (ເມົາສ໌/ຄີ/ສຳຜັດ) — ບໍ່ ນັບ wire:poll ເປັນ activity. --}}
    <div
        x-cloak
        x-data="idleTimeout({{ (int) $idleMin }})"
        x-init="start()"
        @mousemove.window.passive="bump()"
        @mousedown.window.passive="bump()"
        @keydown.window="bump()"
        @scroll.window.passive="bump()"
        @touchstart.window.passive="bump()"
        @wheel.window.passive="bump()"
    >
        <template x-if="warn">
            <div class="fixed inset-0 z-[60] grid place-items-center bg-black/40 p-4" role="alertdialog" aria-modal="true">
                <div class="w-full max-w-sm rounded-2xl bg-white border border-gray-200 shadow-2xl overflow-hidden">
                    <div class="px-5 py-4 bg-amber-50 border-b border-amber-100 flex items-center gap-2">
                        <span class="text-xl">⏳</span>
                        <h2 class="font-semibold text-amber-800">ກຳລັງ ຈະ ອອກ ຈາກ ລະບົບ</h2>
                    </div>
                    <div class="px-5 py-4 text-sm text-gray-700">
                        ບໍ່ ພົບ ການ ເຄື່ອນໄຫວ — ລະບົບ ຈະ ອອກ ອັດຕະໂນມັດ ໃນ
                        <span class="font-bold text-amber-700 tabular-nums" x-text="remaining"></span> ວິນາທີ
                        ເພື່ອ ຄວາມ ປອດໄພ.
                    </div>
                    <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-2">
                        <button type="button" @click="logoutNow()"
                                class="h-9 px-4 rounded-lg bg-white border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            ອອກ ດຽວນີ້
                        </button>
                        <button type="button" @click="bump()"
                                class="h-9 px-4 rounded-lg bg-sky-600 text-white text-sm font-semibold hover:bg-sky-700">
                            ຢູ່ ຕໍ່
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <form method="POST" action="{{ route('logout') }}" x-ref="logoutForm" class="hidden">
            @csrf
            <input type="hidden" name="reason" value="idle">
        </form>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('idleTimeout', (minutes) => ({
                timeoutMs: minutes * 60000,
                warnMs: Math.min(30000, minutes * 60000 / 2),
                last: Date.now(),
                warn: false,
                remaining: 30,
                fired: false,
                timer: null,

                start() {
                    this.last = Date.now();
                    this.timer = setInterval(() => this.tick(), 1000);
                },
                bump() {
                    // ການ ເຄື່ອນໄຫວ ຈິງ → ຣີເຊັດ ໂຕ ນັບ + ປິດ ຄຳ ເຕືອນ
                    this.last = Date.now();
                    if (this.warn) this.warn = false;
                },
                tick() {
                    if (this.fired) return;
                    const elapsed = Date.now() - this.last;
                    if (elapsed >= this.timeoutMs) {
                        this.logoutNow();
                    } else if (elapsed >= this.timeoutMs - this.warnMs) {
                        this.warn = true;
                        this.remaining = Math.ceil((this.timeoutMs - elapsed) / 1000);
                    }
                },
                logoutNow() {
                    if (this.fired) return;
                    this.fired = true;
                    if (this.timer) clearInterval(this.timer);
                    this.$refs.logoutForm.submit();
                },
            }));
        });
    </script>
@endif
