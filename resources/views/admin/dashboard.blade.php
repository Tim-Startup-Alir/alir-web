@extends('layouts.admin')

@section('title', 'Ringkasan Admin')
@section('topbar_title', 'Ringkasan Operasional')
@section('topbar_subtitle', 'Panel kendali Tim Startup ALIR')

@section('content')
    <div class="pg-h">
        <div>
            <span class="kicker">UTAMA</span>
            <h2>Ringkasan</h2>
            <p class="muted">Status wilayah cakupan, komunitas relawan, dan kesiapsiagaan Kota Pekalongan.</p>
        </div>
    </div>

    <div class="kpi-g">
        <div class="stat hov">
            <span class="ic" style="background:var(--water-tint);color:var(--water-deep)"><i data-lucide="map"></i></span>
            <b>{{ $wilayahAktif }} / {{ $totalWilayah }}</b>
            <span>Wilayah aktif</span>
        </div>
        <div class="stat hov">
            <span class="ic" style="background:var(--coral-tint);color:var(--coral-dark)"><i data-lucide="users"></i></span>
            <b>{{ $totalKomunitas }}</b>
            <span>Komunitas disetujui</span>
        </div>
        <a href="{{ route('admin.komunitas.index') }}" class="stat hov">
            <span class="ic" style="background:var(--amber-tint);color:var(--amber-ink)"><i data-lucide="user-check"></i></span>
            <b>{{ $antreanPendaftaran }}</b>
            <span>Pendaftaran menunggu</span>
        </a>
        <div class="stat hov">
            <span class="ic" style="background:var(--teal-tint);color:var(--teal-ink)"><i data-lucide="shield-check"></i></span>
            <b>{{ $komunitasSiaga }}</b>
            <span>Komunitas siaga sekarang</span>
        </div>
        <div class="stat hov">
            <span class="ic" style="background:var(--amber-tint);color:var(--amber-ink)"><i data-lucide="calendar-clock"></i></span>
            <b>{{ $totalJadwal }}</b>
            <span>Total shift kesiapsiagaan</span>
        </div>
    </div>

    <div class="card">
        <div class="row between mb-3">
            <h3>Jejak Audit Terbaru</h3>
            <span class="tag">{{ $auditTerbaru->count() }} entri</span>
        </div>
        @if ($auditTerbaru->isEmpty())
            <div class="empty">Belum ada catatan aktivitas.</div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($auditTerbaru as $log)
                    <div class="py-2.5 flex flex-wrap items-center justify-between gap-2 text-sm">
                        <div>
                            <span class="tag navy mr-2">{{ $log->aksi }}</span>
                            <span>{{ $log->deskripsi ?? '-' }}</span>
                        </div>
                        <div class="xs muted">
                            {{ $log->user?->name ?? 'Sistem' }} &middot; {{ $log->waktu?->diffForHumans() }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
