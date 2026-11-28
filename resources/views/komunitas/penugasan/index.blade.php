@extends('layouts.komunitas')

@section('title', 'Penugasan')

@push('styles')
<style>
.community-assignments{display:grid;gap:14px}
.community-assignment-heading{display:flex;justify-content:space-between;align-items:flex-end;gap:18px}
.community-assignment-heading h2{margin:5px 0;font-size:28px}
.community-assignment-heading p{margin:0;color:#6b7b8d}
.community-assignment-count{flex:0 0 auto;color:#687b8f;font-size:13px;font-weight:700}
.community-assignment-list{display:grid;gap:8px}
.community-assignment-item{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px 18px;padding:14px 16px;border:1px solid #e1e8ed;border-left:3px solid #d5e2e8;border-radius:10px;background:#fff}
.community-assignment-item.is-active{border-left-color:#238b78}
.community-assignment-item.is-rejected{border-left-color:#be5552}
.community-assignment-main{min-width:0}
.community-assignment-title{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.community-assignment-title h3{margin:0;font-size:15px;color:#213a50}
.community-assignment-location{margin-top:4px;color:#4f6579;font-size:13px}
.community-assignment-meta{display:flex;gap:8px 16px;flex-wrap:wrap;margin-top:8px;color:#718195;font-size:12px}
.community-assignment-meta strong{color:#324a5f}
.community-assignment-note{margin-top:8px;color:#6b7b8d;font-size:12px}
.community-assignment-action{display:flex;align-items:center}
.community-assignment-empty{padding:28px;border:1px dashed #d8e2e8;border-radius:10px;background:#fff;text-align:center;color:#718195}
.community-pagination{margin-top:2px}
@media(max-width:680px){
 .community-assignment-heading{align-items:flex-start;flex-direction:column}
 .community-assignment-heading h2{font-size:24px}
 .community-assignment-item{grid-template-columns:1fr}
 .community-assignment-action{justify-content:flex-start}
}
</style>
@endpush

@section('content')
<div class="community-assignments">
    <header class="community-assignment-heading">
        <div>
            <span class="kicker">OPERASI LAPANGAN</span>
            <h2>Penugasan</h2>
            <p>Tinjau permintaan, lokasi, kebutuhan personel, dan perkembangan tugas komunitas.</p>
        </div>
        <span class="community-assignment-count">{{ $penugasan->total() }} penugasan</span>
    </header>

    @if ($penugasan->isEmpty())
        <div class="community-assignment-empty">Belum ada penugasan untuk komunitas Anda.</div>
    @else
        <div class="community-assignment-list">
            @foreach ($penugasan as $tugas)
                @php
                    $kodeLaporan = $tugas->laporanBanjir?->kode_pelacakan ?? str_pad((string)$tugas->laporan_banjir_id, 5, '0', STR_PAD_LEFT);
                    $stageClass = match ($tugas->stage) {
                        'menunggu' => 'amber',
                        'diterima', 'menuju_lokasi', 'ditangani' => 'teal',
                        'ditolak' => 'red',
                        default => 'navy',
                    };
                @endphp
                <article class="community-assignment-item {{ in_array($tugas->stage,['diterima','menuju_lokasi','ditangani'],true) ? 'is-active' : '' }} {{ $tugas->stage === 'ditolak' ? 'is-rejected' : '' }}" dusk="penugasan-{{ $tugas->id }}">
                    <div class="community-assignment-main">
                        <div class="community-assignment-title">
                            <h3>Penugasan #{{ $tugas->id }} · P-{{ $kodeLaporan }}</h3>
                            <span class="tag {{ $stageClass }}">{{ ucfirst(str_replace('_',' ', $tugas->stage)) }}</span>
                        </div>
                        <div class="community-assignment-location">
                            {{ $tugas->laporanBanjir?->alamat_singkat ?: 'Lokasi laporan' }}
                            · {{ $tugas->laporanBanjir?->wilayah?->nama_kelurahan ?? 'Wilayah tidak diketahui' }}
                        </div>
                        <div class="community-assignment-meta">
                            <span>Laporan {{ $tugas->laporanBanjir?->created_at?->translatedFormat('d M Y, H:i') ?? '-' }}</span>
                            <span><strong>{{ $tugas->laporanBanjir?->jumlah_warga ?? '-' }}</strong> warga terdampak</span>
                            <span><strong>{{ $tugas->jumlah_personil_tugas }}</strong> personel · {{ $tugas->armada_perahu_tugas }} perahu</span>
                            @if ($tugas->jarak_km !== null)<span>{{ number_format((float)$tugas->jarak_km,2) }} km dari posko</span>@endif
                        </div>
                        @if ($tugas->catatan)
                            <div class="community-assignment-note">Catatan: {{ \Illuminate\Support\Str::limit($tugas->catatan, 150) }}</div>
                        @endif
                    </div>
                    <div class="community-assignment-action">
                        <a class="btn btn-navy btn-sm" href="{{ route('komunitas.penugasan.show',$tugas) }}" aria-label="Lihat detail penugasan #{{ $tugas->id }}">
                            <i data-lucide="arrow-up-right"></i> Lihat detail
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="community-pagination">{{ $penugasan->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
if(window.lucide)window.lucide.createIcons();
</script>
@endpush
