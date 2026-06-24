import './bootstrap';

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

// Skip nodes flagged data-noexport (toolbars, ⚙ menus) when capturing.
const exportFilter = (node) => !(node?.dataset && 'noexport' in node.dataset);

// Export a DOM element to a downloaded .JPG (borrow record, dashboard…).
window.exportJpg = async (elementId, filename) => {
    const el = document.getElementById(elementId);
    if (!el) {
        return;
    }
    try {
        const { toJpeg } = await import('html-to-image');
        const dataUrl = await toJpeg(el, { quality: 0.95, backgroundColor: '#ffffff', pixelRatio: 2, filter: exportFilter });
        const a = document.createElement('a');
        a.href = dataUrl;
        a.download = filename || 'export.jpg';
        a.click();
    } catch (e) {
        console.error('JPG export failed', e);
    }
};

// Export a DOM element to a single-page PDF (image fitted to A4 landscape).
window.exportPdf = async (elementId, filename) => {
    const el = document.getElementById(elementId);
    if (!el) {
        return;
    }
    try {
        const [{ toPng }, { jsPDF }] = await Promise.all([import('html-to-image'), import('jspdf')]);
        const dataUrl = await toPng(el, { backgroundColor: '#ffffff', pixelRatio: 2, filter: exportFilter });
        const img = new Image();
        img.onload = () => {
            const pdf = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });
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
    }
};
