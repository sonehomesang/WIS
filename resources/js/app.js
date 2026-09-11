import './bootstrap';

// Force the PWA to the latest version: refresh the service worker, drop every
// cache, then hard-reload. Wired to the "update app" button by the bell.
window.updateApp = async () => {
    try {
        if ('serviceWorker' in navigator) {
            const regs = await navigator.serviceWorker.getRegistrations();
            await Promise.all(regs.map((r) => r.update().catch(() => {})));
        }
        if (window.caches) {
            const keys = await caches.keys();
            await Promise.all(keys.map((k) => caches.delete(k)));
        }
    } catch (e) {
        console.error('update failed', e);
    } finally {
        window.location.reload();
    }
};

// Heavy libs (chart.js, jspdf, html-to-image) are loaded on demand so only
// the dashboard / export actions pay for them — every other page stays light.

const CHART_PALETTE = ['#0ea5e9', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#6366f1', '#14b8a6', '#9ca3af'];

let chartRegistered = false;
async function loadChart() {
    const m = await import('chart.js');
    if (!chartRegistered) {
        m.Chart.register(
            m.PieController, m.ArcElement, m.BarController, m.BarElement,
            m.CategoryScale, m.LinearScale, m.Tooltip, m.Legend,
        );
        chartRegistered = true;
    }
    return m.Chart;
}

// Dashboard charts — registered as an Alpine component. The canvases live
// inside a wire:ignore block so Livewire re-renders don't wipe them.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('dashCharts', (data) => ({
        pieChart: null,
        barChart: null,
        async init() {
            const Chart = await loadChart();
            if (data?.pie?.labels?.length) {
                this.pieChart = new Chart(this.$refs.pie, {
                    type: 'doughnut',
                    data: { labels: data.pie.labels, datasets: [{ data: data.pie.data, backgroundColor: CHART_PALETTE, borderWidth: 0 }] },
                    options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'right', labels: { boxWidth: 10, font: { size: 11 } } } } },
                });
            }
            if (data?.bar?.labels?.length) {
                this.barChart = new Chart(this.$refs.bar, {
                    type: 'bar',
                    data: {
                        labels: data.bar.labels,
                        datasets: [
                            { label: 'Borrow', data: data.bar.borrow, backgroundColor: '#6366f1' },
                            { label: 'Request', data: data.bar.request, backgroundColor: '#0ea5e9' },
                            { label: 'Deposit', data: data.bar.deposit, backgroundColor: '#10b981' },
                        ],
                    },
                    options: { responsive: true, maintainAspectRatio: false, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } }, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } } },
                });
            }
        },
        destroy() {
            this.pieChart?.destroy();
            this.barChart?.destroy();
        },
    }));
});

// ── Client-side photo optimize ────────────────────────────────────────────
// Shrink a camera/gallery image to Full-HD (max 1920px edge) and re-encode as
// JPEG before it ever leaves the phone. A 5 MB shot becomes ~300–500 KB, so
// uploads are fast on field mobile data and never hit server size limits.
async function compressImage(file, maxEdge = 1920, quality = 0.82) {
    if (!file || !file.type || !file.type.startsWith('image/')) {
        return file;
    }
    try {
        const dataUrl = await new Promise((res, rej) => {
            const r = new FileReader();
            r.onload = () => res(r.result);
            r.onerror = rej;
            r.readAsDataURL(file);
        });
        const img = await new Promise((res, rej) => {
            const i = new Image();
            i.onload = () => res(i);
            i.onerror = rej;
            i.src = dataUrl;
        });
        const longest = Math.max(img.width, img.height);
        const scale = Math.min(1, maxEdge / longest);
        // Already small in both dimensions and file size → leave as-is.
        if (scale >= 1 && file.size <= 1.5 * 1024 * 1024) {
            return file;
        }
        const w = Math.round(img.width * scale);
        const h = Math.round(img.height * scale);
        const canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        canvas.getContext('2d').drawImage(img, 0, 0, w, h);
        const blob = await new Promise((res) => canvas.toBlob(res, 'image/jpeg', quality));
        if (!blob || blob.size >= file.size) {
            return file; // compression didn't help (rare) → keep original
        }
        const name = (file.name || 'photo').replace(/\.[^.]+$/, '') + '.jpg';
        return new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() });
    } catch (e) {
        console.error('image compress failed, uploading original', e);
        return file;
    }
}

