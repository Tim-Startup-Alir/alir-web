<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ALIR - Air naik, bantuan bergerak')</title>
    <meta name="description" content="@yield('description', 'ALIR: laporan banjir warga, relawan, logistik, dan donasi transparan untuk Kota Pekalongan.')">
    <meta name="theme-color" content="#0B1E33">
    {{-- tandai JS aktif sedini mungkin agar elemen [data-reveal] hanya disembunyikan bila JS berjalan --}}
    <script>document.documentElement.classList.add('js');</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/landing/index.js'])
    {{-- NFR-02 / TR-07: CAPTCHA Cloudflare Turnstile untuk modal Lapor Banjir (dirender eksplisit oleh lapor.js) --}}
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async defer></script>
    @stack('head')
</head>
<body class="bg-paper text-navy">
    @include('landing.partials.nav')

    <main>
        @yield('content')
    </main>

    @include('landing.partials.footer')

    {{-- konfigurasi modal Lapor Banjir (tersedia di semua halaman publik) --}}
    <script type="application/json" id="lapor-config">{!! json_encode(\App\Support\FormLaporConfig::data(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
    @stack('data')
    <script src="https://unpkg.com/lucide@0.469.0/dist/umd/lucide.min.js"></script>
</body>
</html>
