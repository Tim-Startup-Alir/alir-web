@php
    $isPerbaikan = isset($komunitas);
    $latAwal = old('posko_latitude', $poskoUtama->latitude ?? '');
    $lngAwal = old('posko_longitude', $poskoUtama->longitude ?? '');
@endphp

<div class="reg-sec">
    <h3><b>1</b>Data komunitas &amp; titik posko</h3>

    <div class="grid-alir g2">
        <div class="field">
            <label for="nama">Nama komunitas / tim <span class="text-red-600">*</span></label>
            <input
                id="nama"
                name="nama"
                type="text"
                class="input {{ $errors->has('nama') ? 'err' : '' }}"
                value="{{ old('nama', $komunitas->nama ?? '') }}"
                placeholder="Contoh: Komunitas Sigap Tirto"
                autocomplete="organization"
                required
            >
            @error('nama')<div class="err-msg">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="wilayah_id">Kelurahan layanan <span class="text-red-600">*</span></label>
            <select id="wilayah_id" name="wilayah_id" class="input {{ $errors->has('wilayah_id') ? 'err' : '' }}" required>
                <option value="">-- Pilih kelurahan layanan --</option>
                @foreach ($wilayahAktif as $w)
                    <option value="{{ $w->id }}" @selected((string) old('wilayah_id', $komunitas->wilayah_id ?? '') === (string) $w->id)>
                        {{ $w->nama_kelurahan }} ({{ $w->kecamatan }})
                    </option>
                @endforeach
            </select>
            @error('wilayah_id')<div class="err-msg">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="field mt-4">
        <label>Titik posko / sekretariat di peta <span class="text-red-600">*</span></label>
        <div id="pmap" class="map" style="height:230px;border:1.5px solid var(--s-200);cursor:crosshair;border-radius:14px"></div>
        <span class="xs muted" id="pint">
            {{ $latAwal !== '' && $lngAwal !== '' ? "Lokasi dipilih: {$latAwal}, {$lngAwal}" : 'Ketuk peta untuk memasang pin lokasi posko atau isi koordinat di bawah.' }}
        </span>
    </div>

    <div class="grid-alir g2 mt-3">
        <div class="field">
            <label for="posko_latitude">Latitude posko <span class="text-red-600">*</span></label>
            <input id="posko_latitude" name="posko_latitude" type="text" class="input {{ $errors->has('posko_latitude') ? 'err' : '' }}" value="{{ $latAwal }}" placeholder="-6.8928121" required>
            @error('posko_latitude')<div class="err-msg">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="posko_longitude">Longitude posko <span class="text-red-600">*</span></label>
            <input id="posko_longitude" name="posko_longitude" type="text" class="input {{ $errors->has('posko_longitude') ? 'err' : '' }}" value="{{ $lngAwal }}" placeholder="109.6515333" required>
            @error('posko_longitude')<div class="err-msg">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="posko_nama">Nama posko (opsional)</label>
            <input id="posko_nama" name="posko_nama" type="text" class="input {{ $errors->has('posko_nama') ? 'err' : '' }}" value="{{ old('posko_nama', $poskoUtama->nama ?? '') }}" placeholder="Posko Utama Kelurahan">
            @error('posko_nama')<div class="err-msg">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="posko_radius_m">Radius layanan (meter)</label>
            <input id="posko_radius_m" name="posko_radius_m" type="number" min="100" max="10000" class="input {{ $errors->has('posko_radius_m') ? 'err' : '' }}" value="{{ old('posko_radius_m', $poskoUtama->radius_m ?? 1500) }}">
            @error('posko_radius_m')<div class="err-msg">{{ $message }}</div>@enderror
        </div>

        <div class="field md:col-span-2">
            <label for="posko_alamat">Alamat posko</label>
            <input id="posko_alamat" name="posko_alamat" type="text" class="input {{ $errors->has('posko_alamat') ? 'err' : '' }}" value="{{ old('posko_alamat', $poskoUtama->alamat ?? '') }}" placeholder="Jl. Raya Kelurahan...">
            @error('posko_alamat')<div class="err-msg">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 mt-4">
        <label class="check">
            <input type="checkbox" name="pernyataan_luar_cakupan" value="1" @checked(old('pernyataan_luar_cakupan', isset($poskoUtama) && $poskoUtama->pernyataan_luar_cakupan_pada !== null))>
            <span class="text-xs font-semibold text-amber-900">
                Jika titik posko berada di luar 3 kelurahan pilot (Tirto, Pasirkratonkramat, Degayu), kami menyatakan siap menanggung risiko jarak layanan.
            </span>
        </label>
        @error('pernyataan_luar_cakupan')<div class="err-msg mt-2">{{ $message }}</div>@enderror
    </div>
</div>

