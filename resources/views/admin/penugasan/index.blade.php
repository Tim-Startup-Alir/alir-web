@extends('layouts.admin')

@section('title', 'Penugasan Relawan')
@section('topbar_title', 'Penugasan Relawan')
@section('topbar_subtitle', 'Rekomendasi komunitas dan koordinasi lapangan')

@push('styles')
<style>
.assignment-page{display:grid;gap:20px}
.assignment-header{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:20px;align-items:end}
.assignment-header h2{margin:5px 0 6px;font-size:30px;letter-spacing:-.035em}
.assignment-header p{margin:0;color:#6b7b8d;max-width:680px}
.assignment-stats{display:grid;grid-template-columns:repeat(3,minmax(132px,1fr));gap:10px}
.assignment-stat{display:flex;min-width:0;min-height:74px;align-items:center;gap:12px;padding:14px;border:1px solid #e2e8ee;border-radius:12px;background:#fff;box-shadow:0 2px 8px rgba(28,52,73,.035)}
.assignment-stat-icon{display:flex;align-items:center;justify-content:center;flex:0 0 42px;width:42px;height:42px;min-width:42px;min-height:42px;border-radius:11px;background:#fff4df;color:#a86a11;padding:0;margin:0;line-height:0;box-sizing:border-box}
.assignment-stat-icon svg,.assignment-stat-icon i,.assignment-stat-icon [data-lucide]{display:block;width:20px;height:20px;min-width:20px;min-height:20px;max-width:20px;max-height:20px;stroke-width:2;margin:0;padding:0;flex-shrink:0}
.assignment-stat.is-assigned .assignment-stat-icon{background:#e8f4f3;color:#187b89}
.assignment-stat.is-priority .assignment-stat-icon{background:#fff0ee;color:#bc5b4f}
.assignment-stat-copy{min-width:0}
.assignment-stat strong{display:block;font-size:23px;font-weight:800;letter-spacing:-.025em;color:#18384f;line-height:1.1}
.assignment-stat-copy>span{display:block;margin-top:5px;font-size:12px;font-weight:600;line-height:1.3;color:#75879a}
.assignment-filter{display:grid;grid-template-columns:repeat(3,minmax(145px,1fr)) minmax(200px,1.35fr) auto;align-items:end;gap:12px;padding:14px;border:1px solid #e1e8ed;border-radius:13px;background:#fff}
.assignment-filter label{display:grid;gap:6px;font-size:12px;font-weight:700;color:#52677a}
.assignment-filter select,.assignment-filter input{height:42px;width:100%;border:1px solid #dce5ea;border-radius:9px;background:#fbfcfd;padding:0 11px;color:#263d52;font:inherit;font-size:13px}
.assignment-filter-actions{display:flex;align-items:center;gap:7px;min-height:42px}
.assignment-tabs{display:flex;gap:4px;border-bottom:1px solid #e5ebef}
.assignment-tabs a{padding:11px 12px;border-bottom:2px solid transparent;color:#718195;text-decoration:none;font-size:12px;font-weight:800}
.assignment-tabs a.active{border-bottom-color:#187b89;color:#187b89}
.assignment-tabs span{margin-left:5px;color:#91a0ae}
.report-list{display:grid;gap:8px}
.report-item{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;padding:14px 16px;border:1px solid #e2e8ed;border-left:3px solid #d5e2e8;border-radius:10px;background:#fff}
.report-item.priority{border-left-color:#d69a32}
.report-main{min-width:0}
.report-heading{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.report-heading h3{margin:0;font-size:15px;color:#1d3448}
.report-location{margin-top:4px;color:#75879a;font-size:12px}
.report-facts{display:flex;gap:16px;margin-top:10px;flex-wrap:wrap;color:#6c7c8c;font-size:12px}
.report-facts strong{color:#263e53}
.report-actions{display:flex;align-items:center;justify-content:flex-end;gap:6px}
.report-action-button{display:inline-flex;min-height:36px;align-items:center;justify-content:center;padding:8px 11px;border:1px solid #d5e5e8;border-radius:8px;background:#fff;color:#187b89;font-size:12px;font-weight:800;line-height:1.2;text-decoration:none;white-space:nowrap;cursor:pointer;transition:background .15s,border-color .15s}
.report-action-button:hover,.report-action-button:focus-visible{border-color:#b8d5d9;background:#eef7f7;color:#126c78}
.recommendation-panel{grid-column:1/-1;display:none;margin-top:4px;padding-top:12px;border-top:1px solid #edf1f4}
.report-item.open .recommendation-panel{display:block}
.recommendation-heading{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
.recommendation-heading h4{margin:0;font-size:13px;color:#263e53}
.recommendation-heading span{font-size:11px;color:#8493a1}
.recommendation-groups{display:grid;gap:7px}
.recommendation-group{border:1px solid #e6ecef;border-radius:9px}
.recommendation-group summary{display:flex;justify-content:space-between;padding:9px 11px;cursor:pointer;font-size:12px;font-weight:800;color:#344e63;list-style:none}
.recommendation-group summary::-webkit-details-marker{display:none}
.recommendation-group.priority{background:#fbfdfd}
.candidate-list{display:grid;gap:6px;padding:0 8px 8px}
.candidate{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;padding:10px;border:1px solid #edf1f3;border-radius:8px;background:#fff}
.candidate.best,.candidate.selected{border-left:3px solid #2e9b83}
.candidate.selected{background:#f4faf8}
.candidate.disabled{background:#fafbfc}
.candidate-details{min-width:0}
.candidate-name{font-size:13px;font-weight:800;color:#20384e}
.candidate-meta{display:flex;gap:10px;flex-wrap:wrap;margin-top:5px;color:#738396;font-size:11px}
.candidate-side{display:grid;justify-items:end;align-content:start;gap:6px;text-align:right}
.candidate-score{font-size:11px;font-weight:800;color:#247f70}
.candidate-actions{margin-top:2px;white-space:nowrap}
.candidate-reason{margin-top:6px;color:#9a5d09;font-size:11px}
.candidate-empty{padding:9px;color:#8291a0;font-size:12px}
.candidate-status{display:flex;gap:5px;flex-wrap:wrap;margin-top:7px}
.task-section{margin-top:4px}
.task-section-head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:8px}
.task-section-head h3{margin:0;font-size:17px}
.task-view-switcher{display:inline-flex;gap:3px;padding:3px;border:1px solid #dce5ea;border-radius:9px;background:#f7f9fa}
.task-view-switcher button{min-height:30px;padding:6px 12px;border:0;border-radius:6px;background:transparent;color:#718195;font:inherit;font-size:12px;font-weight:800;cursor:pointer}
.task-view-switcher button[aria-pressed="true"]{background:#fff;color:#187b89;box-shadow:0 1px 3px rgba(28,52,73,.12)}
.task-view-panel[hidden]{display:none}
.task-table-wrap{overflow:auto;border:1px solid #e2e8ed;border-radius:10px;background:#fff}
.task-table{width:100%;border-collapse:collapse;font-size:12px}
.task-table th,.task-table td{padding:10px 12px;border-bottom:1px solid #edf1f4;text-align:left;white-space:nowrap}
.task-table th{background:#f7f9fa;color:#718195;font-size:10px;text-transform:uppercase;letter-spacing:.04em}
.task-actions{display:flex;gap:5px}
.icon-action{display:inline-grid;place-items:center;width:32px;height:32px;border:1px solid #dde6eb;border-radius:8px;background:#fff;color:#466176;cursor:pointer}
.icon-action:hover{background:#eef5f7;color:#187b89}
.icon-action.danger{color:#b44b49}
.dialog,.assign-dialog{position:fixed;inset:0;margin:auto;width:min(560px,calc(100vw - 32px));max-height:min(84vh,760px);overflow-y:auto;overscroll-behavior:contain;padding:0;border:0;border-radius:14px;background:#fff;box-shadow:0 18px 60px rgba(13,31,49,.25)}
.dialog::backdrop,.assign-dialog::backdrop{background:rgba(15,31,47,.52)}
.edit-assignment-dialog{width:min(720px,calc(100vw - 32px))}
.dialog-body{padding:18px}
.dialog-head,.assign-dialog-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}
.dialog-head h3,.assign-dialog-head h3{margin:0;font-size:18px;color:#20384e}
.dialog-close{border:0;background:transparent;font-size:24px;color:#718195;cursor:pointer}
.detail-grid{display:grid;grid-template-columns:140px 1fr;gap:7px 12px;font-size:12px}
.detail-grid dt{color:#718195;font-weight:800}.detail-grid dd{margin:0;color:#263d52}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.form-grid label{display:grid;gap:5px;font-size:11px;font-weight:800;color:#718195}
.form-grid input,.form-grid select,.form-grid textarea{width:100%;border:1px solid #dce5ea;border-radius:8px;padding:9px;font:inherit;font-size:12px;color:#263d52}
.form-grid textarea{min-height:70px;resize:vertical}
.span-2{grid-column:1/-1}.dialog-actions{display:flex;justify-content:flex-end;gap:7px;margin-top:12px}
.edit-assignment-form{gap:12px}
.edit-form-section{padding:13px;border:1px solid #e5ebef;border-radius:10px;background:#f9fbfc}
.edit-form-section h4{margin:0 0 10px;color:#2e475b;font-size:12px}
.edit-assignment-context{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px}
.edit-assignment-context .context-card{min-width:0;padding:12px;border:1px solid #e5ebef;border-radius:10px;background:#f9fbfc}
.edit-assignment-context h4{margin:0 0 9px;color:#2e475b;font-size:12px}
.edit-assignment-context .detail-grid{grid-template-columns:105px minmax(0,1fr);gap:6px 9px;font-size:11px}
.edit-assignment-context .detail-grid dd{overflow-wrap:anywhere}
.transfer-help{margin:7px 0 0;color:#77899a;font-size:11px;font-weight:400;line-height:1.5}
.transfer-reason[hidden]{display:none}
.assign-dialog-content{padding:20px}
.assign-dialog-summary{margin:0 0 14px;padding:12px;border:1px solid #e5ebef;border-radius:10px;background:#f9fbfc;color:#52677a;font-size:12px}
.assign-dialog-summary p{margin:4px 0}
.assign-dialog-form{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.assign-dialog-form label{display:grid;gap:5px;color:#52677a;font-size:12px;font-weight:700}
.assign-dialog-form input,.assign-dialog-form textarea{width:100%;border:1px solid #dce5ea;border-radius:8px;padding:9px;font:inherit;font-size:13px;color:#263d52}
.assign-dialog-form textarea{min-height:74px;resize:vertical}
.assign-dialog-note,.assign-dialog-actions{grid-column:1/-1}
.assign-dialog-actions{display:flex;justify-content:flex-end;gap:7px}
.empty-state{padding:28px;border:1px dashed #d9e3e8;border-radius:10px;text-align:center;color:#718195}
.pagination-wrap{margin-top:10px}
@media(max-width:1050px){
.assignment-header{grid-template-columns:1fr}
.assignment-filter{grid-template-columns:repeat(3,minmax(120px,1fr))}
.assignment-filter-actions{grid-column:1/-1;justify-content:flex-end}
.report-item{grid-template-columns:1fr}.report-actions{justify-content:flex-start}
}
@media(max-width:700px){
.assignment-header h2{font-size:24px}.assignment-filter{grid-template-columns:1fr 1fr}
.assignment-stats{grid-template-columns:repeat(2,minmax(0,1fr))}
.candidate{grid-template-columns:1fr}.candidate-side{justify-items:start;text-align:left}.form-grid{grid-template-columns:1fr}.span-2{grid-column:auto}
.detail-grid{grid-template-columns:1fr}.edit-assignment-context{grid-template-columns:1fr}.assignment-tabs{overflow:auto}.task-table{min-width:850px}
.assign-dialog-form{grid-template-columns:1fr}.assign-dialog-note,.assign-dialog-actions{grid-column:auto}
}
@media(max-width:440px){
.assignment-filter{grid-template-columns:1fr}.assignment-filter-actions{grid-column:auto;justify-content:stretch}.assignment-filter-actions>*{flex:1;justify-content:center}
.assignment-stats{grid-template-columns:1fr}
}
</style>
@endpush

@section('content')
<div class="assignment-page">
    <header class="assignment-header">
        <div>
            <span class="kicker">LAPORAN OPERASIONAL</span>
            <h2>Penugasan Relawan</h2>
            <p>Pilih komunitas relawan untuk menangani laporan banjir.</p>
        </div>
        <div class="assignment-stats">
            <div class="assignment-stat is-waiting">
                <span class="assignment-stat-icon" aria-hidden="true"><i data-lucide="clock"></i></span>
                <span class="assignment-stat-copy"><strong>{{ $jumlahLaporan['belum_ditugaskan'] }}</strong><span>Menunggu penugasan</span></span>
            </div>
            <div class="assignment-stat is-assigned">
                <span class="assignment-stat-icon" aria-hidden="true"><i data-lucide="users"></i></span>
                <span class="assignment-stat-copy"><strong>{{ $jumlahLaporan['sudah_ditugaskan'] }}</strong><span>Sudah ditugaskan</span></span>
            </div>
            <div class="assignment-stat is-priority">
                <span class="assignment-stat-icon" aria-hidden="true"><i data-lucide="triangle-alert"></i></span>
                <span class="assignment-stat-copy"><strong>{{ $jumlahPrioritasTinggi }}</strong><span>Prioritas tinggi</span></span>
            </div>
        </div>
    </header>

    <nav class="assignment-tabs" aria-label="Filter status laporan">
        @foreach (['semua' => 'Semua', 'belum_ditugaskan' => 'Belum ditugaskan', 'sudah_ditugaskan' => 'Sudah ditugaskan'] as $key => $label)
            <a href="{{ route('admin.penugasan.index', array_merge(request()->except('status','page','tugas_page','view'), ['status' => $key])) }}" class="{{ $statusFilter === $key ? 'active' : '' }}"><span>{{ $label }}</span><span>{{ $jumlahLaporan[$key] }}</span></a>
        @endforeach
    </nav>

    <form method="GET" action="{{ route('admin.penugasan.index') }}" class="assignment-filter">
        <input type="hidden" name="status" value="{{ $statusFilter }}">
        @if ($statusFilter === 'sudah_ditugaskan')
            <input type="hidden" name="view" value="{{ $taskView }}" data-task-view-filter>
        @endif
        <label>Kepercayaan
            <select name="kepercayaan">
                @foreach (['semua'=>'Semua','rendah'=>'Rendah','sedang'=>'Sedang','tinggi'=>'Tinggi'] as $key=>$label)
                    <option value="{{ $key }}" @selected($confidenceFilter === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>Keparahan
            <select name="severitas">
                @foreach (['semua'=>'Semua','ringan'=>'Ringan','sedang'=>'Sedang','berat'=>'Berat'] as $key=>$label)
                    <option value="{{ $key }}" @selected($severityFilter === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>Kelurahan
            <select name="kelurahan">
                <option value="">Semua kelurahan</option>
                @foreach ($opsiKelurahan as $wilayah)
                    <option value="{{ $wilayah->id }}" @selected((int)$villageFilter === (int)$wilayah->id)>{{ str_replace('Pasirkratonkramat','Pasirkraton Kramat',$wilayah->nama_kelurahan) }}</option>
                @endforeach
            </select>
        </label>
        <label>Cari laporan
            <input type="search" name="q" value="{{ $search }}" placeholder="Kode atau alamat laporan">
        </label>
        <div class="assignment-filter-actions">
            <button type="submit" class="btn btn-navy btn-sm">Terapkan</button>
            <a href="{{ route('admin.penugasan.index') }}" class="btn btn-ghost btn-sm">Reset</a>
        </div>
    </form>

    @if ($statusFilter !== 'sudah_ditugaskan')
        @if ($laporan->isEmpty())
            <div class="empty-state">Tidak ada laporan untuk filter saat ini.</div>
        @else
            <div class="report-list">
            @foreach ($laporan as $item)
                @php
                    $dataRekomendasi = $rekomendasiLaporan[$item->id];
                    $aktif = $item->penugasan;
                    $semuaKandidat = collect($dataRekomendasi['kelompok'])->flatMap(fn ($group) => $group['kandidat']);
                    $kandidatTerbaik = $semuaKandidat->filter(fn ($candidate) => $candidate['bisa_ditugaskan'])->sortByDesc('skor_rekomendasi')->first();
                    $bestCandidateId = $kandidatTerbaik ? (int)$kandidatTerbaik['komunitas']->id : null;
                    $statusLaporan = $aktif->isNotEmpty() ? 'Ditugaskan' : 'Belum ditugaskan';
                    $severityClass = match ($item->severity) {'ringan'=>'teal','sedang'=>'amber',default=>'red'};
                @endphp
                <article class="report-item {{ $item->severity === 'berat' ? 'priority' : '' }}" id="laporan-{{ $item->id }}">
                    <div class="report-main">
                        <div class="report-heading">
                            <h3>P-{{ $item->kode_pelacakan }}</h3>
                            <span class="tag {{ $aktif->isNotEmpty() ? 'teal' : 'amber' }}">{{ $statusLaporan }}</span>
                            <span class="tag {{ $severityClass }}">Keparahan {{ ucfirst($item->severity) }}</span>
                        </div>
                        <div class="report-location">{{ $item->wilayah?->nama_kelurahan ?? 'Wilayah tidak diketahui' }} &middot; {{ $item->alamat_singkat ?: 'Lokasi laporan' }} &middot; {{ $item->created_at?->translatedFormat('d M Y, H:i') }}</div>
                        <div class="report-facts">
                            <span><strong>{{ $item->jumlah_warga }}</strong> warga terdampak</span>
                            <span>Kepercayaan <strong>{{ ucfirst($item->tingkat_kepercayaan ?? 'belum dinilai') }}</strong></span>
                        </div>
                    </div>
                    <div class="report-actions">
                        @if ($aktif->isNotEmpty())
                            @foreach ($aktif as $tugas)
                                <button type="button" class="report-action-button" data-dialog-id="lihat-penugasan-{{ $tugas->id }}">Lihat penugasan</button>
                            @endforeach
                        @endif
                        @if ($aktif->isEmpty())
                            <button type="button" class="report-action-button" data-toggle-recommendations aria-expanded="false">Lihat rekomendasi</button>
                        @endif
                    </div>

                    @if ($aktif->isEmpty())
                    <div class="recommendation-panel">
                        <div class="recommendation-heading"><h4>Rekomendasi komunitas</h4><span>Diurutkan berdasarkan skor rekomendasi</span></div>
                        <div class="recommendation-groups">
                            @foreach ($dataRekomendasi['kelompok'] as $group)
                                @php $prioritas = $group['prioritas']; @endphp
                                <details class="recommendation-group {{ $prioritas ? 'priority' : '' }}" @if ($prioritas) open @endif>
                                    <summary><span>{{ $group['nama'] }}</span><span>{{ $group['kandidat']->count() }} komunitas</span></summary>
                                    <div class="candidate-list">
                                        @forelse ($group['kandidat'] as $candidate)
                                            @include('admin.penugasan._candidate', ['candidate'=>$candidate,'laporan'=>$item,'bestCandidateId'=>$bestCandidateId])
                                        @empty
                                            <div class="candidate-empty">Belum ada komunitas terdaftar di kelurahan ini.</div>
                                        @endforelse
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </article>
            @endforeach
            </div>
            <div class="pagination-wrap">{{ $laporan->links() }}</div>
        @endif
    @endif

    @if ($statusFilter === 'sudah_ditugaskan')
    <section class="task-section">
        <div class="task-section-head">
            <h3>Daftar penugasan</h3>
            <div class="task-view-switcher" role="group" aria-label="Mode tampilan penugasan" data-task-view-switcher data-view="{{ $taskView }}">
                <button type="button" data-task-view="table" aria-pressed="{{ $taskView === 'table' ? 'true' : 'false' }}">Tabel</button>
                <button type="button" data-task-view="card" aria-pressed="{{ $taskView === 'card' ? 'true' : 'false' }}">Card</button>
            </div>
        </div>
        @if ($daftarTugas->isEmpty())
            <div class="empty-state">Belum ada penugasan tersimpan.</div>
        @else
        <div class="task-view-panel" data-task-view-panel="table" @if ($taskView !== 'table') hidden @endif>
            <div class="task-table-wrap">
                <table class="task-table">
                    <thead><tr><th>Laporan</th><th>Komunitas</th><th>Personel</th><th>Perahu</th><th>Status</th><th>Waktu</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @foreach ($daftarTugas as $tugas)
                            <tr>
                                <td>P-{{ $tugas->laporanBanjir?->kode_pelacakan ?? '-' }}</td>
                                <td>{{ $tugas->komunitasRelawan?->nama ?? '-' }}</td>
                                <td>{{ $tugas->jumlah_personil_tugas }}</td>
                                <td>{{ $tugas->armada_perahu_tugas }}</td>
                                <td><span class="tag {{ in_array($tugas->stage,['diterima','menuju_lokasi','ditangani','selesai'],true)?'teal':($tugas->stage==='ditolak'?'red':'navy') }}">{{ ucfirst(str_replace('_',' ', $tugas->stage)) }}</span></td>
                                <td>{{ $tugas->created_at?->translatedFormat('d M Y, H:i') }}</td>
                                <td>
                                    <div class="task-actions">
                                        <button type="button" class="icon-action" data-dialog-id="lihat-penugasan-{{ $tugas->id }}" title="Lihat detail" aria-label="Lihat detail penugasan"><i data-lucide="eye"></i></button>
                                        <button type="button" class="icon-action" data-dialog-id="edit-penugasan-{{ $tugas->id }}" title="Edit" aria-label="Edit penugasan"><i data-lucide="pencil"></i></button>
                                        <button type="button" class="icon-action danger" data-dialog-id="hapus-penugasan-{{ $tugas->id }}" title="Hapus" aria-label="Hapus penugasan"><i data-lucide="trash-2"></i></button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="report-list task-view-panel" data-task-view-panel="card" @if ($taskView !== 'card') hidden @endif>
            @foreach ($daftarTugas as $tugas)
                @php
                    $laporanTugas = $tugas->laporanBanjir;
                    $tahapClass = match ($tugas->stage) {
                        'menunggu' => 'amber',
                        'diterima', 'menuju_lokasi', 'ditangani', 'selesai' => 'teal',
                        'ditolak' => 'red',
                        default => 'navy',
                    };
                    $keparahanClass = match ($laporanTugas?->severity) {
                        'ringan' => 'teal',
                        'sedang' => 'amber',
                        default => 'red',
                    };
                @endphp
                <article class="report-item task-card {{ $laporanTugas?->severity === 'berat' ? 'priority' : '' }}" data-assignment-id="{{ $tugas->id }}">
                    <div class="report-main">
                        <div class="report-heading">
                            <h3>P-{{ $laporanTugas?->kode_pelacakan ?? '-' }}</h3>
                            <span class="tag {{ $tahapClass }}">{{ ucfirst(str_replace('_', ' ', $tugas->stage)) }}</span>
                            <span class="tag {{ $keparahanClass }}">Keparahan {{ ucfirst($laporanTugas?->severity ?? 'tidak diketahui') }}</span>
                        </div>
                        <div class="report-location">
                            {{ $tugas->komunitasRelawan?->nama ?? 'Komunitas tidak diketahui' }}
                            &middot; {{ $laporanTugas?->wilayah?->nama_kelurahan ?? 'Wilayah tidak diketahui' }}
                            &middot; {{ $laporanTugas?->alamat_singkat ?: 'Lokasi laporan' }}
                        </div>
                        <div class="report-facts">
                            <span>Laporan {{ $laporanTugas?->created_at?->translatedFormat('d M Y, H:i') ?? '-' }}</span>
                            <span><strong>{{ $laporanTugas?->jumlah_warga ?? '-' }}</strong> warga terdampak</span>
                            <span>Kepercayaan <strong>{{ ucfirst($laporanTugas?->tingkat_kepercayaan ?? 'belum dinilai') }}</strong></span>
                            <span><strong>{{ $tugas->jumlah_personil_tugas }}</strong> personel · {{ $tugas->armada_perahu_tugas }} perahu</span>
                        </div>
                    </div>
                    <div class="report-actions task-actions">
                        <button type="button" class="icon-action" data-dialog-id="lihat-penugasan-{{ $tugas->id }}" title="Lihat detail" aria-label="Lihat detail penugasan"><i data-lucide="eye"></i></button>
                        <button type="button" class="icon-action" data-dialog-id="edit-penugasan-{{ $tugas->id }}" title="Edit" aria-label="Edit penugasan"><i data-lucide="pencil"></i></button>
                        <button type="button" class="icon-action danger" data-dialog-id="hapus-penugasan-{{ $tugas->id }}" title="Hapus" aria-label="Hapus penugasan"><i data-lucide="trash-2"></i></button>
                    </div>
                </article>
            @endforeach
        </div>
        @foreach ($daftarTugas as $tugas)
            @php
                $kode = $tugas->laporanBanjir?->kode_pelacakan ?? '-';
                $opsiEdit = $opsiEditPenugasan[$tugas->id] ?? null;
                $bisaAlihkan = in_array($tugas->stage, ['menunggu', 'ditolak'], true) && $opsiEdit !== null;
            @endphp
            <dialog class="dialog" id="lihat-penugasan-{{ $tugas->id }}" aria-labelledby="lihat-penugasan-{{ $tugas->id }}-title">
                <div class="dialog-body">
                    <div class="dialog-head"><h3 id="lihat-penugasan-{{ $tugas->id }}-title">Detail penugasan</h3><button type="button" class="dialog-close" data-close-dialog aria-label="Tutup">&times;</button></div>
                    <dl class="detail-grid">
                        <dt>Laporan</dt><dd>P-{{ $kode }} &middot; {{ $tugas->laporanBanjir?->wilayah?->nama_kelurahan ?? '-' }}</dd>
                        <dt>Komunitas</dt><dd>{{ $tugas->komunitasRelawan?->nama ?? '-' }}</dd>
                        <dt>Posko</dt><dd>{{ $tugas->komunitasRelawan?->poskoUtama()?->nama ?? '-' }}</dd>
                        <dt>Status</dt><dd>{{ ucfirst(str_replace('_',' ', $tugas->stage)) }}</dd>
                        <dt>Personel / perahu</dt><dd>{{ $tugas->jumlah_personil_tugas }} / {{ $tugas->armada_perahu_tugas }}</dd>
                        <dt>Ditugaskan</dt><dd>{{ $tugas->created_at?->translatedFormat('d M Y, H:i') ?? '-' }}</dd>
                        <dt>Diterima</dt><dd>{{ $tugas->waktu_diterima?->translatedFormat('d M Y, H:i') ?? '-' }}</dd>
                        <dt>Ditolak</dt><dd>{{ $tugas->alasan_tolak ?: '-' }}</dd>
                        <dt>Menuju lokasi</dt><dd>{{ $tugas->waktu_menuju_lokasi?->translatedFormat('d M Y, H:i') ?? '-' }}</dd>
                        <dt>Ditangani</dt><dd>{{ $tugas->waktu_ditangani?->translatedFormat('d M Y, H:i') ?? '-' }}</dd>
                        <dt>Selesai</dt><dd>{{ $tugas->waktu_selesai?->translatedFormat('d M Y, H:i') ?? '-' }}</dd>
                        <dt>Catatan</dt><dd>{{ $tugas->catatan ?: '-' }}</dd>
                    </dl>
                    <div class="dialog-actions"><a class="btn btn-navy btn-sm" href="{{ route('admin.penugasan.show',$tugas) }}">Buka halaman detail</a><button type="button" class="btn btn-ghost btn-sm" data-close-dialog>Tutup</button></div>
                </div>
            </dialog>

            <dialog class="dialog edit-assignment-dialog" id="edit-penugasan-{{ $tugas->id }}" aria-labelledby="edit-penugasan-{{ $tugas->id }}-title">
                <div class="dialog-body">
                    <div class="dialog-head"><h3 id="edit-penugasan-{{ $tugas->id }}-title">Edit penugasan</h3><button type="button" class="dialog-close" data-close-dialog aria-label="Tutup">&times;</button></div>
                    <div class="edit-assignment-context">
                        <section class="context-card">
                            <h4>Informasi laporan</h4>
                            <dl class="detail-grid">
                                <dt>Kode laporan</dt><dd>P-{{ $kode }}</dd>
                                <dt>Kelurahan</dt><dd>{{ str_replace('Pasirkratonkramat', 'Pasirkraton Kramat', $tugas->laporanBanjir?->wilayah?->nama_kelurahan ?? '-') }}</dd>
                                <dt>Alamat</dt><dd>{{ $tugas->laporanBanjir?->alamat_singkat ?: '-' }}</dd>
                                <dt>Warga terdampak</dt><dd>{{ $tugas->laporanBanjir?->jumlah_warga ?? '-' }}</dd>
                            </dl>
                        </section>
                        <section class="context-card">
                            <h4>Penugasan saat ini</h4>
                            <dl class="detail-grid">
                                <dt>Komunitas</dt><dd>{{ $tugas->komunitasRelawan?->nama ?? '-' }}</dd>
                                <dt>Posko</dt><dd>{{ $tugas->komunitasRelawan?->poskoUtama()?->nama ?? '-' }}</dd>
                                <dt>Status</dt><dd>{{ ucfirst(str_replace('_',' ', $tugas->stage)) }}</dd>
                            </dl>
                        </section>
                    </div>
                    @if (in_array($tugas->stage, ['diterima', 'menuju_lokasi', 'ditangani', 'selesai'], true))
                        <p class="xs muted">Personel dan komunitas dikunci demi integritas proses lapangan. Hanya catatan yang dapat diperbarui.</p>
                    @elseif ($tugas->stage === 'dibatalkan')
                        <p class="xs muted">Penugasan yang dibatalkan tidak dapat diaktifkan kembali. Hanya catatan internal yang dapat diperbarui.</p>
                    @endif
                    <form method="POST" action="{{ route('admin.penugasan.update',$tugas) }}" class="form-grid edit-assignment-form" data-submit-state>
                        @csrf @method('PATCH')
                        @if ($bisaAlihkan)
                            <section class="edit-form-section span-2">
                                <h4>Komunitas penerima</h4>
                                <label>
                                    Alihkan ke komunitas lain
                                    <select name="komunitas_relawan_id" data-transfer-community>
                                        <option value="" data-max-personil="{{ $opsiEdit['maks_personil_saat_ini'] }}" data-max-perahu="{{ $opsiEdit['maks_perahu_saat_ini'] }}">Tetap di {{ $tugas->komunitasRelawan?->nama }}</option>
                                        @foreach ($opsiEdit['kandidat'] as $candidate)
                                            <option value="{{ $candidate['komunitas']->id }}" data-max-personil="{{ $candidate['sisa_personil'] }}" data-max-perahu="{{ $candidate['sisa_perahu'] }}">{{ $candidate['komunitas']->nama }} &middot; {{ number_format(round($candidate['skor_rekomendasi'])) }}/100</option>
                                        @endforeach
                                    </select>
                                </label>
                                <p class="transfer-help">Alasan pengalihan hanya diminta jika komunitas penerima diubah.</p>
                            </section>
                            <div class="transfer-reason span-2" data-transfer-reason-section hidden>
                                <label>Alasan pengalihan
                                    <input type="text" name="alasan_pengalihan" maxlength="1000" data-transfer-reason>
                                </label>
                            </div>
                        @endif
                        @if ($tugas->stage === 'menunggu')
                            <label>Personel<input type="number" name="jumlah_personil_tugas" min="1" value="{{ $tugas->jumlah_personil_tugas }}" required></label>
                            <label>Perahu<input type="number" name="armada_perahu_tugas" min="0" value="{{ $tugas->armada_perahu_tugas }}"></label>
                        @elseif ($tugas->stage === 'ditolak')
                            <label>Personel<input type="number" name="jumlah_personil_tugas" min="1" value="{{ $tugas->jumlah_personil_tugas }}" data-transfer-personnel data-reassign-only readonly></label>
                            <label>Perahu<input type="number" name="armada_perahu_tugas" min="0" value="{{ $tugas->armada_perahu_tugas }}" data-transfer-boats readonly></label>
                        @elseif (in_array($tugas->stage, ['diterima', 'menuju_lokasi', 'ditangani', 'selesai'], true))
                            <input type="hidden" name="jumlah_personil_tugas" value="{{ $tugas->jumlah_personil_tugas }}">
                            <input type="hidden" name="armada_perahu_tugas" value="{{ $tugas->armada_perahu_tugas }}">
                        @else
                            <label>Personel<input type="number" value="{{ $tugas->jumlah_personil_tugas }}" readonly></label>
                            <label>Perahu<input type="number" value="{{ $tugas->armada_perahu_tugas }}" readonly></label>
                        @endif
                        <label class="span-2">Catatan<textarea name="catatan" maxlength="1000">{{ $tugas->catatan }}</textarea></label>
                        <div class="dialog-actions span-2"><button type="button" class="btn btn-navy btn-sm" data-close-dialog>Batal</button><button type="submit" class="btn btn-coral btn-sm">Simpan perubahan</button></div>
                    </form>
                </div>
            </dialog>

            <dialog class="dialog" id="hapus-penugasan-{{ $tugas->id }}" aria-labelledby="hapus-penugasan-{{ $tugas->id }}-title">
                <div class="dialog-body">
                    <div class="dialog-head"><h3 id="hapus-penugasan-{{ $tugas->id }}-title">Hapus penugasan</h3><button type="button" class="dialog-close" data-close-dialog aria-label="Tutup">&times;</button></div>
                    @if (in_array($tugas->stage, ['diterima', 'menuju_lokasi', 'ditangani'], true))
                        <p>Penugasan berstatus <strong>{{ ucfirst(str_replace('_',' ', $tugas->stage)) }}</strong> tidak dapat dihapus permanen atau dibatalkan karena dapat merusak progres lapangan. Riwayat tetap dipertahankan.</p>
                        <div class="dialog-actions"><button type="button" class="btn btn-navy btn-sm" data-close-dialog>Mengerti</button></div>
                    @else
                        @if ($tugas->stage === 'menunggu')
                            <p>Penugasan menunggu akan dibatalkan dan dipertahankan sebagai riwayat. Laporan utama tidak dihapus.</p>
                        @else
                            <p>Penugasan akan dihapus secara aman dengan soft delete. Riwayat, audit log, dan laporan utama tidak dihapus.</p>
                        @endif
                        <form method="POST" action="{{ route('admin.penugasan.destroy',$tugas) }}" class="form-grid" data-submit-state>
                            @csrf @method('DELETE')
                            <label class="span-2">Alasan penghapusan<input name="alasan" required maxlength="1000"></label>
                            <div class="dialog-actions span-2"><button type="button" class="btn btn-navy btn-sm" data-close-dialog>Batal</button><button type="submit" class="btn btn-red btn-sm">{{ $tugas->stage === 'menunggu' ? 'Batalkan penugasan' : 'Hapus aman' }}</button></div>
                        </form>
                    @endif
                </div>
            </dialog>
        @endforeach
        <div class="pagination-wrap">{{ $daftarTugas->links() }}</div>
        @endif
    </section>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-dialog-id]').forEach(button=>button.addEventListener('click',()=>{const dialog=document.getElementById(button.dataset.dialogId);if(dialog?.showModal)dialog.showModal()}));
document.querySelectorAll('[data-close-dialog]').forEach(button=>button.addEventListener('click',()=>button.closest('dialog')?.close()));
document.querySelectorAll('[data-toggle-recommendations]').forEach(button=>button.addEventListener('click',()=>{const item=button.closest('.report-item');item.classList.toggle('open');const expanded=item.classList.contains('open');button.textContent=expanded?'Tutup rekomendasi':'Lihat rekomendasi';button.setAttribute('aria-expanded',String(expanded))}));
document.querySelectorAll('[data-select-candidate]').forEach(button=>button.addEventListener('click',()=>{const report=button.closest('.report-item');report?.querySelectorAll('.candidate.selected').forEach(candidate=>candidate.classList.remove('selected'));button.closest('.candidate')?.classList.add('selected')}));
document.querySelectorAll('[data-transfer-community]').forEach(select=>{const form=select.closest('form');const reason=form?.querySelector('[data-transfer-reason]');const reasonSection=form?.querySelector('[data-transfer-reason-section]');const personnel=form?.querySelector('[data-transfer-personnel]');const boats=form?.querySelector('[data-transfer-boats]');const updateTransferFields=()=>{const option=select.options[select.selectedIndex];const transferring=select.value!=='';if(reason){reason.required=transferring}if(reasonSection){reasonSection.hidden=!transferring}if(personnel){personnel.readOnly=personnel.hasAttribute('data-reassign-only')&&!transferring;if(personnel.hasAttribute('data-reassign-only'))personnel.required=transferring;personnel.max=option?.dataset.maxPersonil||''}if(boats){boats.readOnly=boats.hasAttribute('data-reassign-only')&&!transferring;boats.max=option?.dataset.maxPerahu||''}};select.addEventListener('change',updateTransferFields);updateTransferFields();});
document.querySelectorAll('[data-submit-state]').forEach(form=>form.addEventListener('submit',()=>{const button=form.querySelector('button[type=\"submit\"]');if(button&&form.reportValidity()){button.disabled=true;button.textContent='Menyimpan...'}}));
document.querySelectorAll('[data-task-view-switcher]').forEach(switcher=>{
    const section=switcher.closest('.task-section');
    const setView=view=>{
        switcher.dataset.view=view;
        switcher.querySelectorAll('[data-task-view]').forEach(button=>button.setAttribute('aria-pressed',String(button.dataset.taskView===view)));
        section?.querySelectorAll('[data-task-view-panel]').forEach(panel=>{panel.hidden=panel.dataset.taskViewPanel!==view});
        const filterInput=document.querySelector('[data-task-view-filter]');
        if(filterInput)filterInput.value=view;
        const url=new URL(window.location.href);
        if(view==='table')url.searchParams.delete('view');else url.searchParams.set('view',view);
        section?.querySelectorAll('.pagination-wrap a[href]').forEach(link=>{
            const pageUrl=new URL(link.href,window.location.href);
            if(view==='table')pageUrl.searchParams.delete('view');else pageUrl.searchParams.set('view',view);
            link.href=pageUrl;
        });
        window.history.replaceState({},'',url);
    };
    switcher.addEventListener('click',event=>{
        const button=event.target.closest('button[data-task-view]');
        if(button)setView(button.dataset.taskView);
    });
    setView(switcher.dataset.view||'table');
});
if(window.lucide)window.lucide.createIcons();
</script>
@endpush
