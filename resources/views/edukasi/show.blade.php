@extends('layouts.public')

@section('title', $konten->judul.' - Edukasi ALIR')
@section('description', \Illuminate\Support\Str::limit(strip_tags((string) $konten->isi) ?: 'Materi edukasi kesiapsiagaan banjir dari Tim ALIR.', 150))

@section('content')
@php
    $video = $konten->tipe === 'video_sematan' && $embed;
    $infografis = $konten->tipe === 'infografis' && filled($konten->media_url);
    $visual = $video || $infografis;
    $penyedia = $video ? (str_contains((string) $konten->media_url, 'vimeo') ? 'Vimeo' : 'YouTube') : null;
    $paragraf = filled($konten->isi) ? preg_split('/\R{2,}/', trim($konten->isi)) : [];
@endphp
<div class="pg-edukasi pg-f08">
    @if ($visual)
        <header class="kv-hero">
            <div class="kv-in">
                <a class="kv-back" href="{{ route('edukasi', ['kategori' => $konten->kategori]) }}#konten"><i data-lucide="arrow-left"></i>Kembali ke Edukasi</a>
                <div class="kv-meta">
                    <span class="kv-pill">{{ $konten->labelKategori() }}</span>
                    <span class="kv-type"><i data-lucide="{{ $video ? 'play-circle' : 'image' }}"></i>{{ $konten->labelTipe() }}</span>
                </div>
                <h1>{{ $konten->judul }}</h1>
            </div>
        </header>

        <div class="kv-stage-wrap">
            @if ($video)
                <div class="kv-stage">
                    <iframe src="{{ $embed }}" title="{{ $konten->judul }}" loading="lazy" allowfullscreen
                            referrerpolicy="strict-origin-when-cross-origin"
                            allow="accelerometer; encrypted-media; gyroscope; picture-in-picture"></iframe>
                </div>
            @else
                <figure class="kv-stage img">
                    <img src="{{ $konten->media_url }}" alt="Infografis: {{ $konten->judul }}" referrerpolicy="no-referrer">
                </figure>
            @endif
        </div>

        <div class="kv-cols">
            <div class="kv-desc">
                <h2>{{ $video ? 'Tentang video ini' : 'Tentang infografis ini' }}</h2>
                @forelse ($paragraf as $p)
                    <p>{!! nl2br(e($p)) !!}</p>
                @empty
                    <p class="kv-empty">Belum ada deskripsi untuk materi ini.</p>
                @endforelse
            </div>

            <aside class="kv-side" aria-label="Informasi materi">
                <dl>
                    <div><dt>Kategori</dt><dd>{{ $konten->labelKategori() }}</dd></div>
                    <div><dt>Jenis</dt><dd>{{ $konten->labelTipe() }}</dd></div>
                    <div><dt>Diterbitkan</dt><dd>{{ $konten->diterbitkan_pada?->translatedFormat('d F Y') }}</dd></div>
                </dl>
                <a class="kv-btn" href="{{ $konten->media_url }}" target="_blank" rel="noopener noreferrer">
                    <i data-lucide="{{ $video ? 'external-link' : 'maximize-2' }}"></i>{{ $video ? 'Tonton di '.$penyedia : 'Buka gambar ukuran penuh' }}
                </a>
                <a class="kv-btn ghost" href="{{ route('edukasi', ['kategori' => $konten->kategori]) }}#konten"><i data-lucide="layout-grid"></i>Materi lainnya</a>
            </aside>
        </div>
    @else
        <article class="kn-article">
            <a class="kn-back" href="{{ route('edukasi', ['kategori' => $konten->kategori]) }}#konten"><i data-lucide="arrow-left"></i>Kembali ke Edukasi</a>
            <div style="margin-top:22px"><span class="kn-pill">{{ $konten->labelKategori() }}</span></div>
            <h1>{{ $konten->judul }}</h1>
            <div class="kn-sub">{{ $konten->labelTipe() }} &middot; Diterbitkan {{ $konten->diterbitkan_pada?->translatedFormat('d F Y') }}</div>

            @if ($paragraf)
                <div class="kn-body">
                    @foreach ($paragraf as $p)
                        <p>{!! nl2br(e($p)) !!}</p>
                    @endforeach
                </div>
            @endif
        </article>
    @endif

    @if ($terkait->isNotEmpty())
        <section class="kn-rel" aria-labelledby="terkait">
            <h2 id="terkait" style="font-size:22px;letter-spacing:-.02em">Baca juga</h2>
            <div class="kn-grid">
                @foreach ($terkait as $k)
                    <article class="kn-card">
                        <div class="kn-in">
                            <span class="kn-pill">{{ $k->labelKategori() }}</span>
                            <h3><a class="kn-link" href="{{ route('edukasi.show', $k) }}">{{ $k->judul }}</a></h3>
                            <div class="kn-foot"><span>{{ $k->labelTipe() }}</span><span>{{ $k->diterbitkan_pada?->translatedFormat('d M Y') }}</span></div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
