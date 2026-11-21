<footer class="relative mt-[140px] bg-navy text-s-400">
    <div class="pointer-events-none absolute -top-[59px] left-0 h-[60px] w-full overflow-hidden" aria-hidden="true">
        <svg viewBox="0 0 1440 60" preserveAspectRatio="none" class="block h-[60px] w-full max-w-none"><path d="M0 60 C 240 0 480 0 720 30 S 1200 60 1440 10 V 60 Z" fill="#0B1E33"/></svg>
    </div>
    <div class="relative mx-auto max-w-[1240px] px-(--gutter) pt-14 pb-7">
        <div class="flex flex-wrap justify-between gap-10">
            <div class="max-w-[420px] flex-[1_1_320px]">
                <a class="brand text-[22px] text-paper" href="{{ route('landing') }}"><span class="logo !size-[34px] text-lg"><i data-lucide="waves"></i></span>ALIR</a>
                <p class="mt-3.5 mb-[18px] text-sm leading-relaxed">Aplikasi Logistik Informasi dan Relawan Banjir. Laporan warga, relawan, logistik, dan donasi transparan untuk Kelurahan Tirto, Pasirkratonkramat, dan Degayu, Kota Pekalongan.</p>
                <div class="flex flex-wrap gap-2">
                    <span class="flex items-center gap-2 rounded-full bg-[#3F7E44] px-3.5 py-2 text-xs font-extrabold whitespace-nowrap text-white"><i data-lucide="globe"></i>SDGs 13 &middot; Climate Action</span>
                    <span class="flex items-center gap-2 rounded-full bg-teal px-3.5 py-2 text-xs font-extrabold whitespace-nowrap text-navy"><i data-lucide="message-circle"></i>Kabar status via WhatsApp</span>
                </div>
            </div>
            <div class="flex flex-wrap gap-14">
                <div class="ld-foot-col flex flex-col gap-2.5 text-sm">
                    <span class="text-[11px] font-extrabold tracking-[.12em] text-s-500">MENU</span>
                    <a href="{{ route('landing') }}">Beranda</a>
                    <a href="{{ route('cuaca') }}">Cuaca</a>
                    <a href="{{ route('donasi') }}">Donasi</a>
                    <a href="{{ route('edukasi') }}">Edukasi</a>
                    <button type="button" class="text-left font-bold !text-coral-hover hover:!text-white" data-open-lapor>Lapor banjir</button>
                </div>
                <div class="ld-foot-col flex flex-col gap-2.5 text-sm">
                    <span class="text-[11px] font-extrabold tracking-[.12em] text-s-500">KOMUNITAS</span>
                    <a href="{{ route('register') }}">Daftar relawan</a>
                    <a href="{{ route('login') }}">Portal komunitas</a>
                    <a href="{{ route('login') }}">Panel Tim Startup ALIR</a>
                </div>
                <div class="ld-foot-col flex flex-col gap-2.5 text-sm">
                    <span class="text-[11px] font-extrabold tracking-[.12em] text-s-500">DARURAT</span>
                    <a class="font-extrabold !text-coral-hover" href="tel:112">112 &middot; Panggilan darurat</a>
                    <a href="tel:113">113 &middot; Damkar</a>
                    <a href="tel:119">119 &middot; Ambulans</a>
                    <a href="tel:123">123 &middot; PLN</a>
                </div>
            </div>
        </div>
        <div class="mt-10 flex flex-wrap justify-between gap-3 border-t border-s-400/20 pt-[18px] text-xs text-s-500">
            <span>&copy; {{ date('Y') }} ALIR &middot; Tim Startup ALIR &middot; Kota Pekalongan</span>
            <span>Pelaporan banjir untuk Kelurahan Tirto, Pasirkratonkramat, dan Degayu</span>
        </div>
    </div>
</footer>
