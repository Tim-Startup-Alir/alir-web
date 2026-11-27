/* Modal Lapor Banjir, 3 langkah (NFR-07):
   1 Lokasi  : pin peta / GPS + alamat singkat, cek wilayah cakupan ke server (PBI-005)
   2 Kondisi : keparahan, perkiraan warga terdampak, foto (PBI-004)
   3 Kontak  : WhatsApp + persetujuan, CAPTCHA Cloudflare Turnstile, kirim (PBI-004, NFR-02)
   Sukses -> halaman "Laporan diterima" berisi kode ALR-XXXXXX (PBI-006).
   Isian disimpan di tingkat modul: menutup modal atau gagal validasi tidak menghapus isian. */
import L from 'leaflet';
import { $, $$, esc, fmtDist, haversine, icons } from './util';
import { withProgress } from './nprogress';
import { openModal, closeModal, toast } from './modal';
import { baseMap, drawCoverage, pinIcon } from './map';

const SEV = {
    ringan: ['Ringan', '#14B8A6', 'Genangan di jalan atau halaman, aktivitas masih bisa berjalan.'],
    sedang: ['Sedang', '#F5A524', 'Air masuk pekarangan atau rumah, butuh bantuan mengamankan barang.'],
    berat: ['Berat', '#E5484D', 'Air tinggi, warga terjebak atau rentan dan butuh evakuasi.'],
};
const LABELS = ['Lokasi', 'Kondisi', 'Kontak & kirim'];
const FIELD_STEP = { latitude: 1, longitude: 1, sumber_lokasi: 1, alamat_singkat: 1, severity: 2, jumlah_warga: 2, foto: 2 };
const TYPES = /^image\/(jpeg|png|webp)$/;
const WA_HINT = 'Format 08xx atau +62 8xx. Status laporan dikabarkan lewat WhatsApp.';

let cfg;
const config = () => {
    if (!cfg) {
        try {
            cfg = JSON.parse(document.getElementById('lapor-config')?.textContent || '{}');
        } catch {
            cfg = {};
        }
    }
    return cfg;
};

/** sama dengan StoreLaporanBanjirRequest::normalisasiWa + POLA_WA */
export const normalizeWa = (v) => String(v || '').replace(/[\s\-.()]/g, '').replace(/^\+?62/, '0');
export const validWa = (v) => /^08[1-9]\d{6,11}$/.test(normalizeWa(v));

const fresh = () => ({
    step: 1,
    lat: null, lng: null, sumber: 'pin', cov: null, alamat: '',
    sev: '', people: 1, file: null, photoUrl: '',
    wa: '', nama: '', catatan: '', consent: false, token: '',
    err: '', errors: {}, sending: false,
});
let st = fresh();

/** cadangan bila server tidak dapat dihubungi: radius dari pusat kelurahan (server tetap memvalidasi saat kirim) */
function localCoverage(lat, lng) {
    const near = (config().wilayah || []).map((w) => ({ w, d: haversine(lat, lng, w.lat, w.lng) })).sort((a, b) => a.d - b.d)[0];
    if (!near) return { ok: false, pesan: 'Wilayah cakupan belum tersedia.' };
    return near.d <= near.w.radius
        ? { ok: true, kel: near.w.nama, pesan: `Lokasi berada di Kelurahan ${near.w.nama}.` }
        : { ok: false, pesan: `Titik berada di luar wilayah cakupan ALIR (${fmtDist(near.d)} dari Kel. ${near.w.nama}). Geser pin ke lokasi banjir yang benar.` };
}

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

