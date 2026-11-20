@extends('layouts.komunitas')

@section('title', 'Beranda Komunitas')

@section('content')
    <div class="pg-h">
        <div>
            <span class="kicker">PORTAL KOMUNITAS</span>
            <h2>Selamat bertugas</h2>
            <p class="muted">Ringkasan kesiapsiagaan untuk {{ $komunitas?->nama ?? auth()->user()->name }}.</p>
        </div>
    </div>

    <div class="grid-alir g3">
        <div class="stat hov">
            <span class="ic" style="background:var(--water-tint);color:var(--water-deep)"><i data-lucide="map-pin"></i></span>
            <b>{{ $komunitas?->wilayah?->nama_kelurahan ?? '-' }}</b>
            <span>Kelurahan layanan</span>
        </div>
        <div class="stat hov">
            <span class="ic" style="background:var(--coral-tint);color:var(--coral-dark)"><i data-lucide="users"></i></span>
            <b>{{ $komunitas?->kapasitas_max ?? 0 }} / {{ $komunitas?->total_anggota ?? 0 }}</b>
            <span>Kapasitas siap / total anggota</span>
        </div>
        <div class="stat hov">
            <span class="ic" style="background:var(--teal-tint);color:var(--teal-ink)"><i data-lucide="calendar-clock"></i></span>
            <b>{{ $totalJadwal }}</b>
            <span>Shift kesiapsiagaan terdaftar</span>
        </div>
    </div>

    <div class="grid-alir g2">
        <div class="card">
            <h3 class="mb-3">Posko Utama</h3>
            @if ($poskoUtama)
                <div class="space-y-1.5 text-sm">
                    <div class="font-bold text-base">{{ $poskoUtama->nama }}</div>
                    <div class="muted">{{ $poskoUtama->alamat ?: 'Alamat belum diisi' }}</div>
                    <div class="xs muted mono">
                        Koordinat: {{ $poskoUtama->latitude }}, {{ $poskoUtama->longitude }} &middot; Radius {{ $poskoUtama->radius_m }} m
                    </div>
                    @if ($poskoUtama->diLuarCakupan())
                        <span class="tag amber mt-2">Di luar 3 kelurahan pilot</span>
                    @else
                        <span class="tag teal mt-2">Di dalam wilayah cakupan</span>
                    @endif
                </div>
            @else
                <div class="empty">Belum ada posko utama terdaftar.</div>
            @endif
        </div>

        <div class="card">
            <h3 class="mb-3">Status Shift Saat Ini</h3>
            @if ($jadwalAktif)
                <div class="space-y-2 text-sm">
                    <span class="tag teal">Siaga: {{ $jadwalAktif->label }}</span>
                    <div class="font-bold">{{ ucfirst($jadwalAktif->hari) }}, {{ substr($jadwalAktif->jam_mulai, 0, 5) }} - {{ substr($jadwalAktif->jam_selesai, 0, 5) }}</div>
                    <div class="muted">PIC Shift: {{ $jadwalAktif->nama_pic }} ({{ $jadwalAktif->nomor_whatsapp_pic }})</div>
                </div>
            @else
                <div class="empty">Tidak ada shift aktif pada jam ini.</div>
            @endif
        </div>
    </div>
@endsection
