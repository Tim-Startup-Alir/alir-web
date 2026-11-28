@extends('layouts.public')

{{-- Donasi: FE saja (mockup-alir/donasi.html). Katalog & donatur dirender server-side dari $wishlist/$donatur,
     filter dan modal "Isi donasi" ada di resources/js/publik/donasi.js. --}}
@section('title', 'Donasi & Transparansi - ALIR')
@section('description', 'Donasikan barang yang benar-benar dibutuhkan posko banjir Kota Pekalongan.')

@push('head')
    @vite('resources/js/publik/donasi.js')
@endpush

@php
    $rp = fn ($n) => 'Rp'.number_format($n, 0, ',', '.');
    $warnaUrgensi = ['Kritis' => 'red', 'Mendesak' => 'amber', 'Normal' => 'teal'];
    $ikonKategori = ['Pangan' => 'wheat', 'Sandang' => 'shirt', 'Papan' => 'tent'];
    $kategori = collect($wishlist)->pluck('kategori')->unique()->values();
    $avatar = [['#DBEAFE', '#1D4ED8'], ['#FFEDE6', '#9A2E10'], ['#CCF3EE', '#0E6B62'], ['#FFF3D6', '#8A5A00'], ['#F1F5F9', '#475569']];
    $langkah = [['Pilih barang', 'Katalog kebutuhan posko'], ['Isi donasi', 'Jumlah & tampilan nama'], ['Bayar', 'QRIS · Virtual Account · Gerai']];
@endphp

