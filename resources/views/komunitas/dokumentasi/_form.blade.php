{{-- Isi form dokumentasi, dipakai halaman unggah (create) dan perbaikan (edit). $dokumentasi terisi saat perbaikan. --}}
@php
    $perbaikan = isset($dokumentasi);
    $laporanTerpilih = (string) old('laporan_banjir_id', $dokumentasi->laporan_banjir_id ?? '');
    $logistikTerpilih = (string) old('distribusi_bantuan_id', $dokumentasi->distribusi_bantuan_id ?? '');
    $catatan = old('keterangan', $dokumentasi->keterangan ?? '');
@endphp

<div class="field">
    <label for="berkas">{{ $perbaikan ? 'Ganti foto atau video (opsional)' : 'Foto atau video' }} @unless ($perbaikan)<span class="text-red-600">*</span>@endunless</label>
    <div class="dk-drop {{ $errors->has('berkas') ? 'err' : '' }}" id="drop">
        <i data-lucide="image-up"></i>
        <input id="berkas" name="berkas" type="file" accept=".jpg,.jpeg,.png,.mp4,.webm,image/jpeg,image/png,video/mp4,video/webm" @unless ($perbaikan) required @endunless aria-describedby="berkas-hint">
        <div class="dk-hint" id="berkas-hint">Foto JPG atau PNG maks {{ $fotoMb }} MB. Video MP4 atau WebM maks {{ $videoMb }} MB.@if ($perbaikan) Kosongkan bila berkas lama tetap dipakai.@endif</div>
        <div class="dk-preview" id="preview" hidden></div>
        <div class="err-msg" id="berkas-err" role="alert" @if (! $errors->has('berkas')) hidden @endif>{{ $errors->first('berkas') }}</div>
    </div>
    @if ($perbaikan)
        <div class="dk-hint" style="margin-top:8px">Berkas saat ini:</div>
        <div class="dk-preview" style="margin-top:6px">
            @if ($dokumentasi->isVideo())
                <video src="{{ route('komunitas.dokumentasi.media', $dokumentasi) }}" controls preload="metadata" data-poster-frame></video>
            @else
                <img src="{{ route('komunitas.dokumentasi.media', $dokumentasi) }}" alt="Berkas dokumentasi saat ini">
            @endif
        </div>
    @endif
</div>

<div class="grid-alir g2">
    <div class="field">
        <label for="laporan_banjir_id">Laporan terkait <span class="text-red-600">*</span></label>
        <select id="laporan_banjir_id" name="laporan_banjir_id" class="input {{ $errors->has('laporan_banjir_id') ? 'err' : '' }}" required>
            <option value="">-- Pilih laporan --</option>
            @foreach ($laporanList as $l)
                <option value="{{ $l->id }}" @selected($laporanTerpilih === (string) $l->id)>{{ $l->kode_pelacakan }} · {{ $l->alamat_singkat }}@if ($l->wilayah) ({{ $l->wilayah->nama_kelurahan }})@endif</option>
            @endforeach
        </select>
        @error('laporan_banjir_id')<div class="err-msg">{{ $message }}</div>@enderror
    </div>

    <div class="field">
        <label for="distribusi_bantuan_id">Item logistik tersalur (opsional)</label>
        <select id="distribusi_bantuan_id" name="distribusi_bantuan_id" class="input {{ $errors->has('distribusi_bantuan_id') ? 'err' : '' }}">
            <option value="">-- Tidak ditautkan --</option>
            @foreach ($logistikList as $g)
                <option value="{{ $g->id }}" @selected($logistikTerpilih === (string) $g->id)>{{ $g->jumlah }} {{ $g->item }} · {{ $g->tanggal->translatedFormat('d M Y') }}</option>
            @endforeach
        </select>
        @if ($logistikList->isEmpty())<div class="dk-hint">Belum ada distribusi logistik tercatat untuk komunitas Anda.</div>@endif
        @error('distribusi_bantuan_id')<div class="err-msg">{{ $message }}</div>@enderror
    </div>
</div>

<div class="field">
    <label for="keterangan">Catatan kegiatan</label>
    <textarea id="keterangan" name="keterangan" rows="4" maxlength="1000" class="input {{ $errors->has('keterangan') ? 'err' : '' }}" placeholder="Ceritakan singkat apa yang dilakukan, di mana, dan siapa yang terbantu.">{{ $catatan }}</textarea>
    <div class="dk-count"><span id="hitung">{{ mb_strlen($catatan) }}</span>/1000</div>
    @error('keterangan')<div class="err-msg">{{ $message }}</div>@enderror
</div>

@push('scripts')
<script>
(() => {
    const input = document.getElementById('berkas');
    if (!input) return;
    const preview = document.getElementById('preview');
    const err = document.getElementById('berkas-err');
    const drop = document.getElementById('drop');
    const MB = 1024 * 1024;
    const batas = { foto: {{ $fotoMb }} * MB, video: {{ $videoMb }} * MB };
    let url = null;

    const salah = (pesan) => { err.textContent = pesan; err.hidden = !pesan; drop.classList.toggle('err', !!pesan); };

    input.addEventListener('change', () => {
        if (url) URL.revokeObjectURL(url);
        preview.replaceChildren(); preview.hidden = true; salah('');
        const f = input.files[0];
        if (!f) return;
        const video = /^video\/(mp4|webm)$/.test(f.type);
        const foto = /^image\/(jpeg|png)$/.test(f.type);
        if (!video && !foto) { input.value = ''; salah('Format berkas harus JPG, PNG (foto) atau MP4, WebM (video).'); return; }
        const maks = video ? batas.video : batas.foto;
        if (f.size > maks) { input.value = ''; salah('Ukuran berkas terlalu besar. Maksimal ' + Math.round(maks / MB) + ' MB untuk ' + (video ? 'video' : 'foto') + '.'); return; }
        url = URL.createObjectURL(f);
        const el = document.createElement(video ? 'video' : 'img');
        el.src = url;
        if (video) { el.controls = true; el.muted = true; el.preload = 'metadata'; } else { el.alt = 'Pratinjau foto'; }
        preview.appendChild(el); preview.hidden = false;
    });

    ['dragenter', 'dragover'].forEach((e) => drop.addEventListener(e, () => drop.classList.add('drag')));
    ['dragleave', 'drop'].forEach((e) => drop.addEventListener(e, () => drop.classList.remove('drag')));

    const ta = document.getElementById('keterangan'), n = document.getElementById('hitung');
    ta?.addEventListener('input', () => { n.textContent = ta.value.length; });
})();
</script>
@include('partials.video-poster')
@endpush
