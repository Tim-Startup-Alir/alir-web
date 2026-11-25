<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Masuk') - ALIR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
<div class="lg">
    <aside class="lg-side">
        <a class="lg-top" href="{{ url('/') }}">
            <span class="logo"><i data-lucide="waves"></i></span>
            <span>ALIR</span>
        </a>
        <div class="lg-mid">
            <h1>Satu portal <span>tanggap banjir</span> Pekalongan.</h1>
            <p>Koordinasi wilayah, komunitas relawan, dan kesiapsiagaan penanganan banjir rob secara terpadu.</p>
            <div class="lg-stage" aria-hidden="true">
                <div class="orb-r r2" style="--t:40s;--rad:108px">
                    <span class="it" style="--a:0deg"><b><i data-lucide="map-pin"></i></b></span>
                    <span class="it" style="--a:120deg"><b><i data-lucide="life-buoy"></i></b></span>
                    <span class="it" style="--a:240deg"><b><i data-lucide="package"></i></b></span>
                </div>
                <div class="core">
                    <span class="pg"></span>
                    <span class="pg"></span>
                    <i data-lucide="waves"></i>
                </div>
            </div>
            <div class="lg-chips">
                <div class="lg-chip"><b>3</b><span>kelurahan pilot</span></div>
                <div class="lg-chip"><b>24/7</b><span>pantauan siaga</span></div>
                <div class="lg-chip"><b>SDGs 13</b><span>ketahanan iklim</span></div>
            </div>
        </div>
        <div data-hero-water="#F5F7FA" data-h="90px" style="z-index:1" aria-hidden="true"></div>
    </aside>

    <main class="lg-form">
        <div class="lg-card">
            <a class="lg-brand" href="{{ url('/') }}">
                <span class="logo"><i data-lucide="waves"></i></span>
                <span>ALIR</span>
            </a>
            @yield('content')
        </div>
    </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.js"></script>
<script src="https://unpkg.com/lucide@0.469.0/dist/umd/lucide.min.js"></script>
@stack('scripts')
</body>
</html>
