@extends('layouts.public')

@section('title', 'Galeri Aksi Relawan - ALIR')
@section('description', 'Foto dan video kegiatan relawan di lapangan, dikelompokkan per wilayah dan komunitas.')

@push('head')
    @vite('resources/js/publik/galeri.js')
@endpush

@section('content')
<div class="pg-f08">
    <header class="f8-head">
        <span class="kicker">GALERI AKSI RELAWAN</span>
        <h1>Aksi nyata di <span class="c">lapangan.</span></h1>
        <p>Dokumentasi kegiatan relawan yang sudah diperiksa Tim ALIR. Pilih wilayah untuk melihat aksi komunitas di sekitarmu.</p>
    </header>

    <div class="f8-wrap">
        @if ($wilayahList->isNotEmpty())
            <nav class="f8-bar chips" aria-label="Filter wilayah">
                <a class="chip {{ $wilayahId ? '' : 'on' }}" href="{{ route('galeri') }}" @if (! $wilayahId) aria-current="true" @endif>Semua wilayah</a>
                @foreach ($wilayahList as $w)
                    <a class="chip {{ $wilayahId === $w->id ? 'on' : '' }}" href="{{ route('galeri', ['wilayah' => $w->id]) }}" @if ($wilayahId === $w->id) aria-current="true" @endif>{{ $w->nama_kelurahan }}</a>
                @endforeach
            </nav>
        @endif

        @forelse ($galeri as $grup)
            <section class="gl-region" aria-labelledby="wil-{{ $loop->index }}">
                <h2 id="wil-{{ $loop->index }}"><i data-lucide="map-pin"></i>{{ $grup['wilayah'] }}</h2>

                @foreach ($grup['komunitas'] as $kom)
                    <div class="gl-com">
                        <h3><i data-lucide="users"></i>{{ $kom['nama'] }} <span class="tag">{{ $kom['items']->count() }} dokumentasi</span></h3>
                        <div class="gl-grid">
                            @foreach ($kom['items'] as $d)
                                @php
                                    $src = route('galeri.media', $d);
                                    $judul = $d->laporanBanjir?->alamat_singkat ?? 'Aksi relawan';
                                    $meta = $kom['nama'].' · '.$d->created_at->translatedFormat('d M Y').($d->distribusiBantuan ? ' · '.$d->distribusiBantuan->jumlah.' '.$d->distribusiBantuan->item : '');
                                @endphp
                                <button type="button" class="gl-item" data-gl-open
                                        data-src="{{ $src }}" data-jenis="{{ $d->jenis_media }}"
                                        data-judul="{{ $judul }}" data-meta="{{ $meta }}" data-catatan="{{ $d->keterangan }}"
                                        data-alt="{{ $judul }}"
                                        aria-label="Buka {{ $d->isVideo() ? 'video' : 'foto' }}: {{ $judul }}">
                                    @if ($d->isVideo())
                                        <video src="{{ $src }}" preload="metadata" muted playsinline data-poster-frame></video>
                                        <span class="gl-play" aria-hidden="true"><i data-lucide="play"></i></span>
                                    @else
                                        <img src="{{ $src }}" alt="" loading="lazy">
                                    @endif
                                    <span class="gl-cap">{{ \Illuminate\Support\Str::limit($d->keterangan ?: $judul, 70) }}<small>{{ $d->created_at->translatedFormat('d M Y') }}</small></span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </section>
        @empty
            <div class="empty" style="margin-top:40px">
                @if ($wilayahId)
                    Belum ada dokumentasi yang tampil untuk wilayah ini.
                @else
                    Belum ada dokumentasi aksi relawan yang tampil. Kembali lagi nanti ya.
                @endif
            </div>
        @endforelse

        @if ($halaman->hasPages())
            <nav class="gl-pager" aria-label="Halaman galeri">
                <p class="xs muted">Menampilkan {{ $halaman->firstItem() }}-{{ $halaman->lastItem() }} dari {{ $halaman->total() }} dokumentasi</p>
                {{ $halaman->links() }}
            </nav>
        @endif
    </div>
</div>

<dialog id="gl-dialog" class="gl-dialog" aria-label="Pratinjau dokumentasi">
    <div class="gl-box">
        <button type="button" class="btn btn-icon btn-ghost gl-x" data-gl-close aria-label="Tutup"><i data-lucide="x"></i></button>
        <div class="gl-stage"></div>
        <div class="gl-info">
            <b data-gl-judul></b>
            <div class="xs muted" data-gl-meta></div>
            <p data-gl-catatan></p>
        </div>
    </div>
</dialog>
@endsection
