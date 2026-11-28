/* Efek bersama halaman Cuaca/Donasi/Edukasi (port dari mockup fx.js + ui.js). */
import { reduceMotion } from '../landing/util';

/** hujan di canvas: tetes ternormalisasi (port rain() mockup); statis bila prefers-reduced-motion */
export function rain(c, { n = 160, color = '226,232,240' } = {}) {
    if (!c) return;
    const ctx = c.getContext('2d'), drops = [], still = reduceMotion();
    let dpr = Math.min(devicePixelRatio || 1, 2), vis = true;
    const size = () => {
        const r = c.getBoundingClientRect();
        dpr = Math.min(devicePixelRatio || 1, 2);
        c.width = Math.max(1, r.width * dpr);
        c.height = Math.max(1, r.height * dpr);
    };
    size();
    addEventListener('resize', size);
    for (let i = 0; i < n; i++) drops.push({ x: Math.random(), y: Math.random(), l: 0.6 + Math.random() * 0.9, v: 0.6 + Math.random() * 0.8, a: 0.12 + Math.random() * 0.32 });
    new IntersectionObserver((e) => (vis = e[0].isIntersecting)).observe(c);
    const draw = () => {
        const w = c.width, h = c.height;
        ctx.clearRect(0, 0, w, h);
        ctx.lineCap = 'round';
        for (const p of drops) {
            if (!still) {
                p.y += 0.009 * p.v;
                p.x -= 0.0012 * p.v;
                if (p.y > 1.05) { p.y = -0.05; p.x = Math.random() * 1.1; }
            }
            const x = p.x * w, y = p.y * h, len = 18 * p.l * dpr;
            ctx.strokeStyle = `rgba(${color},${p.a})`;
            ctx.lineWidth = 1.2 * dpr;
            ctx.beginPath();
            ctx.moveTo(x, y);
            ctx.lineTo(x - len * 0.18, y + len);
            ctx.stroke();
        }
    };
    if (still) return draw();
    (function tick() {
        requestAnimationFrame(tick);
        if (vis && c.isConnected && !document.hidden) draw();
    })();
}

/** konfeti kecil di sekitar elemen (tas siaga lengkap) */
export function burstEl(el, n = 28) {
    if (!el || reduceMotion()) return;
    const r = el.getBoundingClientRect(), x = r.left + r.width / 2, y = r.top + r.height / 2;
    const colors = ['#FF6A3D', '#FFB27A', '#14B8A6', '#569CEB', '#F5A524', '#F5F7FA'];
    for (let i = 0; i < n; i++) {
        const p = document.createElement('i'), a = Math.random() * Math.PI * 2, d = 70 + Math.random() * 150;
        p.className = 'pt' + (i % 3 === 0 ? ' dot' : '');
        p.style.cssText = `left:${x}px;top:${y}px;background:${colors[i % colors.length]};--dx:${Math.cos(a) * d}px;--dy:${Math.sin(a) * d - 40}px;--du:${1 + Math.random() * 0.6}s`;
        document.body.appendChild(p);
        setTimeout(() => p.remove(), 1900);
    }
}

/** elemen [data-depth] bergeser mengikuti mouse (hero Edukasi) */
export function mouseParallax(box) {
    if (!box || matchMedia('(pointer: coarse)').matches || reduceMotion()) return;
    const kids = [...box.querySelectorAll('[data-depth]')];
    box.addEventListener('mousemove', (e) => {
        const r = box.getBoundingClientRect(), nx = (e.clientX - r.left) / r.width - 0.5, ny = (e.clientY - r.top) / r.height - 0.5;
        kids.forEach((k) => { const d = +k.dataset.depth; k.style.transform = `translate(${(nx * d * 46).toFixed(1)}px,${(ny * d * 46).toFixed(1)}px)`; });
    });
}

export const rp = (n) => 'Rp' + Math.round(n).toLocaleString('id-ID');
