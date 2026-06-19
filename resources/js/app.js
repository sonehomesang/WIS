import './bootstrap';

import { toJpeg } from 'html-to-image';

// Export a DOM element to a downloaded .JPG (borrow record, etc.).
window.exportJpg = (elementId, filename) => {
    const el = document.getElementById(elementId);
    if (!el) {
        return;
    }
    toJpeg(el, { quality: 0.95, backgroundColor: '#ffffff', pixelRatio: 2 })
        .then((dataUrl) => {
            const a = document.createElement('a');
            a.href = dataUrl;
            a.download = filename || 'export.jpg';
            a.click();
        })
        .catch((e) => console.error('JPG export failed', e));
};