export function openLapor() {
    let pmap = null, pin = null, widget = null, ov, m, checkSeq = 0;

    const teardown = () => {
        pmap?.remove();
        pmap = pin = null;
        if (widget != null) {
            try {
                window.turnstile?.remove(widget);
            } catch {
                /* widget sudah hilang */
            }
            widget = null;
        }
    };

    const ferr = (k) => (st.errors[k] ? `<p class="lp-ferr" data-err-for="${k}" role="alert">${esc(st.errors[k])}</p>` : '');
    const req = '<i>*</i>';

    const dropBody = () =>
        st.file
            ? `<img src="${st.photoUrl}" alt="Pratinjau foto"><span class="lp-drop-ok"><i data-lucide="circle-check"></i><span>${esc(st.file.name)}</span><u>Ganti foto</u></span>`
            : `<span class="lp-drop-ic"><i data-lucide="camera"></i></span><span><b>Unggah foto kondisi banjir ${req}</b><small>JPG, PNG, atau WEBP maks. ${Math.round((config().fotoMaksKb || 5120) / 1024)} MB &middot; ketuk atau seret ke sini</small></span>`;

    const stepBody = () => {
        if (st.step === 1)
            return `<div class="lp-in">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-3"><div><h3 class="lp-q" tabindex="-1" data-focus>Tandai lokasi banjir ${req}</h3><p class="lp-sub">Ketuk peta untuk memasang pin (bisa digeser), atau pakai GPS.</p></div>
                <button type="button" class="lp-gps" data-gps dusk="lapor-gps"><i data-lucide="locate-fixed"></i>Gunakan lokasi saya</button></div>
                <div id="pmap" class="lp-map" dusk="lapor-map"><div class="map-fallback"></div></div>
                <div data-field="latitude"><div class="lp-ok" data-pin-info aria-live="polite"></div>
                ${ferr('latitude') || ferr('longitude') || ferr('sumber_lokasi')}</div>
                <div class="mt-4" data-field="alamat_singkat"><label class="lp-lbl" for="lp-alamat">Alamat singkat / patokan ${req}</label>
                <div class="lp-field ${st.errors.alamat_singkat ? 'bad' : ''}"><span><i data-lucide="map-pinned" class="text-coral-dark"></i></span><input id="lp-alamat" name="alamat_singkat" data-k="alamat" value="${esc(st.alamat)}" maxlength="255" placeholder="Contoh: Gang Melati RT 03, dekat mushola"></div>
                ${ferr('alamat_singkat')}</div>
            </div>`;
        if (st.step === 2)
            return `<div class="lp-in flex flex-col gap-5">
                <div data-field="severity"><h3 class="lp-q mb-2.5" tabindex="-1" data-focus>Seberapa parah banjirnya? ${req}</h3>
                <div class="lp-sev ${st.errors.severity ? 'bad' : ''}" role="radiogroup" aria-label="Tingkat keparahan">${Object.entries(SEV)
                    .map(([k, [label, color, desc]], i) => `<button type="button" class="sev-o ${st.sev === k ? 'on' : ''}" style="--c:${color}" data-sev="${k}" dusk="sev-${k}" role="radio" aria-checked="${st.sev === k}" tabindex="${st.sev === k || (!st.sev && i === 0) ? 0 : -1}"><b>${label}</b><small>${desc}</small></button>`)
                    .join('')}</div>
                ${ferr('severity')}</div>
                <div data-field="jumlah_warga"><div class="lp-row"><label class="lp-lbl !mb-0" for="lp-warga">Perkiraan warga terdampak ${req}</label>
                    <div class="lp-stp"><button type="button" data-pm aria-label="Kurangi"><i data-lucide="minus"></i></button><input id="lp-warga" name="jumlah_warga" type="number" inputmode="numeric" min="1" max="10000" value="${st.people}" data-people><button type="button" data-pp aria-label="Tambah"><i data-lucide="plus"></i></button></div></div>
                    ${ferr('jumlah_warga')}</div>
                <div data-field="foto"><label class="lp-lbl">Foto kondisi ${req}</label>
                    <label class="lp-drop ${st.errors.foto ? 'bad' : ''}" data-drop><input type="file" name="foto" accept="image/jpeg,image/png,image/webp" class="sr-only" data-photo dusk="lapor-foto"><span class="contents" data-drop-body>${dropBody()}</span></label>${ferr('foto')}</div>
            </div>`;
        return `<div class="lp-in flex flex-col gap-4">
            <div><h3 class="lp-q" tabindex="-1" data-focus>Ringkasan &amp; kontak</h3><p class="lp-sub">Periksa laporan Anda, lalu isi nomor WhatsApp untuk kabar status.</p></div>
            <div class="lp-sum">
                <div><i data-lucide="map-pin" class="text-coral-dark"></i><span>Kel. ${esc(st.cov?.kel || '-')} &middot; ${st.lat?.toFixed(5)}, ${st.lng?.toFixed(5)}</span></div>
                <div><i class="d" style="background:${SEV[st.sev]?.[1]}"></i><span>Banjir ${SEV[st.sev]?.[0]?.toLowerCase()} &middot; ${st.people} warga terdampak</span></div>
                <div><i data-lucide="image" class="text-teal-ink"></i><span>Foto terlampir</span></div>
            </div>
            <div data-field="nomor_wa"><label class="lp-lbl" for="lp-wa">Nomor WhatsApp ${req}</label>
            <div class="lp-field ${st.errors.nomor_wa ? 'bad' : ''}" data-wa-box><span><i data-lucide="message-circle" class="text-teal"></i>WA</span><input id="lp-wa" name="nomor_wa" data-k="wa" value="${esc(st.wa)}" inputmode="tel" autocomplete="tel" maxlength="20" placeholder="0812 3456 7890"></div>
            ${st.errors.nomor_wa ? ferr('nomor_wa') : `<div class="lp-hint" data-wa-hint>${WA_HINT}</div>`}</div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="lp-lbl" for="lp-nama">Nama pelapor <small>(opsional)</small></label><div class="lp-field"><input id="lp-nama" name="nama_pelapor" data-k="nama" value="${esc(st.nama)}" maxlength="100" autocomplete="name" placeholder="Nama Anda"></div>${ferr('nama_pelapor')}</div>
                <div><label class="lp-lbl" for="lp-catatan">Catatan <small>(opsional)</small></label><div class="lp-field"><input id="lp-catatan" name="catatan" data-k="catatan" value="${esc(st.catatan)}" maxlength="500" placeholder="Contoh: ada lansia belum bisa keluar"></div>${ferr('catatan')}</div>
            </div>
            <div data-field="setuju_dihubungi"><label class="lp-consent"><input type="checkbox" name="setuju_dihubungi" data-consent ${st.consent ? 'checked' : ''}><span>Saya setuju dihubungi Tim Startup ALIR lewat WhatsApp terkait laporan ini. Nomor saya <b>tidak ditampilkan</b> di peta publik.</span></label>${ferr('setuju_dihubungi')}</div>
            <div data-field="cf-turnstile-response"><div class="lp-ts" data-turnstile dusk="lapor-captcha"><span class="text-[13px] text-s-500">Memuat verifikasi CAPTCHA...</span></div>${ferr('cf-turnstile-response')}</div>
        </div>`;
    };

    const bannerHtml = () => (st.err ? `<div class="lp-err" role="alert" dusk="lapor-error"><i data-lucide="triangle-alert"></i><span>${esc(st.err)}</span></div>` : '');

    const render = () => {
        teardown();
        const s = st.step;
        m.innerHTML = `<div class="lp-h"><span class="lp-hi"><i data-lucide="siren"></i></span><div class="lp-ht"><h2>Lapor Banjir</h2><p>Laporan Anda membantu warga sekitar</p></div><button type="button" class="lp-x" data-x aria-label="Tutup"><i data-lucide="x"></i></button></div>
            <ol class="lp-steps" aria-label="Langkah">${LABELS.map((l, i) => `<li class="${i + 1 === s ? 'on' : i + 1 < s ? 'done' : ''}" ${i + 1 === s ? 'aria-current="step"' : ''}><span class="n">${i + 1 < s ? '<i data-lucide="check"></i>' : i + 1}</span>${l}</li>`).join('')}</ol>
            <div class="lp-body">${stepBody()}</div>
            <div class="lp-alert" data-alert>${bannerHtml()}</div>
            <div class="lp-f"><button type="button" class="lp-back" data-b ${s === 1 ? 'disabled' : ''}><i data-lucide="arrow-left"></i>Kembali</button><button type="button" class="lp-next" data-n dusk="lapor-next">${s === 3 ? 'Kirim laporan' : 'Lanjut'}<i data-lucide="${s === 3 ? 'send' : 'arrow-right'}"></i></button></div>`;
        icons();
        $('[data-x]', m).onclick = () => closeModal(ov);
        $('[data-b]', m).onclick = () => go(-1);
        $('[data-n]', m).onclick = next;
        $$('[data-k]', m).forEach((inp) =>
            inp.addEventListener('input', () => {
                st[inp.dataset.k] = inp.value;
                clearErr(inp.name);
            }),
        );
        if (s === 1) initMap();
        if (s === 2) bindStep2();
        if (s === 3) bindStep3();
        $('[data-focus]', m)?.focus({ preventScroll: true });
    };

    /** tampilkan / sembunyikan banner ringkas di atas footer tanpa merender ulang langkah */
    function showBanner(t) {
        st.err = t || '';
        const el = $('[data-alert]', m);
        if (!el) return;
        el.innerHTML = bannerHtml();
        icons();
    }

    /** hapus pesan error satu field saat pengguna memperbaikinya */
    function clearErr(name) {
        if (!name || !st.errors[name]) return;
        delete st.errors[name];
        const host = $(`[data-field="${name}"]`, m);
        $(`[data-err-for="${name}"]`, host || m)?.remove();
        host?.querySelector('.lp-field, .lp-drop, .lp-sev')?.classList.remove('bad');
        if (!Object.keys(st.errors).length) showBanner('');
    }

    /** tandai field bermasalah (inline) + banner ringkas, tanpa render ulang (CAPTCHA tidak ter-reset) */
    function invalid(errs, banner) {
        const keys = Object.keys(errs);
        keys.forEach((k) => {
            st.errors[k] = errs[k];
            const host = $(`[data-field="${k}"]`, m);
            if (!host) return;
            $(`[data-err-for="${k}"]`, host)?.remove();
            $('[data-wa-hint]', host)?.remove();
            host.querySelector('.lp-field, .lp-drop, .lp-sev')?.classList.add('bad');
            host.insertAdjacentHTML('beforeend', `<p class="lp-ferr" data-err-for="${k}" role="alert">${esc(errs[k])}</p>`);
        });
        showBanner(banner || errs[keys[0]]);
        const first = keys.map((k) => $(`[data-field="${k}"]`, m)).find(Boolean);
        if (first) {
            first.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            first.querySelector('input:not([type=file]), button[tabindex="0"], [data-gps]')?.focus({ preventScroll: true });
        }
    }

    const fail = (t) => showBanner(t);
    const go = (dir) =>
        withProgress(() => {
            st.err = '';
            st.step += dir;
            render();
        });

    /* ---------- langkah 1: lokasi ---------- */
    function setPin(lat, lng, sumber = 'pin', fly = false) {
        st.lat = lat;
        st.lng = lng;
        st.sumber = sumber;
        st.cov = { pending: true };
        ['latitude', 'longitude', 'sumber_lokasi'].forEach(clearErr);
        if (st.err && !Object.keys(st.errors).length) showBanner('');
        if (pin) pin.setLatLng([lat, lng]);
        else
            pin = L.marker([lat, lng], { icon: pinIcon(), draggable: true, keyboard: true, title: 'Lokasi banjir' })
                .addTo(pmap)
                .on('dragend', (e) => setPin(e.target.getLatLng().lat, e.target.getLatLng().lng, 'pin'));
        if (fly) {
            pmap.invalidateSize();
            pmap.flyTo([lat, lng], 16, { duration: 0.8 });
        }
        pinInfo();
        checkCoverage(lat, lng);
    }

    async function checkCoverage(lat, lng) {
        const seq = ++checkSeq;
        let cov;
        try {
            const res = await fetch(config().url.cek, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ latitude: lat, longitude: lng }),
            });
            if (!res.ok) throw new Error(String(res.status));
            const j = await res.json();
            cov = { ok: !!j.dalam_cakupan, kel: j.wilayah, pesan: j.pesan };
        } catch {
            cov = { ...localCoverage(lat, lng), offline: true };
        }
        if (seq !== checkSeq) return; // pin sudah dipindah lagi
        st.cov = cov;
        pinInfo();
    }

    function pinInfo() {
        const el = $('[data-pin-info]', m);
        if (!el) return;
        el.dataset.state = st.lat == null ? 'empty' : st.cov?.pending ? 'pending' : st.cov?.ok ? 'ok' : 'out';
        if (st.lat == null) el.innerHTML = '<i data-lucide="map-pin" class="text-alert"></i><span>Belum ada pin. Ketuk peta untuk memilih lokasi.</span>';
        else if (st.cov?.pending) el.innerHTML = '<span class="lp-spin"></span><span>Memeriksa wilayah cakupan...</span>';
        else if (st.cov?.ok) el.innerHTML = `<i data-lucide="circle-check" class="text-teal"></i><span>Di dalam cakupan <b>Kel. ${esc(st.cov.kel)}</b> &middot; ${st.lat.toFixed(5)}, ${st.lng.toFixed(5)} <span class="text-s-500">(${st.sumber === 'gps' ? 'GPS' : 'pin'})</span></span>`;
        else el.innerHTML = `<i data-lucide="circle-alert" class="text-alert"></i><span>${esc(st.cov?.pesan || 'Titik berada di luar wilayah cakupan ALIR. Geser pin ke lokasi banjir yang benar.')}</span>`;
        el.classList.toggle('bad', el.dataset.state === 'out');
        icons();
    }

    function initMap() {
        const c = config();
        const el = $('#pmap', m);
        pmap = baseMap(el, { center: c.pusat, zoom: 13 });
        const bounds = drawCoverage(pmap, c.wilayah);
        if (st.lat != null) {
            // pulihkan pin lama tanpa memeriksa ulang bila hasilnya sudah ada
            const keep = st.cov;
            pin = L.marker([st.lat, st.lng], { icon: pinIcon(), draggable: true, keyboard: true, title: 'Lokasi banjir' })
                .addTo(pmap)
                .on('dragend', (e) => setPin(e.target.getLatLng().lat, e.target.getLatLng().lng, 'pin'));
            pmap.setView([st.lat, st.lng], 16);
            if (!keep || keep.pending) checkCoverage(st.lat, st.lng);
        } else if (bounds?.isValid()) pmap.fitBounds(bounds.pad(0.1));
        pinInfo();
        pmap.on('click', (e) => setPin(e.latlng.lat, e.latlng.lng, 'pin'));
        // dipakai uji Dusk & teknologi bantu: pasang pin tanpa klik pada kanvas peta
        el.alirSetPin = (lat, lng, sumber = 'pin') => setPin(lat, lng, sumber, true);

        $('[data-gps]', m).onclick = (e) => {
            const btn = e.currentTarget;
            const reset = () => {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="locate-fixed"></i>Gunakan lokasi saya';
                icons();
            };
            if (!navigator.geolocation) return toast('GPS tidak tersedia di perangkat ini. Ketuk peta untuk memasang pin.', 'warn');
            btn.disabled = true;
            btn.innerHTML = '<span class="lp-spin"></span>Mencari lokasi...';
            navigator.geolocation.getCurrentPosition(
                (p) => {
                    if (!pmap) return;
                    reset();
                    setPin(p.coords.latitude, p.coords.longitude, 'gps', true);
                },
                () => {
                    if (!pmap) return;
                    reset();
                    toast('Lokasi perangkat tidak dapat diakses. Ketuk peta untuk memasang pin.', 'warn');
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 },
            );
        };
        setTimeout(() => pmap?.invalidateSize(), 400);
    }

    /* ---------- langkah 2: kondisi ---------- */
    function bindStep2() {
        const pick = (k, focus) => {
            st.sev = k;
            clearErr('severity');
            $$('[data-sev]', m).forEach((b) => {
                const on = b.dataset.sev === k;
                b.classList.toggle('on', on);
                b.setAttribute('aria-checked', String(on));
                b.tabIndex = on ? 0 : -1;
                if (on && focus) b.focus();
            });
        };
        const keys = Object.keys(SEV);
        $$('[data-sev]', m).forEach((b) => {
            b.addEventListener('click', () => pick(b.dataset.sev));
            b.addEventListener('keydown', (e) => {
                const d = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[e.key];
                if (!d) return;
                e.preventDefault();
                pick(keys[(keys.indexOf(b.dataset.sev) + d + keys.length) % keys.length], true);
            });
        });
        const inp = $('[data-people]', m);
        const setPeople = (n) => {
            st.people = Math.min(10000, Math.max(1, Math.round(Number(n) || 1)));
            inp.value = st.people;
            clearErr('jumlah_warga');
        };
        $('[data-pm]', m).onclick = () => setPeople(st.people - 1);
        $('[data-pp]', m).onclick = () => setPeople(st.people + 1);
        inp.addEventListener('change', () => setPeople(inp.value));
        inp.addEventListener('input', () => {
            const n = Number(inp.value);
            if (inp.value !== '' && Number.isFinite(n)) st.people = Math.round(n);
            clearErr('jumlah_warga');
        });

        const maxKb = config().fotoMaksKb || 5120;
        const input = $('[data-photo]', m);
        const file = (f) => {
            if (!f) return;
            if (!TYPES.test(f.type)) return invalid({ foto: 'Format foto harus JPG, PNG, atau WEBP.' });
            if (f.size > maxKb * 1024) return invalid({ foto: `Ukuran foto maksimal ${Math.round(maxKb / 1024)} MB.` });
            if (st.photoUrl) URL.revokeObjectURL(st.photoUrl);
            st.file = f;
            st.photoUrl = URL.createObjectURL(f);
            clearErr('foto');
            $('[data-drop-body]', m).innerHTML = dropBody();
            icons();
        };
        input.onchange = (e) => file(e.target.files[0]);
        const d = $('[data-drop]', m);
        d.ondragover = (e) => (e.preventDefault(), d.classList.add('drag'));
        d.ondragleave = () => d.classList.remove('drag');
        d.ondrop = (e) => {
            e.preventDefault();
            d.classList.remove('drag');
            file(e.dataTransfer.files[0]);
        };
    }

    /* ---------- langkah 3: kontak, CAPTCHA ---------- */
    function bindStep3() {
        const box = $('[data-wa-box]', m), inp = $('[data-k=wa]', m);
        const check = () => {
            const v = st.wa.trim(), ok = validWa(v);
            box.classList.toggle('ok', !!v && ok);
            box.classList.toggle('bad', !!v && !ok);
            let hint = $('[data-wa-hint]', m);
            if (!hint) {
                box.insertAdjacentHTML('afterend', '<div data-wa-hint></div>');
                hint = $('[data-wa-hint]', m);
            }
            hint.className = 'lp-hint ' + (!v ? '' : ok ? 'text-teal-ink' : 'text-alert-ink');
            hint.textContent = !v ? WA_HINT : ok ? 'Nomor valid.' : 'Nomor tidak valid. Contoh: 081234567890';
        };
        inp.addEventListener('input', check);
        if (st.wa && !st.errors.nomor_wa) check();
        $('[data-consent]', m).onchange = (e) => {
            st.consent = e.target.checked;
            clearErr('setuju_dihubungi');
        };
        mountTurnstile();
    }

    function mountTurnstile() {
        const host = $('[data-turnstile]', m);
        const sitekey = config().turnstile;
        if (!host) return;
        st.token = ''; // token Turnstile sekali pakai: setiap render meminta token baru
        if (!sitekey) {
            host.innerHTML = '<span class="text-[13px] text-alert-ink">CAPTCHA belum dikonfigurasi (TURNSTILE_SITE_KEY).</span>';
            return;
        }
        let tries = 0;
        const tryRender = () => {
            if (!host.isConnected) return;
            if (!window.turnstile) {
                if (++tries > 100) {
                    host.innerHTML = '<span class="text-[13px] text-alert-ink">CAPTCHA gagal dimuat. Periksa koneksi internet lalu buka ulang form.</span>';
                    return;
                }
                return setTimeout(tryRender, 100);
            }
            host.innerHTML = '';
            const done = (t) => {
                st.token = t || '';
                if (st.token) host.dataset.ok = '1';
                else delete host.dataset.ok;
            };
            widget = window.turnstile.render(host, {
                sitekey,
                language: 'id',
                theme: 'light',
                callback: (t) => {
                    done(t);
                    clearErr('cf-turnstile-response');
                },
                'expired-callback': () => done(''),
                'error-callback': () => done(''),
            });
        };
        tryRender();
    }

    /* ---------- navigasi & kirim ---------- */
    function next() {
        const s = st.step;
        if (s === 1) {
            if (st.lat == null) return invalid({ latitude: 'Tandai lokasi banjir di peta atau gunakan GPS.' });
            if (st.cov?.pending) return fail('Tunggu sebentar, lokasi sedang diperiksa.');
            if (!st.cov?.ok) return fail(st.cov?.pesan || 'Titik berada di luar wilayah cakupan ALIR. Koreksi lokasi pin.');
            if (st.alamat.trim().length < 5) return invalid({ alamat_singkat: 'Isi alamat singkat atau patokan lokasi (min. 5 karakter).' });
        }
        if (s === 2) {
            const e = {};
            if (!st.sev) e.severity = 'Pilih tingkat keparahan banjir.';
            if (!(st.people >= 1 && st.people <= 10000)) e.jumlah_warga = 'Perkiraan warga terdampak harus 1 sampai 10.000 orang.';
            if (!st.file) e.foto = 'Unggah foto kondisi banjir sebagai bukti.';
            if (Object.keys(e).length) return invalid(e, 'Lengkapi isian yang ditandai.');
        }
        if (s === 3) {
            const e = {};
            if (!validWa(st.wa)) e.nomor_wa = 'Format nomor WhatsApp tidak valid. Contoh: 081234567890.';
            if (!st.consent) e.setuju_dihubungi = 'Centang persetujuan agar Tim ALIR dapat menghubungi Anda.';
            if (!st.token) e['cf-turnstile-response'] = 'Selesaikan verifikasi CAPTCHA terlebih dahulu.';
            if (Object.keys(e).length) return invalid(e, 'Lengkapi isian yang ditandai.');
            return submit();
        }
        go(1);
    }

    async function submit() {
        if (st.sending) return;
        st.sending = true;
        const btn = $('[data-n]', m);
        btn.disabled = true;
        $('[data-b]', m).disabled = true;
        $('[data-x]', m).disabled = true;
        btn.innerHTML = 'Mengirim...<span class="lp-spin lp-spin-w"></span>';
        window.NProgress?.start();

        const fd = new FormData();
        fd.append('latitude', st.lat.toFixed(7));
        fd.append('longitude', st.lng.toFixed(7));
        fd.append('sumber_lokasi', st.sumber);
        fd.append('alamat_singkat', st.alamat.trim());
        fd.append('severity', st.sev);
        fd.append('jumlah_warga', String(st.people));
        fd.append('foto', st.file, st.file.name);
        fd.append('nomor_wa', st.wa.trim());
        fd.append('nama_pelapor', st.nama.trim());
        fd.append('catatan', st.catatan.trim());
        fd.append('setuju_dihubungi', st.consent ? '1' : '0');
        fd.append('cf-turnstile-response', st.token);

        let res = null, body = {};
        try {
            res = await fetch(config().url.kirim, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
                body: fd,
            });
            body = await res.json().catch(() => ({}));
        } catch {
            res = null;
        }
        st.sending = false;

        if (res?.status === 201 && body.redirect) {
            if (st.photoUrl) URL.revokeObjectURL(st.photoUrl);
            st = fresh();
            window.location.assign(body.redirect); // NProgress tetap berjalan sampai halaman berikutnya
            return;
        }
        window.NProgress?.done();

        if (res?.status === 422) {
            const errs = body.errors || {};
            st.errors = Object.fromEntries(Object.entries(errs).map(([k, v]) => [k, Array.isArray(v) ? v[0] : String(v)]));
            const steps = Object.keys(st.errors).map((k) => FIELD_STEP[k] || 3);
            st.step = steps.length ? Math.min(...steps) : 3;
            if (st.errors.latitude) st.cov = { ok: false, pesan: st.errors.latitude };
            st.err = 'Periksa kembali isian yang ditandai. Isian Anda tidak dihapus.';
        } else if (res?.status === 419) {
            st.err = 'Sesi halaman kedaluwarsa. Muat ulang halaman lalu kirim kembali.';
        } else if (res?.status === 429) {
            st.err = body.message || 'Terlalu banyak laporan dari perangkat ini. Coba lagi beberapa menit lagi.';
        } else {
            st.err = 'Laporan gagal dikirim. Periksa koneksi internet Anda lalu coba lagi.';
        }
        render();
    }

    ov = openModal('', { cls: 'lapor', label: 'Lapor Banjir', onClose: teardown });
    m = $('.ld-modal', ov);
    render();
}
