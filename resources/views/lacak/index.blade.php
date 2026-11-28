@extends('layouts.public')

{{-- Form pelacakan laporan (fallback tanpa JS, dan tujuan kode yang tidak ditemukan) --}}
@section('title', 'Lacak laporan - ALIR')

@section('content')
    @php($pesan = $galat ?? $errors->first('kode'))
    <header class="ld-hero relative overflow-hidden text-paper">
        <canvas id="radar" class="pointer-events-none absolute inset-0 size-full" aria-hidden="true"></canvas>
        <div class="relative z-[2] mx-auto max-w-[880px] px-(--gutter) pt-[128px] pb-[150px]">
            <span class="grid size-16 place-items-center rounded-full bg-coral text-navy"><i data-lucide="search" class="!size-8"></i></span>
            <h1 class="mt-6 text-[clamp(40px,6vw,76px)] leading-[.95] font-extrabold tracking-[-.045em] animate-[alirUp_.7s_.1s_both]">Lacak laporan.</h1>
            <p class="mt-4 max-w-[560px] text-[17px] leading-relaxed text-s-300 animate-[alirUp_.7s_.2s_both]">
                Masukkan kode laporan yang Anda terima setelah melapor untuk melihat sudah sampai mana penanganannya.
            </p>
        </div>
        <div data-hero-water="#F5F7FA" data-h="120px" aria-hidden="true"></div>
    </header>

    <section class="mx-auto -mt-2 max-w-[640px] px-(--gutter)">
        <form method="POST" action="{{ route('lacak.cari') }}" class="rounded-[28px] bg-white p-6 shadow-[0_1px_2px_rgba(11,30,51,.08)] sm:p-8" data-reveal novalidate>
            @csrf
            <label for="lk-kode" class="lp-lbl">Kode laporan</label>
            <div class="lp-field {{ $pesan ? 'bad' : '' }}">
                <span><i data-lucide="ticket" class="text-coral-dark"></i></span>
                <input id="lk-kode" name="kode" value="{{ old('kode', $kode ?? '') }}" maxlength="20" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="ALR-7K3M9P" class="font-mono tracking-wider uppercase" dusk="lacak-kode" required>
            </div>
            @if ($pesan)
                <p class="lp-ferr" role="alert" dusk="lacak-error">{{ $pesan }}</p>
            @else
                <p class="lp-hint">Kode ada di halaman "Laporan diterima" dan berawalan ALR-.</p>
            @endif
            <button type="submit" class="lp-next mt-5 w-full sm:w-auto" dusk="lacak-submit">Cari laporan<i data-lucide="arrow-right"></i></button>
        </form>
    </section>
@endsection
