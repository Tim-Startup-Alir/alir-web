@extends('layouts.public')

{{-- Cuaca & Rob: FE saja (mockup-alir/cuaca.html). Angka di grafik/kalender disimulasikan di resources/js/publik/cuaca.js. --}}
@section('title', 'Cuaca & Rob - ALIR')
@section('description', 'Prakiraan cuaca, pasang surut, dan kalender risiko rob Kota Pekalongan.')

@push('head')
    @vite('resources/js/publik/cuaca.js')
@endpush

@section('content')
<div class="pg-cuaca">
    <header class="cw-hero">
        <canvas id="rain" class="cw-rain" aria-hidden="true"></canvas>
        <div class="cw-in">
            <div class="locs" id="locs" style="animation:alirUp .6s both">
                @foreach ($lokasi as $i => $l)
                    <button type="button" class="loc {{ $i === 0 ? 'on' : '' }}" data-i="{{ $i }}"><i data-lucide="map-pin"></i>{{ $l[0] }}</button>
                @endforeach
            </div>
            <div class="cw-grid">
                <div style="animation:alirUp .7s .1s both">
                    <div class="upd"><i></i>BMKG &middot; diperbarui <span id="upd">{{ $ringkasan['diperbarui'] }}</span> WIB</div>
                    <div class="tmp"><span class="deg"><span id="temp">27&deg;</span></span><i data-lucide="cloud-rain" class="bob big-ic"></i></div>
                    <div class="cond">{{ $ringkasan['kondisi'] }}</div>
                    <div class="hl" id="hl">{{ $ringkasan['kondisi'] }} &middot; Kelembapan {{ $ringkasan['kelembaban'] }}% &middot; Angin {{ $ringkasan['angin'] }} km/j</div>
                </div>
                <div class="rob glass-d" style="animation:alirUp .7s .2s both" id="robnow"></div>
            </div>
        </div>
        <div data-hero-water="#F5F7FA" data-h="130px" aria-hidden="true"></div>
    </header>

    <div class="cw-main">
        <section class="hour-c">
            <div class="row between" style="padding:0 16px 14px"><b>Per jam</b><span class="small muted">Suhu 24 jam ke depan</span></div>
            <div class="hourly" id="hourly"></div>
        </section>

        <section class="duo" data-reveal>
            <div class="d7"><b style="font-size:16px">Prakiraan 7 hari</b><div id="daily"></div></div>
            <div class="tiles" id="tiles"></div>
        </section>

        <section id="pasang" style="margin-top:100px">
            <div class="sec-top">
                <div><span class="kicker">PASANG SURUT &middot; BMKG MARITIM</span><h2 class="h2x">Kapan air laut naik?</h2></div>
                <div style="text-align:right"><div class="small muted" id="tday">Hari ini</div><div class="big-t" id="tv">0,00 m</div></div>
            </div>
            <div class="tc">
                <svg id="tide" viewBox="0 0 720 240" preserveAspectRatio="none" role="img" aria-label="Grafik pasang surut hari ini"></svg>
                <div class="row between xs" style="color:#94A3B8;margin-top:6px"><span>00.00</span><span>06.00</span><span>12.00</span><span>18.00</span><span>24.00</span></div>
                <input type="range" class="cw" id="tslide" min="0" max="95" step="1" value="48" style="margin-top:16px" aria-label="Geser waktu pada grafik pasang surut">
                <div class="row wrapx" style="gap:18px;margin-top:14px;font-size:13px;color:#CBD5E1">
                    <span class="row" style="gap:8px"><i style="width:18px;border-top:2px dashed #E5484D"></i>Batas rob 1,0 m (air masuk permukiman rendah)</span>
                    <span class="row" style="gap:6px"><i data-lucide="moon" style="width:14px;color:#FF8A63"></i>Purnama &amp; bulan baru: pasang lebih tinggi</span>
                </div>
            </div>
        </section>

        <section id="bulanan" style="margin-top:100px">
            <span class="kicker">PREDIKSI 30 HARI</span>
            <h2 class="h2x" style="margin-bottom:28px">Kalender risiko rob.</h2>
            <div class="cal-w">
                <div class="calc">
                    <div class="calh"><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span></div>
                    <div class="cal" id="cal"></div>
                    <div class="row wrapx xs" style="gap:16px;margin-top:14px;font-weight:700;color:var(--s-600)">
                        <span class="row" style="gap:6px"><i class="cal-sw" style="background:#F1F5F9;box-shadow:inset 0 0 0 1px #E2E8F0"></i>Rendah</span>
                        <span class="row" style="gap:6px"><i class="cal-sw" style="background:#FFD3C2"></i>Sedang</span>
                        <span class="row" style="gap:6px"><i class="cal-sw" style="background:#FF6A3D"></i>Tinggi</span>
                        <span class="row" style="gap:6px"><i data-lucide="moon" style="width:12px"></i>Purnama / bulan baru</span>
                    </div>
                </div>
                <div class="pick" id="dday"></div>
            </div>
        </section>

        <section style="margin-top:100px" data-reveal>
            <span class="kicker">PRAKIRAAN MUSIM</span>
            <h2 class="h2x" style="margin-bottom:28px">Tiga bulan ke depan.</h2>
            <div class="grid-s" id="season"></div>
            <p class="xs muted row" style="gap:8px;margin-top:20px"><i data-lucide="info" style="width:14px"></i>Prototipe: angka di halaman ini simulasi. Saat produksi diisi dari API prakiraan cuaca BMKG dan data pasang surut BMKG Maritim.</p>
        </section>
    </div>
</div>
@endsection

@push('data')
    <script type="application/json" id="cuaca-config">{!! json_encode(['lokasi' => $lokasi, 'ringkasan' => $ringkasan], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush
