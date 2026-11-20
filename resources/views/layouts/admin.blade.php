<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel Admin') - ALIR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body data-panel>
<div class="side-backdrop" id="side-backdrop"></div>
<div class="shell">
    <aside class="side" id="side">
        <a class="brand" href="{{ route('admin.dashboard') }}" style="padding:0">
            <span class="logo"><i data-lucide="waves"></i></span>
            <span>ALIR</span>
        </a>
        <div class="role-c">
            <span class="av">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
            <span>
                <b>{{ auth()->user()->name }}</b>
                <em><i data-lucide="shield-check"></i>Role: Tim Startup ALIR</em>
            </span>
        </div>

        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'on' : '' }}">
            <i data-lucide="layout-dashboard"></i>Ringkasan
        </a>

        @php
            $dataMasterActive = request()->routeIs('admin.wilayah.*', 'admin.komunitas.*', 'admin.jadwal.*');
        @endphp
        <div class="ng {{ $dataMasterActive ? 'open has-on' : '' }}">
            <button type="button" class="ng-h" aria-expanded="{{ $dataMasterActive ? 'true' : 'false' }}">
                <i data-lucide="layers"></i>
                <span>Master &amp; Relawan</span>
                <i data-lucide="chevron-down" class="chev"></i>
            </button>
            <div class="ng-b">
                <div>
                    @if (Route::has('admin.wilayah.index'))
                        <a href="{{ route('admin.wilayah.index') }}" class="{{ request()->routeIs('admin.wilayah.*') ? 'on' : '' }}">
                            <i data-lucide="map"></i>Wilayah
                        </a>
                    @endif
                    @if (Route::has('admin.komunitas.index'))
                        @php
                            $jumlahAntreanKomunitas = \App\Models\KomunitasRelawan::siapDitinjau()->count();
                        @endphp
                        <a href="{{ route('admin.komunitas.index') }}" class="{{ request()->routeIs('admin.komunitas.*') ? 'on' : '' }}">
                            <i data-lucide="users"></i>Komunitas Relawan
                            @if ($jumlahAntreanKomunitas > 0)
                                <span class="badge">{{ $jumlahAntreanKomunitas }}</span>
                            @endif
                        </a>
                    @endif
                    @if (Route::has('admin.jadwal.index'))
                        <a href="{{ route('admin.jadwal.index') }}" class="{{ request()->routeIs('admin.jadwal.*') ? 'on' : '' }}">
                            <i data-lucide="calendar-clock"></i>Jadwal Siaga
                        </a>
                    @endif
                </div>
            </div>
        </div>

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
                <div class="font-bold text-sm">@yield('topbar_title', 'Panel Tim Startup ALIR')</div>
                <div class="xs muted">@yield('topbar_subtitle', 'Koordinasi penanganan banjir rob Kota Pekalongan')</div>
            </div>
            <div class="row" style="margin-left:auto">
                <span class="tag navy hide-m" style="height:34px;padding:0 14px">Admin</span>
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
