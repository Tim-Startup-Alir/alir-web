@extends('layouts.public')

{{-- Edukasi: FE saja (mockup-alir/edukasi.html). Bagian interaktif (simulator, fase, tas siaga, mitos, fun facts, kuis)
     dirender oleh resources/js/publik/edukasi.js; artikel dirender server-side dari $artikel. --}}
@section('title', 'Edukasi Siaga Banjir - ALIR')
@section('description', 'Simulator ketinggian air, tas siaga, mitos atau fakta, dan kuis kesiapan banjir.')

@push('head')
    @vite('resources/js/publik/edukasi.js')
@endpush

@php
    $lompat = [['#sim', 'ruler', 'Simulator'], ['#fase', 'list-checks', 'Fase'], ['#bag', 'backpack', 'Tas siaga'], ['#mitos', 'scale', 'Mitos'], ['#fakta', 'lightbulb', 'Fun facts'], ['#kuis', 'award', 'Kuis']];
@endphp

@section('content')
<div class="pg-edukasi">
    <header class="ed-hero" data-mouse>
        <div class="orbs" id="orbs" aria-hidden="true"></div>
        <div class="hring r1"></div><div class="hring r2"></div>
        <div class="ed-in">
            <div class="eyebrow" style="animation:alirUp .7s both"><i></i>PUSAT EDUKASI SIAGA BANJIR</div>
            <h1 class="ed-h1" style="animation:alirUp .8s .1s both">Siap sebelum <span class="c">air</span> datang.</h1>
            <p class="ed-lead" style="animation:alirUp .8s .2s both">Coba simulator ketinggian air, siapkan tas siaga, tebak mitos atau fakta, dan uji kesiapanmu lewat kuis singkat.</p>
            <div class="jump" style="animation:alirUp .8s .3s both">
                @foreach ($lompat as [$href, $ikon, $label])
                    <a href="{{ $href }}"><i data-lucide="{{ $ikon }}"></i>{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <div class="lvl-card"><span class="lc"><i data-lucide="waves"></i></span><span><small>MUKA AIR &middot; PANJANG WETAN</small><b>78 cm <em>&#9650; naik</em></b></span></div>
        <div data-hero-water="#F5F7FA" data-h="150px" aria-hidden="true"></div>
    </header>

    {{-- 01 SIMULATOR --}}
    <section id="sim" class="es" data-reveal>
        <div class="sec-top"><div><span class="kicker">01 &middot; SIMULATOR KETINGGIAN AIR</span><h2 class="h2e">Seberapa dalam itu berbahaya?</h2></div><p class="muted" style="max-width:380px">Geser ketinggian air dan lihat apa yang terjadi di sekitar rumahmu.</p></div>
        <div class="sim-g">
            <div class="gwrap">
                <div class="ticks" id="ticks" aria-hidden="true"></div>
                <div class="gauge-t" id="gt">
                    <div id="gmarks"></div>
                    <div class="gwv" id="gw"><svg viewBox="0 0 2880 100" preserveAspectRatio="none"><path d="M0 50 Q 360 0 720 50 T 1440 50 T 2160 50 T 2880 50 V 100 H 0 Z" fill="#2A4560"/></svg></div>
                    <div class="person" id="pers"><i></i><b></b></div>
                </div>
            </div>
            <div class="sim-r">
                <div class="row wrapx" style="align-items:flex-end;gap:18px"><span class="lvn"><span id="lv">0</span><small> cm</small></span><span class="lvb" id="lvb"></span></div>
                <div class="lvm" id="lvm" aria-live="polite"></div>
                <input class="edu" id="depth" type="range" min="0" max="150" step="1" value="0" aria-label="Ketinggian air (cm)">
                <div class="chips" id="presets"></div>
                <div class="mks" id="mks"></div>
            </div>
        </div>
    </section>

    {{-- 02 FASE --}}
    <section id="fase" class="es" data-reveal>
        <span class="kicker">02 &middot; SEBELUM &middot; SAAT &middot; SETELAH</span>
        <h2 class="h2e" style="margin:14px 0 32px;max-width:820px">Tiga fase, tiga cara bertindak.</h2>
        <div class="ptabs" id="ptabs"><span class="ind" id="pind"></span></div>
        <div class="pbody" id="pbody"></div>
    </section>

    <section id="artikel" class="es" data-reveal>
        <span class="kicker">ARTIKEL EDUKASI</span>
        <h2 class="h2e" style="margin:14px 0 32px;max-width:820px">Terbaru dari tim ALIR.</h2>
        <div id="arts" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px">
            @forelse ($artikel as $a)
                <article style="background:#fff;border-radius:24px;padding:22px;box-shadow:0 1px 2px rgba(11,30,51,.06);display:flex;flex-direction:column;gap:10px">
                    <span style="align-self:flex-start;padding:4px 12px;border-radius:99px;background:#CCF3EE;color:#0E6B62;font-size:12px;font-weight:800">{{ $a['kategori'] }}</span>
                    <h3 style="font-size:20px;letter-spacing:-.02em">{{ $a['judul'] }}</h3>
                    <small style="color:var(--s-500)">Diperbarui {{ $a['diperbarui'] }}</small>
                </article>
            @empty
                <p>Belum ada artikel terbit.</p>
            @endforelse
        </div>
    </section>

    {{-- 03 TAS --}}
    <section id="bag" class="es" data-reveal>
        <span class="kicker">03 &middot; TAS SIAGA BENCANA</span>
        <h2 class="h2e" style="margin:14px 0 40px;max-width:820px">Isi tasmu, satu per satu.</h2>
        <div class="bag-g">
            <div class="bring">
                <svg viewBox="0 0 120 120" aria-hidden="true"><circle cx="60" cy="60" r="52" fill="none" stroke="#E2E8F0" stroke-width="12"/><circle id="bc" cx="60" cy="60" r="52" fill="none" stroke="#FF6A3D" stroke-width="12" stroke-linecap="round" stroke-dasharray="326.7" stroke-dashoffset="326.7"/></svg>
                <div class="bc-in"><i data-lucide="backpack" id="bic"></i><b id="bpct">0%</b><small id="bdone">0/12 barang siap</small></div>
            </div>
            <div style="flex:1 1 480px;min-width:0">
                <div class="bchips" id="bchips"></div>
                <div class="row wrapx" style="gap:16px;margin-top:24px"><button type="button" class="ld-btn-dark" id="ball">Tandai semua</button><button type="button" class="link-b" id="breset">Ulangi</button><span class="bmsg" id="bmsg" aria-live="polite"></span></div>
            </div>
        </div>
    </section>

    {{-- 04 MITOS --}}
    <section id="mitos" class="es" data-reveal>
        <div class="sec-top"><div><span class="kicker">04 &middot; MITOS ATAU FAKTA?</span><h2 class="h2e">Tebak, lalu buktikan.</h2></div>
            <div class="row" style="gap:6px"><span class="row" id="mdots" style="gap:6px"></span><b id="mscore" style="margin-left:12px">Skor 0/6</b></div></div>
        <div id="mgame"></div>
    </section>

    {{-- 05 FUN FACTS --}}
    <section id="fakta" class="ff" data-reveal>
        <div class="ff-in">
            <div class="sec-top" style="margin-bottom:48px"><div><span class="kicker" style="color:#FF8A63">05 &middot; FUN FACTS ROB PEKALONGAN</span><h2 class="h2e">Tahukah kamu?</h2></div>
                <div class="row" style="gap:8px"><button type="button" class="ffb" id="fprev" aria-label="Sebelumnya"><i data-lucide="arrow-left"></i></button><button type="button" class="ffb c" id="fnext" aria-label="Berikutnya"><i data-lucide="arrow-right"></i></button></div></div>
            <div class="ffbars" id="fbars"></div>
            <div id="fbody"></div>
        </div>
    </section>

    {{-- 06 KUIS --}}
    <section id="kuis" class="es" style="padding-top:100px" data-reveal>
        <div class="sec-top"><div><span class="kicker">06 &middot; KUIS SIAGA</span><h2 class="h2e">Seberapa siap kamu?</h2></div><b id="qprog">Soal 1 dari 5</b></div>
        <div class="qbar"><i id="qbar"></i></div>
        <div id="qbox"></div>
    </section>
</div>
@endsection
