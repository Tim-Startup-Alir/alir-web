@extends('layouts.public')

@section('title', 'ALIR - Lapor banjir, bantuan bergerak')

@php
    $kelurahan = \App\Models\Wilayah::aktif()->orderBy('nama_kelurahan')->pluck('nama_kelurahan');
    // alur status setelah laporan dikirim (PBI-006: laporan baru selalu "Menunggu Verifikasi")
    $alur = [
        ['inbox', 'Menunggu Verifikasi', 'Laporan tersimpan dan Anda menerima kode ALR-XXXXXX.', 'bg-amber-tint text-amber-ink'],
        ['shield-check', 'Terverifikasi', 'Tim Startup ALIR memeriksa lokasi, foto, dan laporan sekitar.', 'bg-water-tint text-water-deep'],
        ['users', 'Ditugaskan', 'Komunitas relawan terdekat diminta menangani lokasi.', 'bg-coral-tint text-coral-dark'],
        ['circle-check', 'Selesai', 'Penanganan selesai, kabar dikirim lewat WhatsApp.', 'bg-teal-tint text-teal-ink'],
    ];
@endphp

@section('content')
    @include('landing.partials.hero', ['kelurahan' => $kelurahan])

    {{-- setelah melapor: kode laporan & alur status --}}
    <section class="mx-auto mt-10 max-w-[1240px] px-(--gutter)" aria-labelledby="alur-judul">
        <div class="flex flex-wrap items-end justify-between gap-6" data-reveal>
            <div class="max-w-[640px]">
                <span class="kicker">SETELAH MELAPOR</span>
                <h2 id="alur-judul" class="mt-2 text-[clamp(32px,4vw,56px)] leading-[.95] tracking-[-.04em]">Simpan kodenya,<br>kami kabari prosesnya.</h2>
            </div>
            <p class="max-w-[420px] text-[15px] text-s-600">Sebutkan kode laporan saat menghubungi Tim Startup ALIR agar laporan Anda langsung ditemukan. Nomor WhatsApp tidak pernah ditampilkan ke publik.</p>
        </div>

        <ol class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($alur as [$ikon, $judul, $teks, $warna])
                <li class="ld-card-lift rounded-[28px] bg-white p-6 shadow-[0_1px_2px_rgba(11,30,51,.08)]" data-reveal style="--i: {{ $loop->index }}">
                    <div class="flex items-center justify-between">
                        <span class="grid size-12 place-items-center rounded-full {{ $warna }}"><i data-lucide="{{ $ikon }}"></i></span>
                        <span class="text-sm font-extrabold text-s-400">0{{ $loop->iteration }}</span>
                    </div>
                    <b class="mt-5 block text-lg">{{ $judul }}</b>
                    <p class="mt-1.5 text-sm text-s-600">{{ $teks }}</p>
                    @if ($loop->first)
                        <span class="mt-4 inline-block rounded-xl bg-navy px-3 py-1.5 font-mono text-sm font-extrabold tracking-[.06em] text-coral">ALR-7K2M9P</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </section>

    @include('landing.partials.cta')
@endsection
