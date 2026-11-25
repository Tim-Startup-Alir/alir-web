/* NProgress ala YouTube: bar di atas layar saat halaman dimuat, saat klik tautan ke halaman lain,
   dan saat aksi di dalam halaman (pindah tab, langkah form). Tema bar ada di resources/css/base.css. */
import NProgress from 'nprogress';

NProgress.configure({ showSpinner: false, minimum: 0.12, trickleSpeed: 130, speed: 420 });
window.NProgress = NProgress;

/** jalankan sejak modul dimuat sampai event load (gambar, tile peta, font) selesai */
export function startPageLoad() {
    if (document.readyState === 'complete') return;
    NProgress.start();
    window.addEventListener('load', () => setTimeout(() => NProgress.done(), 120), { once: true });
}

/** jalankan fn di antara start dan done (pindah tab, langkah form, dsb.) */
export function withProgress(fn, ms = 300) {
    NProgress.start();
    requestAnimationFrame(() => {
        try {
            fn?.();
        } finally {
            setTimeout(() => NProgress.done(), ms);
        }
    });
}

/** bar singkat tanpa aksi (mis. anchor scroll) */
export const blip = (ms = 520) => withProgress(null, ms);

export function bindLinkProgress() {
    // halaman dipulihkan dari bfcache (tombol Back): pastikan bar tidak tertinggal
    window.addEventListener('pageshow', (e) => {
        if (e.persisted) NProgress.done(true);
    });

    // tautan antarhalaman: bar langsung jalan, navigasi tidak ditunda
    document.addEventListener('click', (e) => {
        const a = e.target.closest?.('a[href]');
        if (!a || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;
        if (a.target === '_blank' || a.hasAttribute('download')) return;
        const href = a.getAttribute('href') || '';
        if (!href || /^(tel:|mailto:|javascript:)/i.test(href)) return;
        let u;
        try {
            u = new URL(a.href, location.href);
        } catch {
            return;
        }
        if (u.origin !== location.origin) return;
        if (u.pathname === location.pathname && u.search === location.search) {
            if (u.hash) blip(); // anchor di halaman yang sama: bar pendek sambil scroll halus
            return;
        }
        NProgress.start();
    });
}

export default NProgress;
