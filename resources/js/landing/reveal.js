/* Reveal saat scroll (28px, .9 dtk, threshold .08), counter 1,7 dtk easeOutCubic, bar tumbuh 1,4 dtk. */
import { $$, reduceMotion } from './util';

export function countUp(el) {
    if (el._done) return;
    el._done = true;
    const to = parseFloat(el.dataset.count), pre = el.dataset.pre || '', suf = el.dataset.suf || '', dec = +(el.dataset.dec || 0);
    const fmt = (v) => pre + v.toLocaleString('id-ID', { minimumFractionDigits: dec, maximumFractionDigits: dec }) + suf;
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

/** bar progres (.bar>i) tumbuh dari 0 selama 1,4 dtk (port growBars mockup) */
export function growBars(root = document) {
    $$('.bar>i', root).forEach((i) => {
        if (i._g) return;
        i._g = true;
        const w = i.style.width;
        if (!w) return;
        i.style.width = '0%';
        i.style.transition = 'width 1.4s cubic-bezier(.2,.8,.2,1)';
        void i.offsetWidth;
        requestAnimationFrame(() => setTimeout(() => (i.style.width = w), 80));
    });
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
    growBars(root);
}
