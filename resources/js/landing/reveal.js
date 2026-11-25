/* Reveal saat scroll (28px, .9 dtk, threshold .08), counter 1,7 dtk easeOutCubic, bar tumbuh 1,4 dtk. */
import { $$, reduceMotion } from './util';

export function countUp(el) {
    if (el._done) return;
    el._done = true;
    const to = parseFloat(el.dataset.count), pre = el.dataset.pre || '', suf = el.dataset.suf || '';
    const fmt = (v) => pre + Math.round(v).toLocaleString('id-ID') + suf;
    if (reduceMotion()) {
        el.textContent = fmt(to);
        return;
    }
    const t0 = performance.now(), dur = 1700;
    const f = (t) => {
        const k = Math.min(1, (t - t0) / dur), e = 1 - Math.pow(1 - k, 3);
        el.textContent = fmt(to * e);
        if (k < 1) requestAnimationFrame(f);
    };
    requestAnimationFrame(f);
}

export function growBar(i) {
    if (i._g) return;
    i._g = true;
    requestAnimationFrame(() => setTimeout(() => (i.style.width = i.dataset.bar + '%'), 80));
}

let io;
export function mountReveal(root = document) {
    io ??= new IntersectionObserver(
        (entries) =>
            entries.forEach((e) => {
                if (!e.isIntersecting) return;
                const el = e.target;
                el.classList.add('in');
                io.unobserve(el);
                if (el.dataset.count) countUp(el);
                if (el.dataset.bar) growBar(el);
            }),
        { threshold: 0.08 },
    );
    $$('[data-reveal],[data-count],[data-bar]', root).forEach((el) => {
        if (el._o) return;
        el._o = true;
        io.observe(el);
    });
}
