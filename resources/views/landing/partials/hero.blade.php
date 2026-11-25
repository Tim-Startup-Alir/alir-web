{{-- HERO Beranda (F-02 Pelaporan Banjir): radar, ticker info lapor, CTA, cara melapor 3 langkah, air naik --}}
@php
    $kelurahan = $kelurahan ?? collect();
    $daftarKel = $kelurahan->isNotEmpty() ? $kelurahan->join(', ', ' dan ') : 'wilayah cakupan ALIR';
    $ticker = [
        'Lapor banjir tanpa login, cukup 3 langkah',
        'Cakupan: Kel. '.$daftarKel,
        'Tandai lokasi dengan pin peta atau GPS',
        'Setiap laporan mendapat kode unik ALR-XXXXXX',
        'Status laporan dikabarkan lewat WhatsApp',
        'Darurat jiwa? Hubungi 112',
    ];
    $langkah = [
        ['map-pin', 'Tandai lokasi', 'Pin di peta atau GPS perangkat, plus alamat singkat.'],
        ['camera', 'Ceritakan kondisi', 'Tingkat keparahan, perkiraan warga terdampak, dan foto.'],
        ['message-circle', 'Kirim & simpan kode', 'Isi WhatsApp, selesaikan CAPTCHA, terima kode ALR.'],
    ];
@endphp
<header id="top" class="ld-hero relative overflow-hidden text-paper">
    <canvas id="radar" class="pointer-events-none absolute inset-0 size-full" aria-hidden="true"></canvas>

    <div class="relative z-[2] mx-auto max-w-[1240px] px-(--gutter) pt-[118px]">
        <div class="flex max-w-[760px] animate-[alirUp_.6s_both] items-center gap-3 overflow-hidden rounded-full border border-coral/30 bg-coral/12 p-1.5">
            <span class="flex flex-none items-center gap-2 rounded-full bg-coral px-3 py-1.5 text-[11px] font-extrabold tracking-[.1em] text-navy">
                <i class="live-dot size-[7px] rounded-full bg-navy"></i>LAPOR
            </span>
            <div class="flex-1 overflow-hidden" aria-label="Info pelaporan">
                <div class="ld-ticker flex w-max gap-12 text-[13px] font-semibold whitespace-nowrap text-[#FFD3C2]">
                    @foreach (array_merge($ticker, $ticker) as $t)
                        <span @if ($loop->index >= count($ticker)) aria-hidden="true" @endif>{{ $t }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-11 grid grid-cols-[repeat(auto-fit,minmax(min(100%,440px),1fr))] items-start gap-12 pb-[230px]">
            <div class="animate-[alirUp_.7s_.1s_both]">
                <h1 class="text-[clamp(52px,8vw,120px)] leading-[.9] font-extrabold tracking-[-.05em]">Air naik.<br><span class="text-coral">Bantuan bergerak.</span></h1>
                <p class="mt-6 mb-8 max-w-[540px] text-[clamp(16px,1.5vw,20px)] leading-[1.55] text-s-300">
                    Laporkan genangan dan banjir rob di Kelurahan {{ $daftarKel }}, Kota Pekalongan. Laporan Anda diverifikasi Tim Startup ALIR lalu diteruskan ke komunitas relawan terdekat.
                </p>
                <div class="flex flex-wrap gap-2.5">
                    <button type="button" class="ld-pill ld-pill--coral" data-open-lapor dusk="hero-lapor">Lapor Banjir<i data-lucide="arrow-right" class="arr"></i></button>
                </div>
                <p class="mt-5 flex items-center gap-2 text-[13px] font-semibold text-s-400">
                    <i data-lucide="shield-check" class="text-teal"></i>Tanpa login &middot; maksimal 3 langkah &middot; kabar status via WhatsApp
                </p>
                <noscript><p class="mt-4 rounded-2xl bg-coral/15 p-3 text-sm text-[#FFD3C2]">Form laporan membutuhkan JavaScript. Aktifkan JavaScript atau hubungi 112 bila darurat.</p></noscript>
            </div>

            <div class="ld-glass mt-[clamp(0px,2vw,24px)] animate-[alirUp_.7s_.2s_both] overflow-hidden">
                <div class="flex items-center justify-between border-b border-paper/10 px-[22px] py-4">
                    <span class="text-[11px] font-extrabold tracking-[.12em] text-s-400">CARA MELAPOR</span>
                    <span class="flex items-center gap-1.5 text-[11px] font-bold text-teal"><i data-lucide="timer" class="!size-3.5"></i>&plusmn; 60 detik</span>
                </div>
                <ol class="pb-2">
                    @foreach ($langkah as [$ikon, $judul, $teks])
                        <li class="ld-fi !cursor-default !grid-cols-[36px_1fr_auto]">
                            <span class="grid size-9 place-items-center rounded-full bg-coral/15 text-coral-hover"><i data-lucide="{{ $ikon }}" class="!size-4"></i></span>
                            <span><b class="block text-[15px]">{{ $judul }}</b><span class="text-xs text-s-400">{{ $teks }}</span></span>
                            <span class="text-xl font-extrabold text-s-500">0{{ $loop->iteration }}</span>
                        </li>
                    @endforeach
                </ol>
                <button type="button" data-open-lapor class="flex w-full items-center justify-between gap-3 border-t border-paper/10 px-[22px] py-4 text-left text-sm font-bold text-coral-hover transition hover:bg-paper/5">
                    Mulai dari langkah 1<i data-lucide="arrow-right"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- air naik 2,8 dtk sampai 190px (mountHeroWater) --}}
    <div data-hero-water="#F5F7FA" data-h="190px" aria-hidden="true"></div>
</header>
