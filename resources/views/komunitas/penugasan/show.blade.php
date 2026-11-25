@extends('layouts.komunitas')

@section('title', 'Detail Penugasan')

@push('styles')
<style>
.community-task-detail{max-width:1080px;display:grid;gap:14px}
.community-detail-header{display:flex;justify-content:space-between;align-items:flex-start;gap:16px}
.community-detail-header h2{margin:4px 0;font-size:26px;line-height:1.25;color:#1f374d}
.community-detail-header p{margin:0;color:#6b7b8d}
.community-detail-grid{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(280px,.85fr);gap:12px;align-items:start}
.community-detail-section{padding:16px;border:1px solid #e1e8ed;border-radius:11px;background:#fff}
.community-detail-section h3{margin:0 0 12px;font-size:15px;color:#273f54}
.community-detail-facts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:11px 18px}
.community-detail-fact span{display:block;color:#718195;font-size:11px;font-weight:700}
.community-detail-fact strong{display:block;margin-top:3px;color:#283f54;font-size:13px;font-weight:700}
.community-detail-notice{margin-top:12px;padding:11px 12px;border-left:3px solid #d39a35;border-radius:7px;background:#fff8eb;color:#77500f;font-size:13px;line-height:1.5}
.community-detail-rejection{border-color:#f0d7d6;background:#fff8f8;color:#883e3c}
.community-timeline{display:grid;gap:0}
.community-timeline-item{position:relative;display:grid;grid-template-columns:18px minmax(0,1fr);gap:9px;padding-bottom:15px}
.community-timeline-item:last-child{padding-bottom:0}
.community-timeline-marker{position:relative;display:flex;justify-content:center}
.community-timeline-marker:before{content:"";width:9px;height:9px;margin-top:4px;border:2px solid #258c79;border-radius:50%;background:#fff;z-index:1}
.community-timeline-item:not(:last-child) .community-timeline-marker:after{content:"";position:absolute;top:14px;bottom:-1px;width:1px;background:#d9e7e4}
.community-timeline-copy strong{display:block;color:#2d465b;font-size:13px}
.community-timeline-copy time{display:block;margin-top:2px;color:#8291a0;font-size:11px}
.community-timeline-copy p{margin:5px 0 0;color:#687b8f;font-size:12px;line-height:1.5}
.community-progress-form{display:grid;gap:10px}
.community-progress-form label,.community-reject-form label{display:grid;gap:5px;color:#687b8f;font-size:12px;font-weight:700}
.community-progress-form textarea,.community-reject-form textarea{width:100%;min-height:84px;resize:vertical;border:1px solid #dce5ea;border-radius:8px;padding:10px;font:inherit;font-size:13px;color:#263d52}
.community-step{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:11px;border-radius:8px;background:#f4f8f8}
.community-step strong{color:#2c4b60;font-size:13px}
.community-reject-form{display:grid;gap:9px;margin-top:12px;padding-top:12px;border-top:1px solid #edf1f4}
.community-detail-footer{display:flex;gap:8px;flex-wrap:wrap}
.community-empty-timeline{color:#8392a0;font-size:12px}
@media(max-width:760px){
 .community-detail-grid{grid-template-columns:1fr}
 .community-detail-header{flex-direction:column}
 .community-detail-header h2{font-size:22px}
 .community-detail-facts{grid-template-columns:1fr 1fr}
}
@media(max-width:450px){.community-detail-facts{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
@php
    $kodeLaporan = $penugasan->laporanBanjir?->kode_pelacakan ?? str_pad((string)$penugasan->laporan_banjir_id, 5, '0', STR_PAD_LEFT);
    $statusClass = match ($penugasan->stage) {
        'menunggu' => 'amber',
        'diterima', 'menuju_lokasi', 'ditangani' => 'teal',
        'ditolak' => 'red',
        default => 'navy',
    };
    $riwayatProgres = collect();
    $labelAktivitas = [
        'penugasan.diterima' => 'Penugasan diterima',
        'penugasan.ditolak' => 'Penugasan ditolak',
        'penugasan.menuju_lokasi' => 'Menuju lokasi',
        'penugasan.ditangani' => 'Penanganan dimulai',
        'penugasan.selesai' => 'Penugasan selesai',
        'penugasan.batalkan' => 'Penugasan dibatalkan',
        'penugasan.alihkan_batalkan' => 'Penugasan dialihkan',
    ];
    $riwayatProgres->push([
        'label' => 'Penugasan dibuat',
        'waktu' => $penugasan->created_at,
        'catatan' => $penugasan->catatan,
    ]);
    foreach ($riwayatAudit as $aktivitas) {
        $tahap = str_replace('penugasan.', '', $aktivitas->aksi);
        $riwayatProgres->push([
            'label' => $labelAktivitas[$aktivitas->aksi] ?? ucfirst(str_replace('_', ' ', $tahap)),
            'waktu' => $aktivitas->waktu,
            'catatan' => $aktivitas->aksi === 'penugasan.ditolak'
                ? ($penugasan->alasan_tolak ?: $aktivitas->deskripsi)
                : ($penugasan->catatan_tahap[$tahap] ?? $aktivitas->deskripsi),
        ]);
    }
    $riwayatProgres = $riwayatProgres->sortBy(fn ($item) => $item['waktu']?->getTimestamp() ?? 0)->values();
    $tahapBerikutnya = match ($penugasan->stage) {
        'diterima' => ['stage' => 'menuju_lokasi', 'label' => 'Menuju lokasi'],
        'menuju_lokasi' => ['stage' => 'ditangani', 'label' => 'Tandai mulai ditangani'],
        'ditangani' => ['stage' => 'selesai', 'label' => 'Tandai selesai'],
        default => null,
    };
@endphp

<div class="community-task-detail">
    <header class="community-detail-header">
        <div>
            <span class="kicker">PENUGASAN #{{ $penugasan->id }} · P-{{ $kodeLaporan }}</span>
            <h2>{{ $penugasan->laporanBanjir?->alamat_singkat ?: 'Lokasi laporan' }}</h2>
            <p>{{ $penugasan->laporanBanjir?->wilayah?->nama_kelurahan ?? 'Wilayah tidak diketahui' }} · Dilaporkan {{ $penugasan->laporanBanjir?->created_at?->translatedFormat('d M Y, H:i') ?? '-' }}</p>
        </div>
        <span class="tag {{ $statusClass }}">{{ ucfirst(str_replace('_',' ', $penugasan->stage)) }}</span>
    </header>

    <div class="community-detail-grid">
        <div class="community-detail-section">
            <h3>Ringkasan lapangan</h3>
            <div class="community-detail-facts">
                <div class="community-detail-fact"><span>Warga terdampak</span><strong>{{ $penugasan->laporanBanjir?->jumlah_warga ?? '-' }} warga</strong></div>
                <div class="community-detail-fact"><span>Personel ditugaskan</span><strong>{{ $penugasan->jumlah_personil_tugas }} relawan</strong></div>
                <div class="community-detail-fact"><span>Armada perahu</span><strong>{{ $penugasan->armada_perahu_tugas }} perahu</strong></div>
                <div class="community-detail-fact"><span>Jarak dari posko</span><strong>{{ $penugasan->jarak_km !== null ? number_format((float)$penugasan->jarak_km,2).' km' : 'Tidak tersedia' }}</strong></div>
                <div class="community-detail-fact"><span>Posko komunitas</span><strong>{{ $penugasan->komunitasRelawan?->poskoUtama()?->nama ?? 'Tidak tersedia' }}</strong></div>
                <div class="community-detail-fact"><span>Waktu ditugaskan</span><strong>{{ $penugasan->created_at?->translatedFormat('d M Y, H:i') ?? '-' }}</strong></div>
            </div>
            @if ($penugasan->catatan)
                <div class="community-detail-notice"><strong>Catatan penugasan</strong><br>{{ $penugasan->catatan }}</div>
            @endif
            @if ($penugasan->stage === 'ditolak')
                <div class="community-detail-notice community-detail-rejection">
                    <strong>Alasan penolakan</strong><br>{{ $penugasan->alasan_tolak ?: 'Tidak ada alasan tercatat.' }}
                    @php $waktuTolak = $riwayatAudit->firstWhere('aksi','penugasan.ditolak')?->waktu; @endphp
                    @if ($waktuTolak)<br><span>Ditolak {{ $waktuTolak->translatedFormat('d M Y, H:i') }}</span>@endif
                </div>
            @endif
        </div>

        <div class="community-detail-section">
            <h3>Riwayat progres</h3>
            @if ($riwayatProgres->isEmpty())
                <p class="community-empty-timeline">Belum ada riwayat progres.</p>
            @else
                <div class="community-timeline">
                    @foreach ($riwayatProgres as $item)
                        <div class="community-timeline-item">
                            <span class="community-timeline-marker" aria-hidden="true"></span>
                            <div class="community-timeline-copy">
                                <strong>{{ $item['label'] }}</strong>
                                <time>{{ $item['waktu']?->translatedFormat('d M Y, H:i') ?? 'Waktu tidak tersedia' }}</time>
                                @if ($item['catatan'])<p>{{ $item['catatan'] }}</p>@endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($penugasan->stage === 'menunggu')
            <section class="community-detail-section">
                <h3>Respons penugasan</h3>
                <p class="muted">Tinjau kebutuhan dan lokasi sebelum menerima atau menolak tugas.</p>
                <div class="community-detail-footer">
                    <form method="POST" action="{{ route('komunitas.penugasan.respon',$penugasan) }}" data-submit-state>
                        @csrf
                        <input type="hidden" name="stage" value="diterima">
                        <button class="btn btn-teal btn-sm" type="submit">Terima tugas</button>
                    </form>
                </div>
                <form method="POST" action="{{ route('komunitas.penugasan.respon',$penugasan) }}" class="community-reject-form" data-submit-state>
                    @csrf
                    <input type="hidden" name="stage" value="ditolak">
                    <label>Alasan penolakan
                        <textarea name="alasan_tolak" required maxlength="1000" placeholder="Jelaskan kendala yang membuat tugas tidak dapat diterima"></textarea>
                    </label>
                    <button class="btn btn-red btn-sm" type="submit">Kirim penolakan</button>
                </form>
            </section>
        @elseif ($tahapBerikutnya)
            <section class="community-detail-section">
                <h3>Pembaruan progres</h3>
                <div class="community-step">
                    <strong>Tahap berikutnya: {{ $tahapBerikutnya['label'] }}</strong>
                    <i data-lucide="arrow-right" aria-hidden="true"></i>
                </div>
                <form method="POST" action="{{ route('komunitas.penugasan.progres',$penugasan) }}" class="community-progress-form mt-3" data-submit-state>
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="stage" value="{{ $tahapBerikutnya['stage'] }}">
                    <label>Catatan progres (opsional)
                        <textarea name="catatan" maxlength="1000" placeholder="Catatan singkat kondisi atau tindakan lapangan"></textarea>
                    </label>
                    <button class="btn btn-teal btn-sm" type="submit">{{ $tahapBerikutnya['label'] }}</button>
                </form>
            </section>
        @elseif ($penugasan->stage === 'selesai')
            <section class="community-detail-section">
                <h3>Penugasan selesai</h3>
                <p class="muted">Progres lapangan telah ditandai selesai. Riwayat dan catatan tetap tersedia di halaman ini.</p>
            </section>
        @endif
    </div>

    <div class="community-detail-footer">
        <a href="{{ route('komunitas.penugasan.index') }}" class="btn btn-ghost btn-sm"><i data-lucide="arrow-left"></i> Kembali ke daftar</a>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-submit-state]').forEach(form=>form.addEventListener('submit',()=>{const button=form.querySelector('button[type="submit"]');if(button&&form.reportValidity()){button.disabled=true;button.textContent='Mengirim...'}}));
if(window.lucide)window.lucide.createIcons();
</script>
@endpush
