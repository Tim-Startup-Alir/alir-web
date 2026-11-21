/* Satu sistem overlay untuk semua popup: kunci scroll, tutup dengan Esc / klik luar, fokus dikembalikan. */
import { esc, icons } from './util';

const stack = [];

export function openModal(html, { cls = '', label = 'Dialog', onClose } = {}) {
    const ov = document.createElement('div');
    ov.className = 'ld-ov';
    ov.innerHTML = `<div class="ld-modal ${cls}" role="dialog" aria-modal="true" aria-label="${esc(label)}" tabindex="-1">${html}</div>`;
    ov._onClose = onClose;
    ov._prevFocus = document.activeElement;
    ov.addEventListener('mousedown', (e) => {
        if (e.target === ov) closeModal(ov);
    });
    document.body.appendChild(ov);
    stack.push(ov);
    document.body.classList.add('modal-open');
    icons();
    ov.querySelector('.ld-modal').focus({ preventScroll: true });
    return ov;
}

export function closeModal(ov) {
    if (!ov || !ov.isConnected || ov._closing) return;
    ov._closing = true;
    const fn = ov._onClose;
    ov._onClose = null;
    try {
        fn?.();
    } catch {
        /* abaikan */
    }
    const i = stack.indexOf(ov);
    if (i > -1) stack.splice(i, 1);
    if (!stack.length) document.body.classList.remove('modal-open');
    ov.classList.add('is-closing');
    setTimeout(() => ov.remove(), 180);
    ov._prevFocus?.focus?.({ preventScroll: true });
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && stack.length) closeModal(stack[stack.length - 1]);
});

let toastT;
export function toast(msg, type = 'ok') {
    document.querySelectorAll('.ld-toast').forEach((t) => t.remove());
    clearTimeout(toastT);
    const col = type === 'err' ? '#E5484D' : type === 'warn' ? '#F5A524' : '#14B8A6';
    const t = document.createElement('div');
    t.className = 'ld-toast';
    t.setAttribute('role', 'status');
    t.innerHTML = `<span class="ti" style="background:${col}"><i data-lucide="${type === 'err' ? 'circle-alert' : 'circle-check'}"></i></span>${esc(msg)}`;
    document.body.appendChild(t);
    icons();
    toastT = setTimeout(() => t.remove(), 3400);
}
