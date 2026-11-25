/* Hero: air naik (2,8 dtk ke tinggi data-h, 3 lapis gelombang 18/11/7 dtk), port dari mockup. */
import { $$ } from './util';

export function mountHeroWater() {
    $$('[data-hero-water]').forEach((el) => {
        if (el._hw) return;
        el._hw = true;
        const mk = (q, fill) => `<svg viewBox="0 0 2880 100" preserveAspectRatio="none"><path d="M0 50 Q 360 ${q} 720 50 T 1440 50 T 2160 50 T 2880 50 V 100 H 0 Z" fill="${fill}"/></svg>`;
        el.classList.add('hw');
        el.innerHTML = `<div class="l l1">${mk(0, 'rgba(86,156,235,.14)')}</div><div class="l l2">${mk(10, 'rgba(86,156,235,.22)')}</div><div class="body"></div><div class="l l3">${mk(5, el.dataset.heroWater)}</div>`;
        setTimeout(() => (el.style.height = el.dataset.h || '190px'), 250);
    });
}
