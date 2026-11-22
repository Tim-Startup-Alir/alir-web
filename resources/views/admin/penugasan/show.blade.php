@extends('layouts.admin')

@section('title', 'Detail Penugasan Relawan')
@section('topbar_title', 'Detail Penugasan')
@section('topbar_subtitle', 'Informasi penugasan dan riwayat progres')

@push('styles')
<style>
.assignment-detail{max-width:900px;display:grid;gap:14px}
.assignment-detail-header{display:flex;justify-content:space-between;align-items:center}
.assignment-detail-header h2{margin:0;font-size:24px}
.assignment-detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.assignment-detail-section{border:1px solid #e2e8ed;border-radius:12px;background:#fff;padding:16px}
.assignment-detail-section h3{margin:0 0 12px;font-size:14px}
.assignment-detail-list{display:grid;grid-template-columns:150px 1fr;gap:8px 12px;margin:0;font-size:12px}
.assignment-detail-list dt{color:#718195;font-weight:800}.assignment-detail-list dd{margin:0;color:#263d52}
.assignment-progress{display:flex;gap:6px;flex-wrap:wrap}
.assignment-audit-actions{display:flex;gap:7px}
.assignment-history{grid-column:1/-1}
.assignment-history-list{display:grid;gap:0}
.assignment-history-item{display:grid;grid-template-columns:150px minmax(0,1fr) auto;gap:10px;padding:10px 0;border-top:1px solid #edf1f4;font-size:12px}
.assignment-history-item:first-child{border-top:0}
.assignment-history-item strong{color:#2b4358}
.assignment-history-item time,.assignment-history-item span{color:#74869a}
@media(max-width:700px){.assignment-detail-grid{grid-template-columns:1fr}.assignment-detail-list{grid-template-columns:1fr}.assignment-detail-header{align-items:flex-start;gap:10px}}
@media(max-width:700px){.assignment-history{grid-column:auto}.assignment-history-item{grid-template-columns:1fr;gap:3px}}
</style>
@endpush

@section('content')
<div class="assignment-detail">
    <header class="assignment-detail-header">
        <div>
            <span class="kicker">PENUGASAN #{{ $penugasan->id }}</span>
            <h2>P-{{ $penugasan->laporanBanjir?->kode_pelacakan ?? '-' }}</h2>
        </div>
        <span class="tag {{ $penugasan->stage === 'ditolak' ? 'red' : 'navy' }}">{{ ucfirst(str_replace('_',' ', $penugasan->stage)) }}</span>
    </header>

    <div class="assignment-detail-grid">
        <section class="assignment-detail-section">
            <h3>Laporan dan lokasi</h3>
            <dl class="assignment-detail-list">
                <dt>Kelurahan</dt><dd>{{ $penugasan->laporanBanjir?->wilayah?->nama_kelurahan ?? '-' }}</dd>
                <dt>Lokasi</dt><dd>{{ $penugasan->laporanBanjir?->alamat_singkat ?: '-' }}</dd>
                <dt>Warga terdampak</dt><dd>{{ $penugasan->laporanBanjir?->jumlah_warga ?? '-' }}</dd>
                <dt>Keparahan</dt><dd>{{ ucfirst($penugasan->laporanBanjir?->severity ?? '-') }}</dd>
                <dt>Kepercayaan</dt><dd>{{ ucfirst($penugasan->laporanBanjir?->tingkat_kepercayaan ?? '-') }}</dd>
            </dl>
        </section>

        <section class="assignment-detail-section">
            <h3>Komunitas penerima</h3>
            <dl class="assignment-detail-list">
                <dt>Komunitas</dt><dd>{{ $penugasan->komunitasRelawan?->nama ?? '-' }}</dd>
                <dt>Posko</dt><dd>{{ $penugasan->komunitasRelawan?->poskoUtama()?->nama ?? '-' }}</dd>
                <dt>Wilayah</dt><dd>{{ $penugasan->komunitasRelawan?->wilayah?->nama_kelurahan ?? '-' }}</dd>
                <dt>Personel</dt><dd>{{ $penugasan->jumlah_personil_tugas }}</dd>
                <dt>Perahu</dt><dd>{{ $penugasan->armada_perahu_tugas }}</dd>
            </dl>
        </section>

        <section class="assignment-detail-section">
            <h3>Riwayat progres</h3>
            <dl class="assignment-detail-list">
                <dt>Ditugaskan</dt><dd>{{ $penugasan->created_at?->translatedFormat('d M Y, H:i') ?? '-' }}</dd>
                <dt>Diterima</dt><dd>{{ $penugasan->waktu_diterima?->translatedFormat('d M Y, H:i') ?? '-' }}</dd>
                <dt>Menuju lokasi</dt><dd>{{ $penugasan->waktu_menuju_lokasi?->translatedFormat('d M Y, H:i') ?? '-' }}</dd>
                <dt>Ditangani</dt><dd>{{ $penugasan->waktu_ditangani?->translatedFormat('d M Y, H:i') ?? '-' }}</dd>
                <dt>Selesai</dt><dd>{{ $penugasan->waktu_selesai?->translatedFormat('d M Y, H:i') ?? '-' }}</dd>
                <dt>Alasan ditolak</dt><dd>{{ $penugasan->alasan_tolak ?: '-' }}</dd>
            </dl>
        </section>

        <section class="assignment-detail-section">
            <h3>Catatan dan snapshot</h3>
            <dl class="assignment-detail-list">
                <dt>Catatan</dt><dd>{{ $penugasan->catatan ?: '-' }}</dd>
                <dt>Jarak</dt><dd>{{ $penugasan->jarak_km !== null ? number_format((float)$penugasan->jarak_km,3).' km' : '-' }}</dd>
                <dt>Skor</dt><dd>{{ $penugasan->skor_rekomendasi !== null ? number_format((float)$penugasan->skor_rekomendasi,2).'/100' : '-' }}</dd>
                <dt>Admin penugas</dt><dd>{{ $penugasan->penugas?->name ?? '-' }}</dd>
            </dl>
        </section>

        <section class="assignment-detail-section assignment-history">
            <h3>Riwayat perubahan</h3>
            @if ($riwayatAudit->isEmpty())
                <p class="xs muted">Belum ada catatan audit untuk penugasan ini.</p>
            @else
                <div class="assignment-history-list">
                    @foreach ($riwayatAudit as $aktivitas)
                        <div class="assignment-history-item">
                            <time>{{ $aktivitas->waktu?->translatedFormat('d M Y, H:i') ?? '-' }}</time>
                            <strong>{{ ucfirst(str_replace(['penugasan.','_'], ['', ' '], $aktivitas->aksi)) }}</strong>
                            <span>{{ $aktivitas->user?->name ?? 'Sistem' }}</span>
                            @if ($aktivitas->deskripsi)<p class="muted" style="grid-column:1/-1;margin:0">{{ $aktivitas->deskripsi }}</p>@endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <div class="assignment-audit-actions">
        <a href="{{ route('admin.penugasan.index',['status'=>'sudah_ditugaskan']) }}" class="btn btn-navy btn-sm">Kembali ke daftar</a>
    </div>
</div>
@endsection