@section('content')
<div class="pg-donasi">
    <header class="dn-hero">
        <div class="dn-floats" aria-hidden="true">
            <span class="fl f1"><i data-lucide="package-open"></i></span>
            <span class="fl f2"><i data-lucide="heart-handshake"></i></span>
            <span class="fl f3"><i data-lucide="wheat"></i></span>
        </div>
        <div class="dn-in">
            <div class="dn-copy" style="animation:alirUp .7s both">
                <span class="kicker" style="color:#FFB27A">WISHLIST DONASI</span>
                <h1 class="dn-h1">Donasikan barang yang benar-benar dibutuhkan.</h1>
                <div class="flow" id="flow">
                    @foreach ($langkah as $i => [$judul, $ket])
                        <div class="fs {{ $i === 0 ? 'on' : '' }}" style="opacity:{{ $i === 0 ? 1 : .55 }}">
                            <div class="b"><i></i></div>
                            <div class="n"><span>0{{ $i + 1 }}</span><b>{{ $judul }}</b></div>
                            <small>{{ $ket }}</small>
                        </div>
                    @endforeach
                </div>
            </div>
            <aside class="dn-stat glass-d" style="animation:alirUp .7s .2s both">
                <span class="sl">DANA DONASI</span>
                <div class="sv"><b data-count="{{ $statistik['dana_masuk_jt'] }}" data-dec="1" data-pre="Rp" data-suf=" jt">0</b><small>masuk</small></div>
                <div class="bar" style="background:rgba(248,250,252,.14);margin:12px 0 8px"><i style="width:{{ $statistik['persen'] }}%;background:linear-gradient(90deg,#FFB27A,#FF6A3D)"></i></div>
                <div class="row between small" style="color:#CBD5E1"><span>Dialokasikan Rp{{ number_format($statistik['dialokasikan_jt'], 1, ',', '.') }} jt</span><b style="color:#FF8A63">{{ $statistik['persen'] }}%</b></div>
                <div class="sr">
                    <span><b data-count="{{ $statistik['donatur'] }}">0</b>donatur</span>
                    <span><b data-count="{{ $statistik['relawan'] }}">0</b>relawan</span>
                    <span><b data-count="{{ $statistik['kebutuhan'] }}">0</b>kebutuhan</span>
                </div>
            </aside>
        </div>
        <div data-hero-water="#F5F7FA" data-h="120px" aria-hidden="true"></div>
    </header>

    <div class="dn-main" data-reveal>
        <div style="flex:3 1 640px;min-width:0">
            <div class="cat-h">
                <h2 style="font-size:24px;margin:0">Katalog kebutuhan posko</h2>
                <a class="lk" href="{{ route('landing') }}#transparansi"><i data-lucide="shield-check" style="color:var(--teal)"></i>Lihat jejak penyaluran</a>
            </div>
            <div class="row between wrapx" style="gap:12px;margin-bottom:20px">
                <div class="chips" id="cats">
                    <button type="button" class="chip on" data-c="Semua">Semua</button>
                    @foreach ($kategori as $k)
                        <button type="button" class="chip" data-c="{{ $k }}">{{ $k }}</button>
                    @endforeach
                </div>
                <div class="seg" id="urg">
                    <button type="button" class="on" data-u="all">Semua</button>
                    <button type="button" data-u="Kritis">Kritis</button>
                    <button type="button" data-u="Mendesak">Mendesak</button>
                    <button type="button" data-u="Normal">Normal</button>
                </div>
            </div>

            <div class="cat-g" id="cat">
                @foreach ($wishlist as $i => $w)
                    @php
                        $persen = (int) round($w['terkumpul'] / $w['target'] * 100);
                        $penuh = $w['terkumpul'] >= $w['target'];
                        $warna = ($penuh || $persen >= 75) ? 'var(--teal-ink)' : ($w['urgensi'] === 'Kritis' ? 'var(--red-ink)' : 'var(--coral-dark)');
                    @endphp
                    <article class="dc" id="c-{{ $w['id'] }}" data-kategori="{{ $w['kategori'] }}" data-urgensi="{{ $w['urgensi'] }}" style="animation-delay:{{ $i * .07 }}s">
                        <div class="ph">
                            <img class="gs" src="{{ asset('img/publik/'.$w['foto']) }}" alt="{{ $w['nama'] }}" loading="lazy">
                            <span class="tag {{ $warnaUrgensi[$w['urgensi']] ?? '' }}">{{ $w['urgensi'] }}</span>
                        </div>
                        <div class="bd">
                            <div class="row between" style="align-items:flex-start"><h3>{{ $w['nama'] }}</h3><i data-lucide="{{ $ikonKategori[$w['kategori']] ?? 'package' }}" style="color:var(--s-600)"></i></div>
                            <span class="small muted">{{ $w['oleh'] }}</span>
                            <b class="small">{{ $rp($w['harga']) }} / {{ $w['satuan'] }}</b>
                            <div class="row between" style="align-items:flex-end;margin-top:14px">
                                <span class="pct" style="color:{{ $warna }}">{{ $persen }}%</span>
                                <span class="xs muted money" style="text-align:right">{{ $rp($w['harga'] * $w['terkumpul']) }}<br>dari {{ $rp($w['harga'] * $w['target']) }}</span>
                            </div>
                            <div class="bar {{ $w['urgensi'] === 'Kritis' ? 'coral' : '' }}" style="margin-top:8px"><i style="width:{{ min(100, $persen) }}%"></i></div>
                            <div class="row between xs muted" style="margin:8px 0 16px"><span>{{ $w['terkumpul'] }} / {{ $w['target'] }} {{ $w['satuan'] }}</span><span>{{ max(1, (int) round($w['terkumpul'] / 3)) }} donatur</span></div>
                        </div>
                        <button type="button" class="btn-d" data-pick="{{ $w['id'] }}" @disabled($penuh)>{{ $penuh ? 'Kebutuhan terpenuhi' : 'Donasi barang ini' }}<i data-lucide="arrow-right" class="arr"></i></button>
                    </article>
                @endforeach
                <div class="empty hide" id="cat-kosong" style="grid-column:1/-1">Tidak ada barang untuk filter ini.</div>
            </div>
        </div>

        <aside class="dn-side">
            <div class="side-h"><h3 style="font-size:20px;margin:0">Donatur terbaru</h3><span class="live"><i></i>LIVE</span></div>
            <div id="donors">
                @foreach ($donatur as $d)
                    @php
                        $c = $d['anonim'] ? $avatar[4] : $avatar[ord($d['nama'][0]) % 4];
                        $inisial = $d['anonim'] ? '?' : collect(explode(' ', $d['nama']))->map(fn ($x) => mb_substr($x, 0, 1))->take(2)->join('');
                    @endphp
                    <div class="dr">
                        <span class="av" style="background:{{ $c[0] }};color:{{ $c[1] }}">{{ mb_strtoupper($inisial) }}</span>
                        <span style="min-width:0"><b>{{ $d['nama'] }}</b><span class="it">{{ $d['jumlah'] }}x {{ $d['barang'] }}</span></span>
                        <span class="am"><b>{{ $rp($d['nominal']) }}</b><small>{{ \Carbon\Carbon::parse($d['waktu'])->locale('id')->diffForHumans() }}</small></span>
                    </div>
                @endforeach
            </div>
            <p class="xs muted" style="margin-top:14px">Donatur dapat memilih tampil sebagai Anonim.</p>
        </aside>
    </div>
</div>
@endsection

@push('data')
    {{-- data katalog untuk modal "Isi donasi" (JS) --}}
    <script type="application/json" id="donasi-config">{!! json_encode(['wishlist' => collect($wishlist)->map(fn ($w) => $w + ['foto_url' => asset('img/publik/'.$w['foto'])])->all()], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush
