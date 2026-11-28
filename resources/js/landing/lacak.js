/* Modal "Lacak Laporan": pelapor memasukkan kode ALR-XXXXXX, lalu diarahkan ke halaman progres /lacak/{kode}. */
import { $, esc, icons } from './util';
import { openModal, closeModal } from './modal';

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
const url = () => {
    try {
        return JSON.parse(document.getElementById('lapor-config')?.textContent || '{}').url?.lacak || '/lacak';
    } catch {
        return '/lacak';
    }
};

/** huruf besar, buang karakter asing, awalan ALR- otomatis: "alr7k3" -> "ALR-7K3" */
const format = (v) => {
    const s = String(v).toUpperCase().replace(/[^A-Z0-9]/g, '');
    if (s && 'ALR'.startsWith(s)) return s; // pengguna masih mengetik awalan "ALR"
    const body = s.startsWith('ALR') ? s.slice(3) : s;
    return body ? 'ALR-' + body.slice(0, 6) : '';
};

export function openLacak() {
    let busy = false;
    const ov = openModal(
        `<div class="lp-h"><span class="lp-hi"><i data-lucide="search"></i></span><div class="lp-ht"><h2>Lacak Laporan</h2><p>Cek progres laporan Anda</p></div><button type="button" class="lp-x" data-x aria-label="Tutup"><i data-lucide="x"></i></button></div>
        <form class="lp-body" data-form novalidate>
            <label class="lp-lbl" for="lk-kode-m">Kode laporan</label>
            <div class="lp-field" data-box><span><i data-lucide="ticket" class="text-coral-dark"></i></span><input id="lk-kode-m" name="kode" autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="10" placeholder="ALR-7K3M9P" class="font-mono tracking-wider" dusk="lacak-kode"></div>
            <div data-msg><p class="lp-hint">Kode ada di halaman "Laporan diterima" setelah Anda melapor.</p></div>
            <button type="submit" class="lp-next mt-5 w-full" dusk="lacak-submit" data-go>Cari laporan<i data-lucide="arrow-right"></i></button>
        </form>`,
        { cls: 'lapor lacak', label: 'Lacak Laporan' },
    );
    const m = $('.ld-modal', ov);
    const input = $('input', m), box = $('[data-box]', m), msg = $('[data-msg]', m), go = $('[data-go]', m);
    const show = (t) => {
        box.classList.toggle('bad', !!t);
        msg.innerHTML = t ? `<p class="lp-ferr" role="alert" dusk="lacak-error">${esc(t)}</p>` : '<p class="lp-hint">Kode ada di halaman "Laporan diterima" setelah Anda melapor.</p>';
    };

    icons();
    $('[data-x]', m).onclick = () => closeModal(ov);
    input.focus();
    input.addEventListener('input', () => {
        input.value = format(input.value);
        show('');
    });

    $('[data-form]', m).addEventListener('submit', async (e) => {
        e.preventDefault();
        if (busy) return;
        const kode = format(input.value);
        if (kode.length < 5) return show('Masukkan kode laporan Anda, contoh: ALR-7K3M9P.');
        busy = true;
        go.disabled = true;
        go.innerHTML = 'Mencari...<span class="lp-spin lp-spin-w"></span>';
        try {
            const res = await fetch(url(), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ kode }),
            });
            const body = await res.json().catch(() => ({}));
            if (res.ok && body.redirect) return window.location.assign(body.redirect);
            show(body.message || body.errors?.kode?.[0] || (res.status === 419 ? 'Sesi halaman kedaluwarsa. Muat ulang halaman lalu coba lagi.' : 'Pencarian gagal. Coba lagi.'));
        } catch {
            show('Tidak dapat terhubung. Periksa koneksi internet Anda lalu coba lagi.');
        }
        busy = false;
        go.disabled = false;
        go.innerHTML = 'Cari laporan<i data-lucide="arrow-right"></i>';
        icons();
    });
}