<div class="reg-sec">
    <h3><b>2</b>Penanggung jawab</h3>
    <div class="grid-alir g2">
        <div class="field">
            <label for="nama_pic">Nama lengkap PIC <span class="text-red-600">*</span></label>
            <input id="nama_pic" name="nama_pic" type="text" class="input {{ $errors->has('nama_pic') ? 'err' : '' }}" value="{{ old('nama_pic', $komunitas->nama_pic ?? '') }}" autocomplete="name" placeholder="Nama sesuai KTP" required>
            @error('nama_pic')<div class="err-msg">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="nomor_whatsapp_pic">No. WhatsApp <span class="text-red-600">*</span></label>
            <input id="nomor_whatsapp_pic" name="nomor_whatsapp_pic" type="tel" inputmode="tel" class="input {{ $errors->has('nomor_whatsapp_pic') ? 'err' : '' }}" value="{{ old('nomor_whatsapp_pic', $komunitas->nomor_whatsapp_pic ?? '') }}" placeholder="081234567890" required>
            @error('nomor_whatsapp_pic')<div class="err-msg">{{ $message }}</div>@enderror
        </div>

        <div class="field md:col-span-2">
            <label for="email">Email <span class="text-red-600">*</span></label>
            @if ($isPerbaikan)
                <input id="email" type="email" class="input" value="{{ $komunitas->user->email }}" disabled>
                <span class="xs muted">Email akun tetap sama seperti pendaftaran awal.</span>
            @else
                <input id="email" name="email" type="email" class="input {{ $errors->has('email') ? 'err' : '' }}" value="{{ old('email') }}" autocomplete="username" placeholder="nama@komunitas.id" required>
                @error('email')<div class="err-msg">{{ $message }}</div>@enderror
            @endif
        </div>
    </div>
</div>

<div class="reg-sec">
    <h3><b>3</b>Kapasitas</h3>
    <div class="grid-alir g3">
        <div class="field">
            <label for="total_anggota">Jumlah anggota aktif <span class="text-red-600">*</span></label>
            <input id="total_anggota" name="total_anggota" type="number" min="1" max="5000" class="input {{ $errors->has('total_anggota') ? 'err' : '' }}" value="{{ old('total_anggota', $komunitas->total_anggota ?? '') }}" placeholder="12" required>
            @error('total_anggota')<div class="err-msg">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="kapasitas_max">Kapasitas siap tugas</label>
            <input id="kapasitas_max" name="kapasitas_max" type="number" min="1" max="5000" class="input {{ $errors->has('kapasitas_max') ? 'err' : '' }}" value="{{ old('kapasitas_max', $komunitas->kapasitas_max ?? '') }}" placeholder="Samakan bila kosong">
            @error('kapasitas_max')<div class="err-msg">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="jumlah_perahu">Jumlah perahu / alat evakuasi <span class="text-red-600">*</span></label>
            <input id="jumlah_perahu" name="jumlah_perahu" type="number" min="0" max="500" class="input {{ $errors->has('jumlah_perahu') ? 'err' : '' }}" value="{{ old('jumlah_perahu', $komunitas->jumlah_perahu ?? 0) }}" required>
            @error('jumlah_perahu')<div class="err-msg">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="reg-sec">
    <h3><b>4</b>Dokumen pendukung @if (! $isPerbaikan)<span class="text-red-600">*</span>@endif</h3>
    <p class="small muted mb-3">Unggah surat izin, identitas komunitas, atau dokumen pendukung lain dalam format PDF atau JPG (maks. 5 MB).</p>
    <div class="field">
        <label for="dokumen_pendukung">
            Berkas PDF/JPG
            @if ($isPerbaikan)
                <span class="muted">(opsional, unggah hanya bila ingin mengganti dokumen sebelumnya)</span>
            @else
                <span class="text-red-600">*</span>
            @endif
        </label>
        <input
            id="dokumen_pendukung"
            name="dokumen_pendukung"
            type="file"
            accept=".pdf,.jpg,.jpeg,application/pdf,image/jpeg"
            class="input py-2.5 {{ $errors->has('dokumen_pendukung') ? 'err' : '' }}"
            @if (! $isPerbaikan) required @endif
        >
        @error('dokumen_pendukung')<div class="err-msg">{{ $message }}</div>@enderror
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const mapEl = document.getElementById('pmap');
    const latInput = document.getElementById('posko_latitude');
    const lngInput = document.getElementById('posko_longitude');
    const infoEl = document.getElementById('pint');

    if (!mapEl || !window.L) return;

    const initLat = parseFloat(latInput.value) || -6.8845;
    const initLng = parseFloat(lngInput.value) || 109.6700;
    const map = L.map(mapEl, { center: [initLat, initLng], zoom: 13 });
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    let marker = null;
    const setPin = (lat, lng) => {
        latInput.value = Number(lat).toFixed(7);
        lngInput.value = Number(lng).toFixed(7);
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng]).addTo(map);
        }
        if (infoEl) {
            infoEl.textContent = `Lokasi dipilih: ${latInput.value}, ${lngInput.value}`;
        }
    };

    if (latInput.value && lngInput.value) {
        setPin(initLat, initLng);
    }

    map.on('click', (e) => setPin(e.latlng.lat, e.latlng.lng));
    setTimeout(() => map.invalidateSize(), 250);
});
</script>
@endpush
