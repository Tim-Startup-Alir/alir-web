/* Galeri aksi relawan: lightbox sederhana (foto dan video) memakai elemen <dialog>. */
const dlg = document.getElementById('gl-dialog');

if (dlg) {
    const stage = dlg.querySelector('.gl-stage');
    const judul = dlg.querySelector('[data-gl-judul]');
    const meta = dlg.querySelector('[data-gl-meta]');
    const catatan = dlg.querySelector('[data-gl-catatan]');

    const tutup = () => {
        stage.querySelector('video')?.pause();
        stage.replaceChildren();
        if (dlg.open) dlg.close();
    };

    document.querySelectorAll('[data-gl-open]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const el = document.createElement(btn.dataset.jenis === 'video' ? 'video' : 'img');
            el.src = btn.dataset.src;
            if (el.tagName === 'VIDEO') {
                el.controls = true;
                el.autoplay = true;
                el.playsInline = true;
            } else {
                el.alt = btn.dataset.alt || '';
            }
            stage.replaceChildren(el);
            judul.textContent = btn.dataset.judul || '';
            meta.textContent = btn.dataset.meta || '';
            catatan.textContent = btn.dataset.catatan || '';
            catatan.hidden = !btn.dataset.catatan;
            dlg.showModal();
        });
    });

    dlg.querySelector('[data-gl-close]')?.addEventListener('click', tutup);
    dlg.addEventListener('close', () => stage.replaceChildren());
    // klik di luar kotak menutup dialog
    dlg.addEventListener('click', (e) => { if (e.target === dlg) tutup(); });
}

/* Thumbnail video: tampilkan cuplikan frame sebelum diputar. */
document.querySelectorAll('video[data-poster-frame]').forEach((v) => {
    v.preload = 'auto';
    v.addEventListener('loadedmetadata', () => { v.currentTime = Math.max(1, (v.duration || 4) * 0.25); }, { once: true });
});
