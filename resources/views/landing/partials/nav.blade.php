@php
    // Menu mengikuti mockup: setiap menu adalah halaman tersendiri
    $links = [['landing', 'Beranda'], ['cuaca', 'Cuaca'], ['donasi', 'Donasi'], ['galeri', 'Galeri'], ['edukasi', 'Edukasi']];
    $dashboard = null;
    if (auth()->check()) {
        $dashboard = match (auth()->user()->role) {
            'admin' => route('admin.dashboard'),
            'komunitas_relawan' => route('komunitas.dashboard'),
            default => null,
        };
    }
@endphp
<nav id="nav" aria-label="Navigasi utama"
     class="ld-nav fixed left-1/2 top-3.5 z-[1000] flex w-[min(1240px,calc(100%-24px))] -translate-x-1/2 items-center gap-2.5 rounded-full bg-navy/85 py-2 pr-2 pl-4 text-paper shadow-[0_10px_30px_rgba(6,18,31,.25)] backdrop-blur-[14px]">
    <a class="brand" href="{{ route('landing') }}" aria-label="ALIR - Beranda">
        <span class="logo"><i data-lucide="waves"></i></span>ALIR
    </a>

    <div class="flex min-w-0 flex-1 gap-0.5 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        @foreach ($links as [$route, $label])
            <a href="{{ route($route) }}" class="ld-navlink" @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </div>

    @if ($dashboard)
        <a href="{{ $dashboard }}" class="flex flex-none items-center gap-2 rounded-full bg-white/10 px-3.5 py-2.5 text-sm font-bold whitespace-nowrap text-s-200 transition hover:bg-white/20 hover:text-white max-sm:px-3">
            <i data-lucide="layout-dashboard"></i><span class="max-sm:hidden">Dashboard</span>
        </a>
    @else
        <a href="{{ route('login') }}" class="flex flex-none items-center gap-2 rounded-full bg-white/10 px-3.5 py-2.5 text-sm font-bold whitespace-nowrap text-s-200 transition hover:bg-white/20 hover:text-white max-sm:px-3">
            <i data-lucide="log-in"></i><span class="max-sm:hidden">Masuk</span>
        </a>
    @endif

    <a href="{{ route('lacak.index') }}" data-open-lacak dusk="nav-lacak" aria-label="Lacak Laporan"
       class="flex flex-none items-center gap-2 rounded-full bg-white/10 px-3.5 py-2.5 text-sm font-bold whitespace-nowrap text-s-200 transition hover:bg-white/20 hover:text-white max-sm:px-3">
        <i data-lucide="search"></i><span class="max-md:hidden">Lacak</span>
    </a>

    <button type="button" data-open-lapor dusk="nav-lapor"
            class="flex flex-none items-center gap-2 rounded-full bg-coral px-4 py-2.5 text-sm font-extrabold whitespace-nowrap text-navy transition duration-300 ease-alir hover:scale-105 hover:bg-coral-hover max-sm:px-3">
        <i data-lucide="siren"></i><span class="max-sm:hidden">Lapor Banjir</span>
    </button>
</nav>
