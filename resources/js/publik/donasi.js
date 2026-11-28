/* Halaman Donasi (port mockup-alir/assets/js/donasi.js): filter katalog + modal "Donasi barang".
   FE saja. TODO (PBI donasi): tombol "Lanjut bayar" (#fgo) memanggil payment gateway
   (Midtrans Snap) dan memperbarui katalog/donatur dari respons server. */
import { $, $$, esc, icons } from '../landing/util';
import { openModal, closeModal, toast } from '../landing/modal';
import { mountReveal } from '../landing/reveal';
import { rp } from './fx';

const cfg = JSON.parse(document.getElementById('donasi-config')?.textContent || '{}');
const WL = cfg.wishlist || [];
const st = { cat: 'Semua', urg: 'all', item: null, mode: 'qty', qty: 1, nom: 100000, anon: false, name: '' };
const URG = { Kritis: 'bg-red', Mendesak: 'bg-amber', Normal: 'bg-teal' };

/* ---------- langkah di header (garis terisi dengan transisi) ---------- */
function flow(n) {
    $$('#flow .fs').forEach((el, i) => {
        el.classList.toggle('on', i + 1 <= n);
        el.style.opacity = i + 1 <= n ? 1 : 0.55;
    });
}
flow(0);
setTimeout(() => flow(1), 350);

/* ---------- filter katalog ---------- */
function drawCat() {
    let n = 0;
    $$('#cat .dc').forEach((c) => {
        const show = (st.cat === 'Semua' || c.dataset.kategori === st.cat) && (st.urg === 'all' || c.dataset.urgensi === st.urg);
        c.classList.toggle('hide', !show);
        if (show) n++;
    });
    $('#cat-kosong').classList.toggle('hide', n > 0);
}
$$('#cats .chip').forEach((b) => (b.onclick = () => {
    st.cat = b.dataset.c;
    $$('#cats .chip').forEach((x) => x.classList.toggle('on', x === b));
    drawCat();
}));
$$('#urg button').forEach((b) => (b.onclick = () => {
    st.urg = b.dataset.u;
    $$('#urg button').forEach((x) => x.classList.toggle('on', x === b));
    drawCat();
}));
$$('[data-pick]').forEach((b) => (b.onclick = () => openForm(b.dataset.pick)));

/* ---------- modal Donasi barang ---------- */
const sisa = (w) => Math.max(1, w.target - w.terkumpul);
const total = () => (st.mode === 'qty' ? st.qty * st.item.harga : st.nom);
const eqUnits = () => (st.mode === 'qty' ? st.qty : Math.max(0, Math.floor(st.nom / st.item.harga)));
const wave = (fill, q) => `<svg viewBox="0 0 2880 100" preserveAspectRatio="none"><path d="M0 50 Q 360 ${q} 720 50 T 1440 50 T 2160 50 T 2880 50 V 100 H 0 Z" fill="${fill}"/></svg>`;

