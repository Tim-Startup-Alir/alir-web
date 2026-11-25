<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal Komunitas') - ALIR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body data-panel>
@php
    $komunitasAkun = auth()->user()->komunitasRelawan;
    $namaKomunitas = $komunitasAkun?->nama ?? auth()->user()->name;
    $jumlahKonfirmasiDiminta = $komunitasAkun?->konfirmasiLapangan()->where('status', 'diminta')->count() ?? 0;
@endphp
<div class="side-backdrop" id="side-backdrop"></div>
<div class="shell">
    <aside class="side" id="side">
        <a class="brand" href="{{ route('komunitas.dashboard') }}" style="padding:0">
            <span class="logo"><i data-lucide="waves"></i></span>
            <span>ALIR</span>
        </a>
        <div class="role-c">
            <span class="av">{{ strtoupper(substr($namaKomunitas, 0, 2)) }}</span>
            <span>
                <b>{{ $namaKomunitas }}</b>
                <em><i data-lucide="shield-check"></i>Role: Komunitas Relawan</em>
            </span>
        </div>

        <a href="{{ route('komunitas.dashboard') }}" class="{{ request()->routeIs('komunitas.dashboard') ? 'on' : '' }}">
            <i data-lucide="house"></i>Beranda
        </a>
        @if (Route::has('komunitas.profil.edit'))
            <a href="{{ route('komunitas.profil.edit') }}" class="{{ request()->routeIs('komunitas.profil.*') ? 'on' : '' }}">
                <i data-lucide="map-pin"></i>Profil &amp; Posko
            </a>
        @endif
        @if (Route::has('komunitas.jadwal.index'))
            <a href="{{ route('komunitas.jadwal.index') }}" class="{{ request()->routeIs('komunitas.jadwal.*') ? 'on' : '' }}">
                <i data-lucide="calendar-clock"></i>Jadwal Siaga
            </a>
        @endif
        @if (Route::has('komunitas.penugasan.index'))
            <a href="{{ route('komunitas.penugasan.index') }}" class="{{ request()->routeIs('komunitas.penugasan.*') ? 'on' : '' }}">
                <i data-lucide="clipboard-check"></i>Penugasan
            </a>
        @endif
        {{-- F-03 Pemetaan Titik Banjir --}}
        @if (Route::has('komunitas.konfirmasi.index'))
            <a href="{{ route('komunitas.konfirmasi.index') }}" class="{{ request()->routeIs('komunitas.konfirmasi.*') ? 'on' : '' }}" aria-label="Konfirmasi Lapangan, {{ $jumlahKonfirmasiDiminta }} permintaan menunggu">
                <i data-lucide="map-pinned"></i>Konfirmasi Lapangan
                @if ($jumlahKonfirmasiDiminta > 0)<span class="tag amber" aria-label="{{ $jumlahKonfirmasiDiminta }} permintaan menunggu">{{ $jumlahKonfirmasiDiminta }}</span>@endif
            </a>
        @endif

        <div style="margin-top:auto;padding-top:20px">
            <a href="{{ url('/') }}">
                <i data-lucide="globe"></i>Lihat situs publik
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="side-link" dusk="logout-button">
                    <i data-lucide="log-out"></i>Keluar
                </button>
            </form>
        </div>
    </aside>

    <div class="main">
        <header class="top">
            <button type="button" class="btn btn-icon btn-ghost side-toggle" id="menu" aria-label="Menu">
                <i data-lucide="menu"></i>
            </button>
            <div>
                <b>{{ $namaKomunitas }}</b>
                <div class="xs muted">
                    Role: Komunitas Relawan
                    @if ($komunitasAkun)
                        &middot; {{ $komunitasAkun->total_anggota }} anggota
                    @endif
                </div>
            </div>
            <div class="row" style="margin-left:auto">
                <span class="tag teal hide-m">
                    <i class="ping-dot" style="background:var(--teal);width:8px;height:8px"></i>Aktif
                </span>
            </div>
        </header>

        <main class="content panel-anim">
            <x-flash-message />
            @yield('content')
        </main>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.js"></script>
<script src="https://unpkg.com/lucide@0.469.0/dist/umd/lucide.min.js"></script>
@stack('scripts')
</body>
</html>