// Alpine component for one photo slot (item index + slot name). Both the camera
// and gallery inputs share it, so a single `busy` flag covers compress+upload.
// Files are compressed then handed to Livewire via uploadMultiple() targeting
// the nested property (e.g. "camUpload.0.overall"), keeping the absorb flow.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('photoSlot', (camBase, galBase, slot) => ({
        busy: false,
        async upload(e, kind) {
            const files = Array.from(e.target.files || []);
            if (!files.length) {
                return;
            }
            this.busy = true;
            try {
                const out = [];
                for (const f of files) {
                    out.push(await compressImage(f));
                }
                const prop = (kind === 'cam' ? camBase : galBase) + '.' + slot;
                await new Promise((resolve) => {
                    this.$wire.uploadMultiple(prop, out, resolve, resolve);
                });
            } finally {
                this.busy = false;
                e.target.value = ''; // allow re-picking the same file
            }
        },
    }));

    // Generic single-input photo uploader: compresses every picked file then
    // hands them to Livewire via uploadMultiple() targeting a flat property
    // (e.g. "photos.0" or "storePhotos.42"). For inputs that aren't the 3-slot kind.
    window.Alpine.data('photoInput', (prop) => ({
        busy: false,
        async pick(e) {
            const files = Array.from(e.target.files || []);
            if (!files.length) {
                return;
            }
            this.busy = true;
            try {
                const out = [];
                for (const f of files) {
                    out.push(await compressImage(f));
                }
                await new Promise((resolve) => {
                    this.$wire.uploadMultiple(prop, out, resolve, resolve);
                });
            } finally {
                this.busy = false;
                e.target.value = '';
            }
        },
    }));

    // Single-file variant: compresses one image then $wire.upload() to a scalar
    // property (e.g. "itemPhotos.0"), for inputs that hold ONE file, not an array.
    window.Alpine.data('photoInputOne', (prop) => ({
        busy: false,
        async pick(e) {
            const file = (e.target.files || [])[0];
            if (!file) {
                return;
            }
            this.busy = true;
            try {
                const one = await compressImage(file);
                await new Promise((resolve) => {
                    this.$wire.upload(prop, one, resolve, resolve);
                });
            } finally {
                this.busy = false;
                e.target.value = '';
            }
        },
    }));
});

// html2canvas / image failures often surface as an Event (which prints as "[object Event]");
// pull out something human-readable for the error alert.
function errText(e) {
    if (!e) return 'ບໍ່ ຮູ້ ສາເຫດ';
    if (e.message) return e.message;
    if (typeof Event !== 'undefined' && e instanceof Event) {
        return (e.type || 'error') + (e.target && e.target.src ? ' @ ' + e.target.src : '');
    }
    return String(e);
}

// Reject if `promise` does not settle within `ms` — keeps a stalled capture from leaving
// the button spinning forever with no feedback (the caller's catch shows the error alert).
function withTimeout(promise, ms, label) {
    let t;
    const timeout = new Promise((_, reject) => {
        t = setTimeout(
            () => reject(new Error((label || 'ດຳເນີນການ') + ' ໃຊ້ ເວລາ ດົນ ເກີນ ' + Math.round(ms / 1000) + ' ວິ (timeout)')),
            ms,
        );
    });
    return Promise.race([promise, timeout]).finally(() => clearTimeout(t));
}

