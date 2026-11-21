@extends('layouts.public')

{{-- PBI-006: halaman "Laporan diterima" berisi kode laporan unik --}}
@section('title', 'Laporan diterima - ALIR')

@section('content')
    <header class="ld-hero relative overflow-hidden text-paper">
        <canvas id="radar" class="pointer-events-none absolute inset-0 size-full" aria-hidden="true"></canvas>
        <div class="relative z-[2] mx-auto max-w-[880px] px-(--gutter) pt-[128px] pb-[150px]">
            <span class="ld-check grid size-16 place-items-center rounded-full bg-teal text-navy"><i data-lucide="check" class="!size-8"></i></span>
            <h1 class="mt-6 text-[clamp(40px,6vw,76px)] leading-[.95] font-extrabold tracking-[-.045em] animate-[alirUp_.7s_.1s_both]">Laporan diterima.</h1>
            <p class="mt-4 max-w-[560px] text-[17px] leading-relaxed text-s-300 animate-[alirUp_.7s_.2s_both]">
                Terima kasih telah melapor. Simpan kode di bawah sebagai nomor referensi saat menghubungi Tim Startup ALIR.
            </p>

            <div class="mt-8 flex flex-wrap items-center gap-3 animate-[alirUp_.7s_.3s_both]">
                <span class="rounded-2xl bg-paper px-5 py-3 font-mono text-[clamp(28px,5vw,44px)] font-extrabold tracking-[.08em] text-navy" dusk="kode-laporan">{{ $laporan->kode_pelacakan }}</span>
                <button type="button" class="ld-pill ld-pill--line !gap-3 !py-3.5" data-copy="{{ $laporan->kode_pelacakan }}"><i data-lucide="copy"></i>Salin kode</button>
            </div>
        </div>
        <div data-hero-water="#F5F7FA" data-h="120px" aria-hidden="true"></div>
    </header>

    <section class="mx-auto -mt-2 max-w-[880px] px-(--gutter)">
        <div class="rounded-[28px] bg-white p-6 shadow-[0_1px_2px_rgba(11,30,51,.08)] sm:p-8" data-reveal>
            <dl class="grid gap-5 sm:grid-cols-3">
                <div>
                    <dt class="text-xs font-bold text-s-500">Status</dt>
                    <dd class="mt-1"><span class="inline-flex items-center gap-1.5 rounded-full bg-amber-tint px-3 py-1 text-sm font-extrabold text-amber-ink" dusk="status-laporan"><i class="size-2 rounded-full bg-amber"></i>{{ $statusLabel }}</span></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-s-500">Kelurahan</dt>
                    <dd class="mt-1 font-bold">{{ $laporan->wilayah?->nama_kelurahan ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-s-500">Dikirim</dt>
                    <dd class="mt-1 font-bold">{{ $laporan->created_at?->translatedFormat('d M Y, H:i') }}</dd>
                </div>
            </dl>

            <div class="mt-6 flex gap-3 rounded-2xl bg-teal-tint p-4 text-sm text-teal-ink">
                <i data-lucide="message-circle" class="mt-0.5 flex-none"></i>
                <p>Tim Startup ALIR akan memverifikasi laporan Anda dan mengabarkan hasil serta tim relawan yang ditugaskan lewat WhatsApp. Jika kondisi memburuk atau ada korban jiwa, segera hubungi <a href="tel:112" class="font-extrabold underline">112</a>.</p>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('landing') }}" class="ld-btn-dark">Kembali ke beranda<i data-lucide="arrow-right" class="arr"></i></a>
                <a href="{{ route('landing', ['lapor' => 1]) }}" class="btn btn-ghost">Laporkan lokasi lain</a>
            </div>
        </div>
    </section>
@endsection
