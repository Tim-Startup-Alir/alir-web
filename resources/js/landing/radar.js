/* Radar hero (port FX.radar mockup): 6 cincin tipis, 3 cincin koral mengembang, sapuan koral, ping acak.
   Peningkatan: berhenti menggambar saat hero tidak terlihat; statis bila prefers-reduced-motion. */
import { reduceMotion } from './util';

export function radar(c) {
    if (!c) return;
    const ctx = c.getContext('2d'), pings = [], t0 = performance.now(), still = reduceMotion();
    let visible = true;
    const size = () => {
        const r = c.getBoundingClientRect(), d = Math.min(devicePixelRatio || 1, 2);
        c.width = Math.max(1, r.width * d);
        c.height = Math.max(1, r.height * d);
    };
    size();
    window.addEventListener('resize', size);
    new IntersectionObserver((e) => (visible = e[0].isIntersecting)).observe(c);

    const draw = (now) => {
        const w = c.width, h = c.height, d = Math.min(devicePixelRatio || 1, 2), t = still ? 0 : Math.max(0, (now - t0) / 1000);
        ctx.clearRect(0, 0, w, h);
        const cx = w * (w > 900 * d ? 0.78 : 0.6), cy = h * 0.42, maxR = Math.max(w, h) * 0.55;
        ctx.lineWidth = d;
        for (let i = 1; i <= 6; i++) {
            ctx.strokeStyle = 'rgba(86,156,235,' + (0.1 - i * 0.012) + ')';
            ctx.beginPath();
            ctx.arc(cx, cy, (maxR * i) / 6, 0, Math.PI * 2);
            ctx.stroke();
        }
        for (let k = 0; k < 3; k++) {
            const p = (t * 0.35 + k / 3) % 1;
            ctx.strokeStyle = 'rgba(255,106,61,' + 0.35 * (1 - p) + ')';
            ctx.lineWidth = 2 * d;
            ctx.beginPath();
            ctx.arc(cx, cy, maxR * p, 0, Math.PI * 2);
            ctx.stroke();
        }
        if (ctx.createConicGradient) {
            const g = ctx.createConicGradient(t * 0.9, cx, cy);
            g.addColorStop(0, 'rgba(255,106,61,.22)');
            g.addColorStop(0.08, 'rgba(255,106,61,0)');
            g.addColorStop(1, 'rgba(255,106,61,0)');
            ctx.fillStyle = g;
            ctx.beginPath();
            ctx.arc(cx, cy, maxR, 0, Math.PI * 2);
            ctx.fill();
        }
        if (!still && Math.random() < 0.03) {
            const r = Math.random();
            pings.push({ x: cx + (Math.random() - 0.5) * maxR * 1.4, y: cy + (Math.random() - 0.5) * maxR * 1.1, b: t, c: r < 0.6 ? '229,72,77' : r < 0.8 ? '255,106,61' : '20,184,166' });
        }
        for (let i = pings.length - 1; i >= 0; i--) if (t - pings[i].b >= 3) pings.splice(i, 1);
        for (const p of pings) {
            const age = (t - p.b) / 3;
            ctx.fillStyle = 'rgba(' + p.c + ',' + (1 - age) + ')';
            ctx.beginPath();
            ctx.arc(p.x, p.y, 4 * d, 0, Math.PI * 2);
            ctx.fill();
            ctx.strokeStyle = 'rgba(' + p.c + ',' + 0.6 * (1 - age) + ')';
            ctx.lineWidth = 1.5 * d;
            ctx.beginPath();
            ctx.arc(p.x, p.y, (4 + age * 22) * d, 0, Math.PI * 2);
            ctx.stroke();
        }
        ctx.fillStyle = '#FF6A3D';
        ctx.beginPath();
        ctx.arc(cx, cy, 5 * d, 0, Math.PI * 2);
        ctx.fill();
    };

    if (still) {
        draw(t0);
        window.addEventListener('resize', () => draw(t0));
        return;
    }
    (function tick(now) {
        requestAnimationFrame(tick);
        if (visible && !document.hidden) draw(now);
    })(performance.now());
}
