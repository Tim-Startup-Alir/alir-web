/* Entry halaman publik ALIR: NProgress, animasi mockup, dan modal Lapor Banjir (F-02). */
import { startPageLoad, bindLinkProgress } from './nprogress';
import { copyText, icons } from './util';
import { mountReveal } from './reveal';
import { radar } from './radar';
import { mountHeroWater } from './hero';
import { openLapor } from './lapor';
import { openLacak } from './lacak';
import { toast } from './modal';

startPageLoad();
bindLinkProgress();

const safe = (fn) => {
    try {
        fn();
    } catch (e) {
        console.error('[ALIR publik]', e);
    }
};

const init = () => {
    icons();
    safe(() => radar(document.getElementById('radar')));
    safe(mountHeroWater);
    safe(mountReveal);

    // navbar memadat saat halaman di-scroll (mockup)
    const nav = document.getElementById('nav');
    const onScroll = () => nav?.classList.toggle('is-scrolled', window.scrollY > 24);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    // delegasi: ikut bekerja di konten yang dirender belakangan
    document.addEventListener('click', async (e) => {
        if (e.target.closest('[data-open-lapor]')) return openLapor();
        if (e.target.closest('[data-open-lacak]')) {
            e.preventDefault(); // tautan tetap berfungsi tanpa JS (halaman /lacak)
            return openLacak();
        }
        const cp = e.target.closest('[data-copy]');
        if (cp) {
            const ok = await copyText(cp.dataset.copy);
            toast(ok ? 'Kode laporan disalin.' : 'Salin manual: ' + cp.dataset.copy, ok ? 'ok' : 'warn');
        }
    });

    // ?lapor=1 membuka form langsung (mis. dari tombol "Lapor lagi")
    if (new URLSearchParams(location.search).has('lapor')) safe(openLapor);
};

document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', init) : init();
