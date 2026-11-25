/* Helper UI ALIR (diadaptasi dari mockup-alir) */
window.alirIcons = function () {
    try {
        if (window.lucide) {
            window.lucide.createIcons();
        }
    } catch (e) {
        // abaikan bila CDN lucide tidak tersedia (mis. saat offline test)
    }
};

window.alirMountHeroWater = function () {
    document.querySelectorAll('[data-hero-water]').forEach((el) => {
        if (el._hw) return;
        el._hw = true;

        const fillBottom = el.dataset.heroWater || '#F5F7FA';
        const mkWave = (q, fill) =>
            `<svg viewBox="0 0 2880 100" preserveAspectRatio="none"><path d="M0 50 Q 360 ${q} 720 50 T 1440 50 T 2160 50 T 2880 50 V 100 H 0 Z" fill="${fill}"/></svg>`;

        el.classList.add('hw');
        el.innerHTML =
            `<div class="l l1">${mkWave(0, 'rgba(86,156,235,.14)')}</div>` +
            `<div class="l l2">${mkWave(10, 'rgba(86,156,235,.22)')}</div>` +
            `<div class="body"></div>` +
            `<div class="l l3">${mkWave(5, fillBottom)}</div>`;

        setTimeout(() => {
            el.style.height = el.dataset.h || '190px';
        }, 250);
    });
};

document.addEventListener('DOMContentLoaded', () => {
    window.alirIcons();
    window.alirMountHeroWater();

    if (window.NProgress) {
        window.NProgress.configure({ showSpinner: false, minimum: 0.12, trickleSpeed: 130, speed: 420 });
        window.NProgress.done();
    }

    // Sidebar mobile drawer toggle
    const side = document.getElementById('side');
    const menuBtn = document.getElementById('menu');
    const backdrop = document.getElementById('side-backdrop');

    const toggleSide = (force) => {
        if (!side) return;
        const open = typeof force === 'boolean' ? force : !side.classList.contains('open');
        side.classList.toggle('open', open);
        if (backdrop) backdrop.classList.toggle('open', open);
    };

    if (menuBtn) {
        menuBtn.addEventListener('click', () => toggleSide());
    }
    if (backdrop) {
        backdrop.addEventListener('click', () => toggleSide(false));
    }

    // Accordion group di sidebar admin
    document.querySelectorAll('.ng-h').forEach((btn) => {
        btn.addEventListener('click', () => {
            const group = btn.closest('.ng');
            if (!group) return;
            const willOpen = !group.classList.contains('open');
            group.classList.toggle('open', willOpen);
            btn.setAttribute('aria-expanded', String(willOpen));
        });
    });

    // Toggle lihat/sembunyikan kata sandi
    document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-toggle-password');
            const input = document.getElementById(targetId);
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.innerHTML = `<i data-lucide="${show ? 'eye-off' : 'eye'}"></i>`;
            window.alirIcons();
        });
    });
});
