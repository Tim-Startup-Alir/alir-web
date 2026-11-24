@extends('layouts.komunitas')

@section('title', 'Konfirmasi Laporan')
@section('topbar_title', 'Konfirmasi Laporan')
@section('topbar_subtitle', 'Tinjau lokasi dan kirim bukti kondisi lapangan')

@section('content')
    <div class="pg-h">
        <div><span class="kicker">F-03 PEMETAAN TITIK BANJIR</span><h2>Konfirmasi Kondisi Laporan</h2></div>
        <a class="btn btn-ghost" href="{{ route('komunitas.konfirmasi.index') }}">Kembali ke daftar</a>
    </div>

    @if ($errors->any())
        <div class="card konfirmasi-errors" role="alert" aria-live="assertive" tabindex="-1" dusk="konfirmasi-errors">
            <strong>Periksa kembali isian berikut:</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="konfirmasi-detail-grid">
        <section class="card konfirmasi-report" aria-labelledby="report-title">
            <span class="kicker">{{ $laporan->kode_pelacakan }}</span>
            <h3 id="report-title">{{ $laporan->alamat_singkat }}</h3>
            <dl class="konfirmasi-dl">
                <dt>Wilayah</dt><dd>{{ $laporan->wilayah?->nama_kelurahan ?? 'Tidak tersedia' }}</dd>
                <dt>Tingkat dampak</dt><dd>{{ ucfirst($laporan->severity) }}</dd>
                <dt>Jumlah warga</dt><dd>{{ $laporan->jumlah_warga }}</dd>
                <dt>Waktu laporan</dt><dd>{{ $laporan->created_at?->format('d M Y H:i') }}</dd>
                <dt>Kepercayaan</dt><dd><x-badge-kepercayaan :level="$levelKepercayaan" /></dd>
            </dl>

            <h4>Foto laporan</h4>
            @if ($laporan->foto->isEmpty())
                <p class="empty" role="status">Laporan ini belum memiliki foto.</p>
            @else
                <div class="konfirmasi-photos">
                    @foreach ($laporan->foto as $foto)
                        <a href="{{ route('komunitas.konfirmasi.foto-laporan', [$laporan, $foto]) }}" target="_blank" rel="noopener" aria-label="Buka foto laporan di tab baru">
                            <img src="{{ route('komunitas.konfirmasi.foto-laporan', [$laporan, $foto]) }}" alt="Foto laporan banjir" loading="lazy">
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="card konfirmasi-map-card" aria-labelledby="map-title">
            <h3 id="map-title">Lokasi laporan dan posko</h3>
            <div id="konfirmasi-map" class="konfirmasi-map" role="application" aria-label="Peta lokasi laporan dan posko komunitas" tabindex="0" dusk="konfirmasi-map"
                data-report-lat="{{ $laporan->latitude }}" data-report-lng="{{ $laporan->longitude }}">
                @foreach ($posko as $item)
                    <span hidden data-posko-lat="{{ $item->latitude }}" data-posko-lng="{{ $item->longitude }}" data-posko-name="{{ $item->nama }}"></span>
                @endforeach
            </div>
            <p id="konfirmasi-map-state" class="muted" role="status" aria-live="polite">Peta sedang dimuat.</p>
        </section>

        <section class="card konfirmasi-form-card" aria-labelledby="form-title">
            <h3 id="form-title">Hasil pemeriksaan lapangan</h3>
            <form method="POST" action="{{ route('komunitas.konfirmasi.store', $laporan) }}" enctype="multipart/form-data" id="form-konfirmasi" dusk="form-konfirmasi">
                @csrf
                <fieldset class="konfirmasi-fieldset">
                    <legend>Pilih hasil konfirmasi</legend>
                    <label class="konfirmasi-choice"><input type="radio" name="hasil" value="terkonfirmasi" required @checked(old('hasil') === 'terkonfirmasi')> <span>Banjir terkonfirmasi</span></label>
                    <label class="konfirmasi-choice"><input type="radio" name="hasil" value="tidak_terkonfirmasi" required @checked(old('hasil') === 'tidak_terkonfirmasi')> <span>Tidak terkonfirmasi</span></label>
                    @error('hasil')<small class="field-error">{{ $message }}</small>@enderror
                </fieldset>

                <div class="fld">
                    <label for="foto-bukti">Foto bukti <span aria-hidden="true">(wajib)</span></label>
                    <input class="input" id="foto-bukti" name="foto" type="file" accept="image/jpeg,image/png" required aria-describedby="foto-help foto-error" dusk="konfirmasi-foto">
                    <small id="foto-help" class="muted">JPG, JPEG, atau PNG. Maksimal {{ number_format($radiusFotoKb, 0, ',', '.') }} KB.</small>
                    @error('foto')<small id="foto-error" class="field-error">{{ $message }}</small>@enderror
                    <img id="foto-preview" class="konfirmasi-preview" alt="Pratinjau foto bukti" hidden>
                </div>

                <div class="fld">
                    <label for="catatan">Catatan <span class="muted">(opsional, maks. 500 karakter)</span></label>
                    <textarea class="input" id="catatan" name="catatan" rows="4" maxlength="500" aria-label="Catatan konfirmasi" dusk="konfirmasi-catatan">{{ old('catatan') }}</textarea>
                    @error('catatan')<small class="field-error">{{ $message }}</small>@enderror
                </div>

                <p id="form-state" class="muted" role="status" aria-live="polite"></p>
                <button class="btn btn-primary" id="submit-konfirmasi" type="submit" dusk="submit-konfirmasi">Kirim konfirmasi</button>
            </form>
        </section>
    </div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