// Wait for every <img> inside the node to finish loading before capture — html-to-image
// rejects the whole render if an image is still pending, which makes the button "do nothing".
// Guard against hangs: an image that is already `complete` (loaded OR broken — a broken one is
// complete with naturalWidth 0, and its onload/onerror will never fire again) resolves at once;
// a still-pending image resolves on load/error OR after a short per-image cap, so one stalled
// image just gets left out of the capture instead of freezing the whole export.
async function waitForImages(el, perImageMs = 8000) {
    const imgs = Array.from(el.querySelectorAll('img'));
    await Promise.all(imgs.map((img) => img.complete
        ? Promise.resolve()
        : new Promise((res) => {
            const done = () => { clearTimeout(t); res(); };
            const t = setTimeout(done, perImageMs);
            img.addEventListener('load', done, { once: true });
            img.addEventListener('error', done, { once: true });
        })));
}

// Capture a DOM element to a <canvas> with html2canvas. We use html2canvas (not the SVG
// <foreignObject> approach) because it renders reliably across our pages AND works in a
// background/hidden tab, where foreignObject-image capture silently fails with an error Event.
// data-noexport nodes (toolbars, ⚙ menus) are dropped so the export image never shows the
// export controls themselves.
async function captureCanvas(el, label) {
    const { default: html2canvas } = await import('html2canvas');
    return withTimeout(
        html2canvas(el, {
            scale: 2,
            backgroundColor: '#ffffff',
            useCORS: true,
            logging: false,
            ignoreElements: (node) => node?.dataset && 'noexport' in node.dataset,
        }),
        30000,
        label || 'ດຶງ ຮູບ',
    );
}

// Export a DOM element to a downloaded .JPG (dashboard, inspection sheet, borrow record…).
window.exportJpg = async (elementId, filename) => {
    const el = document.getElementById(elementId);
    if (!el) {
        return;
    }
    try {
        await withTimeout(waitForImages(el), 12000, 'ໂຫຼດ ຮູບ');
        const canvas = await captureCanvas(el, 'ດຶງ JPG');
        const dataUrl = canvas.toDataURL('image/jpeg', 0.95);
        const a = document.createElement('a');
        a.href = dataUrl;
        a.download = filename || 'export.jpg';
        a.click();
    } catch (e) {
        console.error('JPG export failed', e);
        alert('ດຶງ JPG ບໍ່ ສຳເລັດ — ' + errText(e));
    }
};

// Export a DOM element to an A4-portrait PDF. A tall capture (e.g. the dashboard) is sliced
// across multiple A4 pages so nothing gets squashed into one unreadable sliver; short content
// (a borrow record) stays a single page.
window.exportPdf = async (elementId, filename) => {
    const el = document.getElementById(elementId);
    if (!el) {
        return;
    }
    try {
        await withTimeout(waitForImages(el), 12000, 'ໂຫຼດ ຮູບ');
        const [canvas, { jsPDF }] = await Promise.all([captureCanvas(el, 'ດຶງ PDF'), import('jspdf')]);
        const pdf = new jsPDF({ orientation: 'portrait', unit: 'pt', format: 'a4' });
        const m = 24;                                       // page margin (pt)
        const cw = pdf.internal.pageSize.getWidth() - m * 2;  // content width on the page
        const pageContentH = pdf.internal.pageSize.getHeight() - m * 2;
        const sliceH = Math.max(1, Math.floor((pageContentH / cw) * canvas.width)); // source px per page
        const slice = document.createElement('canvas');
        slice.width = canvas.width;
        const ctx = slice.getContext('2d');
        let y = 0;
        let first = true;
        while (y < canvas.height) {
            const h = Math.min(sliceH, canvas.height - y);
            slice.height = h;
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, slice.width, h);
            ctx.drawImage(canvas, 0, y, canvas.width, h, 0, 0, canvas.width, h);
            if (!first) {
                pdf.addPage();
            }
            pdf.addImage(slice.toDataURL('image/jpeg', 0.95), 'JPEG', m, m, cw, (h * cw) / canvas.width);
            first = false;
            y += h;
        }
        pdf.save(filename || 'export.pdf');
    } catch (e) {
        console.error('PDF export failed', e);
        alert('ດຶງ PDF ບໍ່ ສຳເລັດ — ' + errText(e));
    }
};
