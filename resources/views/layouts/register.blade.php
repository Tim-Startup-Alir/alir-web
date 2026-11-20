<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Daftar Komunitas Relawan') - ALIR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .reg-grid{display:grid;grid-template-columns:minmax(0,.82fr) minmax(0,1.18fr);min-height:100vh;min-height:100dvh}
        @media(max-width:960px){.reg-grid{grid-template-columns:1fr}.reg-side{display:none}}
        .reg-side{position:sticky;top:0;height:100vh;height:100dvh;overflow:hidden;color:#F5F7FA;padding:32px clamp(28px,4vw,64px);display:flex;flex-direction:column;background:radial-gradient(60% 55% at 85% 0%,rgba(255,106,61,.32),transparent 62%),radial-gradient(70% 60% at 0% 100%,rgba(86,156,235,.28),transparent 60%),linear-gradient(160deg,#0B1E33 0%,#10304F 100%)}
        .reg-feat{display:grid;gap:14px;margin:0;padding:0;list-style:none}
        .reg-feat li{display:flex;gap:12px;align-items:center;font-weight:600;font-size:15px}
        .reg-feat i{width:34px;height:34px;border-radius:12px;background:rgba(255,106,61,.16);color:#FF8A63;display:grid;place-items:center;flex:none}
        .reg-form{padding:32px clamp(16px,4vw,56px);display:flex;justify-content:center}
        .reg-card{width:min(660px,100%);animation:alirUp .45s both}
        .reg-sec{background:#fff;border:1px solid var(--s-200);border-radius:22px;padding:20px;margin-bottom:16px}
        .reg-sec h3{font-size:15px;display:flex;align-items:center;gap:10px;margin:0 0 16px}
        .reg-sec h3 b{width:26px;height:26px;border-radius:50%;background:var(--navy);color:#fff;display:grid;place-items:center;font-size:13px}
    </style>
    @stack('styles')
</head>
<body>
<div class="reg-grid">
    <aside class="reg-side">
        <a class="lg-top" href="{{ url('/') }}">
            <span class="logo"><i data-lucide="waves"></i></span>
            <span>ALIR</span>
        </a>
        <div class="lg-mid" style="max-width:460px">
            <h1>Gabung jadi <span>relawan</span> tanggap banjir.</h1>
            <p style="margin-bottom:26px">Daftarkan komunitas atau tim Anda agar bisa menerima penugasan, mengelola logistik, dan membantu warga Kota Pekalongan.</p>
            <ul class="reg-feat">
                <li><i data-lucide="life-buoy"></i>Terima penugasan evakuasi terdekat</li>
                <li><i data-lucide="package"></i>Ajukan logistik dan kelola inventaris</li>
                <li><i data-lucide="shield-check"></i>Verifikasi email &amp; peninjauan Tim Startup ALIR</li>
            </ul>
        </div>
    </aside>

    <main class="reg-form">
        <div class="reg-card">
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
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@stack('scripts')
</body>
</html>
