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
    window.Alpine.data('photoSlot', (i, slot) => ({
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
                const prop = (kind === 'cam' ? 'camUpload' : 'galUpload') + '.' + i + '.' + slot;
                await new Promise((resolve) => {
                    this.$wire.uploadMultiple(prop, out, resolve, resolve);
                });
            } finally {
                this.busy = false;
                e.target.value = ''; // allow re-picking the same file
            }
        },
    }));
});

// Skip nodes flagged data-noexport (toolbars, ⚙ menus) when capturing.
const exportFilter = (node) => !(node?.dataset && 'noexport' in node.dataset);

// Wait for every <img> inside the node to finish loading before capture — html-to-image
// rejects the whole render if an image is still pending, which makes the button "do nothing".
async function waitForImages(el) {
    const imgs = Array.from(el.querySelectorAll('img'));
    await Promise.all(imgs.map((img) => (img.complete && img.naturalWidth)
        ? Promise.resolve()
        : new Promise((res) => { img.onload = img.onerror = res; })));
}

// Export a DOM element to a downloaded .JPG (inspection sheet, borrow record…).
window.exportJpg = async (elementId, filename) => {
    const el = document.getElementById(elementId);
    if (!el) {
        return;
    }
    try {
        await waitForImages(el);
        const { toJpeg } = await import('html-to-image');
        // skipFonts: ບໍ່ ຝັງ ຟອນ ພາຍນອກ (fonts.bunny.net) — ຫຼີກ SecurityError ຕອນ ອ່ານ CSS cross-origin.
        const dataUrl = await toJpeg(el, { quality: 0.95, backgroundColor: '#ffffff', pixelRatio: 2, skipFonts: true, filter: exportFilter });
        const a = document.createElement('a');
        a.href = dataUrl;
        a.download = filename || 'export.jpg';
        a.click();
    } catch (e) {
        console.error('JPG export failed', e);
        alert('ດຶງ JPG ບໍ່ ສຳເລັດ — ' + (e && e.message ? e.message : e));
    }
};

// Export a DOM element to a single-page PDF (image fitted to A4 portrait).
window.exportPdf = async (elementId, filename) => {
    const el = document.getElementById(elementId);
    if (!el) {
        return;
    }
    try {
        await waitForImages(el);
        const [{ toPng }, { jsPDF }] = await Promise.all([import('html-to-image'), import('jspdf')]);
        const dataUrl = await toPng(el, { backgroundColor: '#ffffff', pixelRatio: 2, skipFonts: true, filter: exportFilter });
        const img = new Image();
        img.onload = () => {
            const pdf = new jsPDF({ orientation: 'portrait', unit: 'pt', format: 'a4' });
            const pw = pdf.internal.pageSize.getWidth();
            const ph = pdf.internal.pageSize.getHeight();
            const m = 24;
            const scale = Math.min((pw - m * 2) / img.width, (ph - m * 2) / img.height);
            const w = img.width * scale;
            const h = img.height * scale;
            pdf.addImage(dataUrl, 'PNG', (pw - w) / 2, m, w, h);
            pdf.save(filename || 'export.pdf');
        };
        img.src = dataUrl;
    } catch (e) {
        console.error('PDF export failed', e);
        alert('ດຶງ PDF ບໍ່ ສຳເລັດ — ' + (e && e.message ? e.message : e));
    }
};