function openForm(id) {
    st.item = WL.find((w) => w.id === id);
    if (!st.item) return;
    Object.assign(st, { qty: 1, mode: 'qty', nom: Math.max(100000, st.item.harga), anon: false, name: '' });
    flow(2);
    const w = st.item, persen = Math.round((w.terkumpul / w.target) * 100);
    const b = openModal(`
    <div class="dm-ban">
      <img src="${esc(w.foto_url)}" alt="">
      <div class="dm-shade"></div>
      <button type="button" class="dm-x" data-x aria-label="Tutup"><i data-lucide="x"></i></button>
      <div class="dm-cap">
        <span class="dm-urg ${URG[w.urgensi] || ''}">${esc(w.urgensi)}</span>
        <h2>${esc(w.nama)}</h2>
        <p>${rp(w.harga)} / ${esc(w.satuan)} · ${esc(w.oleh)}</p>
      </div>
      <div class="dm-waves" aria-hidden="true"><div class="a">${wave('rgba(255,255,255,.35)', 10)}</div><div class="b">${wave('#fff', 0)}</div></div>
    </div>

    <div class="dm-b">
      <div class="dm-total">
        <small>Total donasi</small>
        <b id="ft">Rp0</b>
        <span id="feq"></span>
        <div class="dm-bar" title="${persen}% kebutuhan terpenuhi"><i style="width:${Math.min(100, persen)}%"></i></div>
        <em>${persen}% kebutuhan terpenuhi</em>
      </div>

      <div class="dm-tabs" id="fm"><button type="button" class="on" data-m="qty">Per barang</button><button type="button" data-m="nom">Nominal bebas</button></div>

      <div id="fq">
        <div class="dm-qty"><button type="button" data-d="-1" aria-label="Kurangi"><i data-lucide="minus"></i></button><output id="fqn">1</output><button type="button" data-d="1" aria-label="Tambah"><i data-lucide="plus"></i></button></div>
        <div class="dm-unit">${esc(w.satuan)}</div>
        <div class="dm-quick">${[5, 10, 25].map((q) => `<button type="button" data-q="${q}">+${q}</button>`).join('')}</div>
      </div>
      <div id="fn" class="hide">
        <div class="dm-nom"><span>Rp</span><input id="fni" inputmode="numeric" autocomplete="off" aria-label="Nominal donasi" value="${st.nom.toLocaleString('id-ID')}"></div>
        <div class="dm-quick">${[50000, 100000, 250000, 500000, 1000000].map((n) => `<button type="button" data-n="${n}">${n >= 1e6 ? '1 jt' : n / 1000 + ' rb'}</button>`).join('')}</div>
      </div>

      <div class="dm-who">
        <div class="dm-fl"><input class="dm-name" id="fnm" placeholder=" " autocomplete="name"><label for="fnm">Nama Anda</label></div>
        <div class="dm-err hide" id="fer">Isi nama Anda atau pilih anonim.</div>
        <label class="dm-anon"><input type="checkbox" id="fan"><span class="dm-sw"></span>Tampilkan sebagai Hamba Allah (anonim)</label>
      </div>
    </div>

    <div class="dm-f">
      <button type="button" class="dm-go" id="fgo">Lanjut bayar<i data-lucide="arrow-right"></i></button>
      <p><i data-lucide="shield-check"></i>Pembayaran aman · minimal Rp10.000</p>
    </div>`,
    { cls: 'dm', label: 'Donasi barang', onClose: () => flow(1) });

    // angka total berubah halus (tween ringan)
    let shown = 0, raf = 0;
    const setTotal = (to) => {
        cancelAnimationFrame(raf);
        const from = shown, t0 = performance.now(), el = $('#ft', b);
        const f = (t) => {
            const k = Math.min(1, (t - t0) / 280), e = 1 - Math.pow(1 - k, 3);
            shown = from + (to - from) * e;
            el.textContent = rp(shown);
            if (k < 1) raf = requestAnimationFrame(f);
        };
        raf = requestAnimationFrame(f);
    };
    const sync = () => {
        const t = total();
        setTotal(t);
        $('#fqn', b).textContent = st.qty;
        $('#feq', b).textContent = st.mode === 'qty'
            ? `${st.qty} ${w.satuan} × ${rp(w.harga)} · sisa ${w.target - w.terkumpul} ${w.satuan}`
            : `Setara ± ${eqUnits()} ${w.satuan} ${w.nama}`;
        $('#fgo', b).disabled = t < 10000;
        $$('#fn [data-n]', b).forEach((c) => c.classList.toggle('on', +c.dataset.n === st.nom));
    };
    $('[data-x]', b).onclick = () => closeModal(b);
    $$('#fm button', b).forEach((x) => (x.onclick = () => {
        st.mode = x.dataset.m;
        $$('#fm button', b).forEach((y) => y.classList.toggle('on', y === x));
        $('#fq', b).classList.toggle('hide', st.mode !== 'qty');
        $('#fn', b).classList.toggle('hide', st.mode !== 'nom');
        sync();
    }));
    $$('.dm-qty button', b).forEach((x) => (x.onclick = () => { st.qty = Math.max(1, Math.min(sisa(w), st.qty + +x.dataset.d)); sync(); }));
    $$('#fq [data-q]', b).forEach((x) => (x.onclick = () => { st.qty = Math.min(sisa(w), st.qty + +x.dataset.q); sync(); }));
    $('#fni', b).oninput = (e) => {
        st.nom = +e.target.value.replace(/\D/g, '') || 0;
        e.target.value = st.nom ? st.nom.toLocaleString('id-ID') : '';
        sync();
    };
    $$('#fn [data-n]', b).forEach((x) => (x.onclick = () => { st.nom = +x.dataset.n; $('#fni', b).value = st.nom.toLocaleString('id-ID'); sync(); }));
    $('#fan', b).onchange = (e) => {
        st.anon = e.target.checked;
        $('#fnm', b).disabled = st.anon;
        $('#fnm', b).classList.remove('err');
        $('#fer', b).classList.add('hide');
    };
    $('#fnm', b).oninput = (e) => { st.name = e.target.value; e.target.classList.remove('err'); $('#fer', b).classList.add('hide'); };
    $('#fgo', b).onclick = () => {
        if (!st.anon && !st.name.trim()) {
            const n = $('#fnm', b);
            n.classList.remove('err');
            void n.offsetWidth;
            n.classList.add('err');
            n.focus();
            $('#fer', b).classList.remove('hide');
            return;
        }
        // TODO (PBI donasi): kirim { item, jumlah/nominal, nama/anonim } ke backend lalu buka payment gateway.
        closeModal(b);
        toast('Pembayaran belum tersambung: bagian ini diisi pada PBI Donasi.', 'warn');
    };
    sync();
    icons();
}

// ?item=W-01 membuka form langsung (mis. dari tautan di landing)
const pre = new URLSearchParams(location.search).get('item');
if (pre && WL.some((w) => w.id === pre && w.terkumpul < w.target)) setTimeout(() => openForm(pre), 500);

icons();
mountReveal();
