@extends('layouts.public')

{{-- Hasil pelacakan laporan: status terkini + linimasa tahap (tanpa data pribadi) --}}
@section('title', 'Laporan '.$lacak['kode'].' - ALIR')

@section('content')
    @php
        $badge = [
            'teal' => 'bg-teal-tint text-teal-ink',
            'amber' => 'bg-amber-tint text-amber-ink',
            'red' => 'bg-alert-tint text-alert-ink',
            'navy' => 'bg-navy text-white',
        ][$lacak['nada']] ?? 'bg-s-100 text-navy';
    @endphp
    <header class="ld-hero relative overflow-hidden text-paper">
        <canvas id="radar" class="pointer-events-none absolute inset-0 size-full" aria-hidden="true"></canvas>
        <div class="relative z-[2] mx-auto max-w-[880px] px-(--gutter) pt-[128px] pb-[150px]">
            <p class="text-[13px] font-extrabold tracking-[.12em] text-coral-hover">KODE LAPORAN</p>
            <div class="mt-3 flex flex-wrap items-center gap-3 animate-[alirUp_.7s_.1s_both]">
                <span class="rounded-2xl bg-paper px-5 py-3 font-mono text-[clamp(26px,5vw,44px)] font-extrabold tracking-[.08em] text-navy" dusk="lacak-kode-hasil">{{ $lacak['kode'] }}</span>
                <button type="button" class="ld-pill ld-pill--line !gap-3 !py-3.5" data-copy="{{ $lacak['kode'] }}"><i data-lucide="copy"></i>Salin kode</button>
            </div>
            <p class="mt-5 max-w-[560px] text-[17px] leading-relaxed text-s-300 animate-[alirUp_.7s_.2s_both]">{{ $lacak['pesan'] }}</p>
        </div>
        <div data-hero-water="#F5F7FA" data-h="120px" aria-hidden="true"></div>
    </header>

    <section class="mx-auto -mt-2 max-w-[880px] px-(--gutter)">
        <div class="rounded-[28px] bg-white p-6 shadow-[0_1px_2px_rgba(11,30,51,.08)] sm:p-8" data-reveal>
            <dl class="grid gap-5 sm:grid-cols-3">
                <div>
                    <dt class="text-xs font-bold text-s-500">Status</dt>
                    <dd class="mt-1"><span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-extrabold {{ $badge }}" dusk="lacak-status">{{ $lacak['label'] }}</span></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-s-500">Kelurahan</dt>
                    <dd class="mt-1 font-bold">{{ $lacak['kelurahan'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-s-500">Dikirim</dt>
                    <dd class="mt-1 font-bold">{{ $lacak['dikirim']?->translatedFormat('d M Y, H:i') }}</dd>
                </div>
            </dl>

            <h2 class="mt-8 mb-4 text-lg font-extrabold">Perjalanan laporan</h2>
            <ol class="lk-tl" dusk="lacak-linimasa">
                @foreach ($lacak['linimasa'] as $l)
                    <li class="lk-{{ $l['state'] }}" @if ($l['state'] === 'aktif') aria-current="step" @endif>
                        <span class="lk-dot">
                            @if ($l['state'] === 'selesai')<i data-lucide="check"></i>
                            @elseif ($l['state'] === 'gagal')<i data-lucide="x"></i>
                            @endif
                        </span>
                        <div>
                            <b>{{ $l['judul'] }}</b>
                            <p>{{ $l['keterangan'] }}</p>
                            @if ($l['waktu'])<time datetime="{{ $l['waktu']->toIso8601String() }}">{{ $l['waktu']->translatedFormat('d M Y, H:i') }}</time>@endif
                        </div>
                    </li>
                @endforeach
            </ol>

            <div class="mt-6 flex gap-3 rounded-2xl bg-teal-tint p-4 text-sm text-teal-ink">
                <i data-lucide="message-circle" class="mt-0.5 flex-none"></i>
                <p>Kabar terbaru juga dikirim lewat WhatsApp. Jika kondisi memburuk atau ada korban jiwa, segera hubungi <a href="tel:112" class="font-extrabold underline">112</a>.</p>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('landing') }}" class="ld-btn-dark">Kembali ke beranda<i data-lucide="arrow-right" class="arr"></i></a>
                <a href="{{ route('lacak.index') }}" class="btn btn-ghost">Cari kode lain</a>
            </div>
        </div>
    </section>
@endsection
