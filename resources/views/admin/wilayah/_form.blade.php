@php
    $isEdit = isset($wilayah);
@endphp

<div class="grid-alir g2">
    <div class="field">
        <label for="kode_adm4">Kode Wilayah (ADM4 Kemendagri) <span class="text-red-600">*</span></label>
        <input
            id="kode_adm4"
            name="kode_adm4"
            type="text"
            class="input {{ $errors->has('kode_adm4') ? 'err' : '' }}"
            value="{{ old('kode_adm4', $wilayah->kode_adm4 ?? '') }}"
            placeholder="Contoh: 33.75.01.1009"
            required
        >
        @error('kode_adm4')
            <div class="err-msg">{{ $message }}</div>
        @enderror
    </div>

    <div class="field">
        <label for="radius_cakupan_m">Radius Cakupan Cadangan (meter)</label>
        <input
            id="radius_cakupan_m"
            name="radius_cakupan_m"
            type="number"
            min="1"
            class="input {{ $errors->has('radius_cakupan_m') ? 'err' : '' }}"
            value="{{ old('radius_cakupan_m', $wilayah->radius_cakupan_m ?? 1500) }}"
            placeholder="1500"
        >
        @error('radius_cakupan_m')
            <div class="err-msg">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="grid-alir g2">
    <div class="field">
        <label for="nama_kelurahan">Nama Kelurahan <span class="text-red-600">*</span></label>
        <input
            id="nama_kelurahan"
            name="nama_kelurahan"
            type="text"
            class="input {{ $errors->has('nama_kelurahan') ? 'err' : '' }}"
            value="{{ old('nama_kelurahan', $wilayah->nama_kelurahan ?? '') }}"
            placeholder="Contoh: Tirto"
            required
        >
        @error('nama_kelurahan')
            <div class="err-msg">{{ $message }}</div>
        @enderror
    </div>

    <div class="field">
        <label for="kecamatan">Kecamatan <span class="text-red-600">*</span></label>
        <input
            id="kecamatan"
            name="kecamatan"
            type="text"
            class="input {{ $errors->has('kecamatan') ? 'err' : '' }}"
            value="{{ old('kecamatan', $wilayah->kecamatan ?? '') }}"
            placeholder="Contoh: Pekalongan Barat"
            required
        >
        @error('kecamatan')
            <div class="err-msg">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="grid-alir g2">
    <div class="field">
        <label for="latitude">Latitude Titik Pusat <span class="text-red-600">*</span></label>
        <input
            id="latitude"
            name="latitude"
            type="text"
            class="input {{ $errors->has('latitude') ? 'err' : '' }}"
            value="{{ old('latitude', $wilayah->latitude ?? '') }}"
            placeholder="-6.8928121"
            required
        >
        @error('latitude')
            <div class="err-msg">{{ $message }}</div>
        @enderror
    </div>

    <div class="field">
        <label for="longitude">Longitude Titik Pusat <span class="text-red-600">*</span></label>
        <input
            id="longitude"
            name="longitude"
            type="text"
            class="input {{ $errors->has('longitude') ? 'err' : '' }}"
            value="{{ old('longitude', $wilayah->longitude ?? '') }}"
            placeholder="109.6515333"
            required
        >
        @error('longitude')
            <div class="err-msg">{{ $message }}</div>
        @enderror
    </div>
</div>

@if ($isEdit)
    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-3">
        <div>
            <div class="text-sm font-bold">Status Batas Poligon GeoJSON</div>
            <div class="xs muted">Diisi melalui perintah Artisan <code>alir:impor-batas-wilayah</code>.</div>
        </div>
        @if (! empty($wilayah->batas_wilayah))
            <span class="tag teal">Terisi GeoJSON</span>
        @else
            <span class="tag amber">Belum (pakai radius cadangan)</span>
        @endif
    </div>
@endif