.konfirmasi-detail-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:16px}.konfirmasi-report,.konfirmasi-map-card,.konfirmasi-form-card{padding:clamp(14px,2vw,22px);min-width:0}.konfirmasi-report h3,.konfirmasi-map-card h3,.konfirmasi-form-card h3{margin:8px 0 16px}.konfirmasi-dl{display:grid;grid-template-columns:minmax(120px,auto) 1fr;gap:8px 12px}.konfirmasi-dl dt{color:var(--s-500)}.konfirmasi-dl dd{margin:0;font-weight:600;overflow-wrap:anywhere}.konfirmasi-photos{display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:10px}.konfirmasi-photos img{width:100%;height:100px;object-fit:cover;border:1px solid var(--s-200);border-radius:var(--r-sm)}.konfirmasi-map{height:340px;min-height:260px;border:1px solid var(--s-200);border-radius:var(--r-md);z-index:0}.konfirmasi-form-card{grid-column:1/-1}.konfirmasi-fieldset{display:grid;gap:10px;border:0;padding:0;margin:0 0 18px}.konfirmasi-fieldset legend{font-weight:700;margin-bottom:8px}.konfirmasi-choice{display:flex;align-items:flex-start;gap:10px;padding:12px;border:1px solid var(--s-200);border-radius:var(--r-sm);cursor:pointer}.konfirmasi-choice:focus-within,.konfirmasi-choice:has(input:focus-visible){outline:2px solid var(--water);outline-offset:2px}.konfirmasi-choice input{margin-top:3px;accent-color:var(--water)}.konfirmasi-form-card .fld{display:grid;gap:6px;margin:14px 0}.konfirmasi-form-card textarea{resize:vertical}.konfirmasi-preview{max-width:min(100%,360px);max-height:260px;object-fit:contain;border-radius:var(--r-sm);border:1px solid var(--s-200)}.konfirmasi-errors{padding:14px;margin-bottom:16px;color:var(--red-ink)}.field-error{display:block;color:var(--red-ink)}#submit-konfirmasi:disabled{opacity:.65;cursor:wait}
@media(max-width:700px){.konfirmasi-detail-grid{grid-template-columns:1fr}.konfirmasi-form-card{grid-column:auto}.konfirmasi-map{height:300px}.konfirmasi-dl{grid-template-columns:1fr;gap:3px}.konfirmasi-dl dd{margin-bottom:8px}}
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const mapNode = document.getElementById('konfirmasi-map');
    const mapState = document.getElementById('konfirmasi-map-state');
    if (mapNode && window.L) {
        const report = [Number(mapNode.dataset.reportLat), Number(mapNode.dataset.reportLng)];
        const map = L.map(mapNode).setView(report, 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; OpenStreetMap'}).addTo(map);
        const points = [report];
        L.marker(report, {title: 'Lokasi laporan'}).addTo(map).bindPopup('Lokasi laporan');
        mapNode.querySelectorAll('[data-posko-lat]').forEach((node, index) => {
            const point = [Number(node.dataset.poskoLat), Number(node.dataset.poskoLng)];
            points.push(point);
            L.marker(point, {title: node.dataset.poskoName || `Posko ${index + 1}`}).addTo(map).bindPopup(node.dataset.poskoName || `Posko ${index + 1}`);
        });
        if (points.length > 1) map.fitBounds(points, {padding: [24, 24], maxZoom: 15});
        mapState.textContent = 'Lokasi laporan dan posko ditampilkan.';
    } else if (mapState) {
        mapState.textContent = 'Peta tidak dapat dimuat. Koordinat laporan tetap tersedia pada detail.';
        mapState.classList.add('field-error');
    }

    const form = document.getElementById('form-konfirmasi');
    const file = document.getElementById('foto-bukti');
    const preview = document.getElementById('foto-preview');
    const state = document.getElementById('form-state');
    const submit = document.getElementById('submit-konfirmasi');
    const maxBytes = Number({{ $radiusFotoKb }}) * 1024;
    let previewUrl = null;
    file?.addEventListener('change', () => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = null;
        preview.hidden = true;
        const selected = file.files?.[0];
        if (!selected) return;
        if (!['image/jpeg', 'image/png'].includes(selected.type) || selected.size > maxBytes) {
            state.textContent = selected.size > maxBytes ? 'Ukuran foto melebihi batas.' : 'Pilih foto JPG, JPEG, atau PNG.';
            file.value = '';
            return;
        }
        previewUrl = URL.createObjectURL(selected);
        preview.src = previewUrl;
        preview.hidden = false;
        state.textContent = 'Pratinjau foto siap.';
    });
    form?.addEventListener('submit', event => {
        if (!window.confirm('Kirim hasil konfirmasi dan foto bukti ini?')) {
            event.preventDefault();
            return;
        }
        submit.disabled = true;
        submit.textContent = 'Mengirim…';
        state.textContent = 'Konfirmasi sedang dikirim.';
    });
    window.addEventListener('beforeunload', () => { if (previewUrl) URL.revokeObjectURL(previewUrl); });
});
</script>
@endpush
