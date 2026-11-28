/* Utilitas kecil bersama untuk halaman publik (port dari mockup ui.js). */
export const $ = (s, r = document) => r.querySelector(s);
export const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

export const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

/** Mockup mengabaikan prefers-reduced-motion, jadi animasi (hujan, radar, reveal, dst.) selalu jalan. */
export const reduceMotion = () => false;

export const icons = () => {
    try {
        window.lucide?.createIcons();
    } catch {
        /* CDN lucide belum termuat / offline */
    }
};

export const haversine = (a, b, c, d) => {
    const R = 6371000, t = (x) => (x * Math.PI) / 180, dl = t(c - a), dg = t(d - b);
    const h = Math.sin(dl / 2) ** 2 + Math.cos(t(a)) * Math.cos(t(c)) * Math.sin(dg / 2) ** 2;
    return 2 * R * Math.asin(Math.sqrt(h));
};
export const fmtDist = (m) => (m < 1000 ? Math.round(m) + ' m' : (m / 1000).toFixed(1).replace('.', ',') + ' km');

/** salin teks (kode laporan) ke clipboard */
export async function copyText(text) {
    try {
        await navigator.clipboard.writeText(text);
        return true;
    } catch {
        return false;
    }
}
