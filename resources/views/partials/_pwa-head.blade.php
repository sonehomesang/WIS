{{-- PWA: installable on mobile / desktop. --}}
<meta name="theme-color" content="#0284c7">
<link rel="manifest" href="{{ route('manifest') }}">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="WH">
<script>
    // ── ໄລຍະ ທົດສອບ: ປິດ service-worker cache ໄວ້ ກ່ອນ ເພື່ອ ໃຫ້ ໂຄ້ດ ໃໝ່ ໂຫຼດ ສະເໝີ. ──
    // ຖ້າ ມີ SW ເກົ່າ ຄ້າງ → ຖອນ ອອກ + ລ້າງ cache + ໂຫຼດ ໃໝ່ ອັດຕະໂນມັດ 1 ເທື່ອ.
    // (ກ່ອນ ຂຶ້ນ production ໃຫ້ ເປີດ register('/sw.js') ຄືນ.)
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistrations().then(function (regs) {
            if (!regs.length) {
                return;
            }
            Promise.all(regs.map(function (r) { return r.unregister(); }))
                .then(function () {
                    return window.caches ? caches.keys().then(function (ks) {
                        return Promise.all(ks.map(function (k) { return caches.delete(k); }));
                    }) : null;
                })
                .then(function () { window.location.reload(); });
        });
    }
</script>
