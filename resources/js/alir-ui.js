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

document.addEventListener('DOMContentLoaded', () => {
    window.alirIcons();

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
            const body = group.querySelector('.ng-b');
            if (body) {
                if (willOpen) {
                    body.removeAttribute('inert');
                } else {
                    body.setAttribute('inert', '');
                }
            }
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
