@php
    $k = $konten ?? null;
    $tipeAwal = old('tipe', $k?->tipe ?? 'artikel');
@endphp

<div class="field">
    <label for="judul">Judul <span class="text-red-600">*</span></label>
    <input id="judul" name="judul" type="text" maxlength="200" required class="input {{ $errors->has('judul') ? 'err' : '' }}" value="{{ old('judul', $k?->judul) }}">
    @error('judul')<div class="err-msg">{{ $message }}</div>@enderror
</div>

<div class="grid-alir g3">
    <div class="field">
        <label for="kategori">Kategori <span class="text-red-600">*</span></label>
        <select id="kategori" name="kategori" required class="input {{ $errors->has('kategori') ? 'err' : '' }}">
            <option value="">-- Pilih kategori --</option>
            @foreach (\App\Models\KontenEdukasi::LABEL_KATEGORI as $v => $label)
                <option value="{{ $v }}" @selected(old('kategori', $k?->kategori) === $v)>{{ $label }}</option>
            @endforeach
        </select>
        @error('kategori')<div class="err-msg">{{ $message }}</div>@enderror
    </div>
    <div class="field">
        <label for="tipe">Tipe konten <span class="text-red-600">*</span></label>
        <select id="tipe" name="tipe" required class="input {{ $errors->has('tipe') ? 'err' : '' }}">
            @foreach (\App\Models\KontenEdukasi::LABEL_TIPE as $v => $label)
                <option value="{{ $v }}" @selected($tipeAwal === $v)>{{ $label }}</option>
            @endforeach
        </select>
        @error('tipe')<div class="err-msg">{{ $message }}</div>@enderror
    </div>
    <div class="field">
        <label for="status">Status <span class="text-red-600">*</span></label>
        <select id="status" name="status" required class="input {{ $errors->has('status') ? 'err' : '' }}">
            <option value="draft" @selected(old('status', $k?->status ?? 'draft') === 'draft')>Draft (belum tampil)</option>
            <option value="terbit" @selected(old('status', $k?->status) === 'terbit')>Terbit (tampil di publik)</option>
        </select>
        @error('status')<div class="err-msg">{{ $message }}</div>@enderror
    </div>
</div>

<div class="field" id="grup-media" hidden>
    <label for="media_url" id="lbl-media">Tautan media</label>
    <input id="media_url" name="media_url" type="url" maxlength="500" class="input {{ $errors->has('media_url') ? 'err' : '' }}" value="{{ old('media_url', $k?->media_url) }}" placeholder="https://">
    <div class="dk-hint" id="hint-media"></div>
    @error('media_url')<div class="err-msg">{{ $message }}</div>@enderror
</div>

<div class="field">
    <label for="isi" id="lbl-isi">Isi konten</label>
    <textarea id="isi" name="isi" rows="12" maxlength="20000" class="input {{ $errors->has('isi') ? 'err' : '' }}">{{ old('isi', $k?->isi) }}</textarea>
    <div class="dk-hint">Teks biasa. Pisahkan paragraf dengan baris kosong.</div>
    @error('isi')<div class="err-msg">{{ $message }}</div>@enderror
</div>

@push('scripts')
<script>
(() => {
    const tipe = document.getElementById('tipe');
    const grup = document.getElementById('grup-media');
    const media = document.getElementById('media_url');
    const hint = document.getElementById('hint-media');
    const lbl = document.getElementById('lbl-media');
    const lblIsi = document.getElementById('lbl-isi');
    const atur = () => {
        const t = tipe.value;
        grup.hidden = t === 'artikel';
        media.required = t !== 'artikel';
        lbl.textContent = t === 'video_sematan' ? 'Tautan video (YouTube atau Vimeo)' : 'Tautan gambar infografis (https)';
        hint.textContent = t === 'video_sematan' ? 'Contoh: https://www.youtube.com/watch?v=xxxxxxxxxxx' : 'Alamat gambar yang dapat diakses publik.';
        lblIsi.textContent = t === 'artikel' ? 'Isi artikel' : 'Deskripsi singkat (opsional)';
    };
    tipe.addEventListener('change', atur);
    atur();
})();
</script>
@endpush
