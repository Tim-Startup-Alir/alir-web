@extends('layouts.admin')

@section('title', 'Moderasi Dokumentasi')
@section('topbar_title', 'Moderasi Dokumentasi')
@section('topbar_subtitle', 'Setujui atau tolak dokumentasi relawan sebelum tampil di galeri publik')

@section('content')
    <div class="pg-h">
        <div>
            <span class="kicker">F-08 DOKUMENTASI</span>
            <h2>Moderasi Dokumentasi</h2>
            <p class="muted">Periksa foto atau video dan catatan, lalu setujui bila layak tampil. Penolakan wajib disertai alasan.</p>
        </div>
    </div>

    <nav class="dk-tabs" aria-label="Filter status moderasi">
        @foreach (\App\Models\Dokumentasi::LABEL_STATUS as $kode => $label)
            <a href="{{ route('admin.dokumentasi.index', ['status' => $kode]) }}" class="{{ $status === $kode ? 'on' : '' }}" @if ($status === $kode) aria-current="page" @endif>
                {{ $label }} <b>{{ $hitung[$kode] ?? 0 }}</b>
            </a>
        @endforeach
    </nav>

    @if ($daftar->isEmpty())
        <div class="card" style="margin-top:20px">
            <div class="empty">{{ $status === 'menunggu' ? 'Tidak ada dokumentasi yang menunggu moderasi.' : 'Tidak ada dokumentasi dengan status ini.' }}</div>
        </div>
    @else
        <div class="dk-grid">
            @foreach ($daftar as $d)
                <article class="dk-card" dusk="moderasi-{{ $d->id }}">
                    <div class="dk-media">
                        @if ($d->isVideo())
                            <video src="{{ route('admin.dokumentasi.media', $d) }}" controls preload="metadata" data-poster-frame></video>
                        @else
                            <img src="{{ route('admin.dokumentasi.media', $d) }}" alt="Dokumentasi dari {{ $d->komunitasRelawan?->nama }}" loading="lazy">
                        @endif
                        <span class="dk-kind"><i data-lucide="{{ $d->isVideo() ? 'video' : 'image' }}"></i>{{ $d->isVideo() ? 'Video' : 'Foto' }}</span>
                    </div>
                    <div class="dk-body">
                        <div class="dk-meta">
                            <span class="tag water">{{ $d->komunitasRelawan?->nama }}</span>
                            @if ($d->komunitasRelawan?->wilayah)<span class="tag">{{ $d->komunitasRelawan->wilayah->nama_kelurahan }}</span>@endif
                            <span class="tag">{{ $d->created_at->translatedFormat('d M Y H:i') }}</span>
                        </div>
                        @if ($d->keterangan)<p class="dk-note">{{ $d->keterangan }}</p>@else<p class="dk-note muted">Tanpa catatan.</p>@endif
                        <div class="dk-link">Laporan: <b class="mono">{{ $d->laporanBanjir?->kode_pelacakan ?? '-' }}</b>
                            @if ($d->laporanBanjir) · {{ $d->laporanBanjir->alamat_singkat }}@endif
                            @if ($d->distribusiBantuan)<br>Logistik: <b>{{ $d->distribusiBantuan->jumlah }} {{ $d->distribusiBantuan->item }}</b>@endif
                        </div>

                        @if ($d->status_moderasi === 'ditolak')
                            <div class="dk-reject" role="note"><b>Alasan penolakan</b>{{ $d->alasan_tolak }}</div>
                        @elseif ($d->status_moderasi === 'disetujui')
                            <span class="tag teal" style="align-self:flex-start"><i data-lucide="circle-check"></i>Tampil di galeri publik</span>
                        @else
                            @php
                                $barisIni = (int) old('_dok') === $d->id;
                                $galatAlasan = $barisIni ? $errors->first('alasan_tolak') : '';
                            @endphp
                            <div class="dk-actions">
                                <form method="POST" action="{{ route('admin.dokumentasi.setujui', $d) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-teal btn-sm" dusk="setujui-{{ $d->id }}"><i data-lucide="check"></i>Setujui</button>
                                </form>
                                <details class="dk-tolak" {{ $galatAlasan !== '' ? 'open' : '' }}>
                                    <summary class="btn btn-ghost btn-sm" dusk="tolak-{{ $d->id }}"><i data-lucide="x"></i>Tolak</summary>
                                    <form method="POST" action="{{ route('admin.dokumentasi.tolak', $d) }}">
                                        @csrf
                                        <input type="hidden" name="_dok" value="{{ $d->id }}">
                                        <label class="lbl" for="alasan-{{ $d->id }}">Alasan penolakan</label>
                                        <textarea id="alasan-{{ $d->id }}" name="alasan_tolak" rows="3" maxlength="500" required class="input" placeholder="Mis. foto buram atau tidak sesuai laporan.">{{ $barisIni ? old('alasan_tolak') : '' }}</textarea>
                                        @if ($galatAlasan !== '')
                                            <div class="err-msg">{{ $galatAlasan }}</div>
                                        @endif
                                        <button type="submit" class="btn btn-red btn-sm" dusk="kirim-tolak-{{ $d->id }}">Kirim penolakan</button>
                                    </form>
                                </details>
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-4">{{ $daftar->links() }}</div>
    @endif
@endsection

@push('scripts')
    @include('partials.video-poster')
@endpush
