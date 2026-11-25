@extends('layouts.public')

{{-- Halaman publik milik fitur lain (Cuaca / Donasi / Edukasi): sengaja dikosongkan untuk diisi pemilik PBI-nya. --}}
@section('title', $judul.' - ALIR')

@section('content')
    <header class="ld-hero relative overflow-hidden text-paper">
        <div class="relative z-[2] mx-auto max-w-[1240px] px-(--gutter) pt-[128px] pb-[120px]">
            <span class="text-[11px] font-extrabold tracking-[.14em] text-coral-hover animate-[alirUp_.6s_both]">{{ $kicker }}</span>
            <h1 class="mt-3 text-[clamp(40px,6vw,84px)] leading-[.95] font-extrabold tracking-[-.045em] animate-[alirUp_.7s_.1s_both]">{{ $judul }}</h1>
        </div>
        <div data-hero-water="#F5F7FA" data-h="80px" aria-hidden="true"></div>
    </header>

    <section class="mx-auto mt-6 max-w-[1240px] px-(--gutter)">
        <div class="grid place-items-center gap-3 rounded-[28px] border-2 border-dashed border-s-200 bg-white px-6 py-20 text-center" data-reveal dusk="placeholder-kosong">
            <span class="grid size-14 place-items-center rounded-full bg-s-100 text-s-500"><i data-lucide="{{ $ikon }}"></i></span>
            <b class="text-lg">Halaman ini belum tersedia</b>
            <p class="max-w-[420px] text-sm text-s-500">Konten {{ strtolower($judul) }} sedang disiapkan. Sementara itu, Anda tetap dapat melaporkan banjir.</p>
            <button type="button" class="ld-btn-dark mt-2" data-open-lapor>Lapor Banjir<i data-lucide="arrow-right" class="arr"></i></button>
        </div>
    </section>
@endsection
