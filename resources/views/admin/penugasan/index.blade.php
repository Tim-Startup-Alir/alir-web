@extends('layouts.admin')

@section('title', 'Penugasan Relawan')
@section('topbar_title', 'Penugasan Relawan')
@section('topbar_subtitle', 'Rekomendasi komunitas dan koordinasi tugas lapangan')

@push('styles')
    <style>
        .assign-intro{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin-bottom:22px}
        .assign-intro h2{margin:5px 0 7px;font-size:clamp(26px,3vw,36px);letter-spacing:-.035em}
        .assign-intro p{margin:0;max-width:720px}
        .assign-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px;padding:5px;background:#eef3f8;border-radius:14px;width:max-content;max-width:100%}
        .assign-tabs a{display:inline-flex;align-items:center;gap:8px;padding:9px 13px;border-radius:10px;font-size:13px;font-weight:700;color:#5a6c80;text-decoration:none}
        .assign-tabs a.active{background:#fff;color:var(--water-deep);box-shadow:0 2px 8px rgba(24,51,78,.08)}
        .assign-tabs .count{font-size:11px;font-weight:800;color:inherit;opacity:.72}
        .report-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;align-items:start}
        .report-card{padding:18px;border:1px solid #e3eaf0;border-radius:18px;background:#fff;box-shadow:0 5px 18px rgba(21,46,71,.045);min-width:0}
        .report-top{display:flex;justify-content:space-between;gap:14px;align-items:flex-start;padding-bottom:14px;border-bottom:1px solid #edf1f5}
        .report-code{font-size:12px;font-weight:800;letter-spacing:.04em;color:var(--water-deep)}
        .report-title{margin:4px 0 3px;font-size:17px;font-weight:800;line-height:1.35;color:#172d42}
        .report-subtitle{font-size:12px;color:#718197}
        .report-status{flex:0 0 auto}
        .report-facts{display:flex;gap:8px 15px;flex-wrap:wrap;padding:12px 0;font-size:12px;color:#607287}
        .report-facts strong{color:#24394e}
        .scope-note{display:inline-flex;align-items:center;gap:6px;margin:0 0 11px;padding:6px 9px;border-radius:8px;background:#fff5e7;color:#9a5d09;font-size:11px;font-weight:800}
        .groups{display:grid;gap:9px}
        .rec-group{border:1px solid #e7edf2;border-radius:12px;overflow:hidden}
        .rec-group.priority{border-color:#b9dbe3}
        .rec-group>summary{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 12px;background:#f8fafc;cursor:pointer;list-style:none}
        .rec-group>summary::-webkit-details-marker{display:none}
        .rec-group.priority>summary{background:#eff8fa}
        .group-name{font-size:12px;font-weight:800;color:#2e455b}
        .group-hint{font-size:10px;font-weight:700;color:#78899a}
        .candidate-list{display:grid;gap:8px;padding:9px}
        .candidate{padding:11px;border:1px solid #e7edf1;border-radius:11px;background:#fff;min-width:0}
        .candidate.disabled{background:#f7f8fa;color:#738092}
        .candidate.best{border-color:#b5dacf;box-shadow:inset 3px 0 0 #38a98e}
        .candidate-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}
        .candidate-main{flex:1;min-width:0}
        .candidate-controls{display:flex;align-items:center;gap:8px;flex:0 0 auto}
        .candidate-assign{white-space:nowrap}
        .candidate-name{font-size:13px;font-weight:800;color:#20384e}
        .candidate.disabled .candidate-name{color:#69798a}
        .score-pill{flex:0 0 auto;padding:4px 7px;border-radius:7px;background:#e8f5f1;color:#147862;font-size:11px;font-weight:800}
        .candidate-stats{display:flex;gap:6px 12px;flex-wrap:wrap;margin:8px 0;color:#6b7b8d;font-size:11px}
        .candidate-status{display:flex;gap:5px;flex-wrap:wrap}
        .candidate-status .tag{font-size:10px;padding:3px 7px}
        .candidate-reason{font-size:11px;color:#7a8795}
        .more-candidates summary{cursor:pointer;color:var(--water-deep);font-size:11px;font-weight:800}
        .more-candidates{padding:3px 9px 9px}
        .assign-dialog{width:min(520px,calc(100vw - 32px));max-width:none;padding:0;border:0;border-radius:16px;box-shadow:0 18px 60px rgba(13,31,49,.28)}
        .assign-dialog::backdrop{background:rgba(15,31,47,.52)}
        .assign-dialog-content{padding:20px}
        .assign-dialog-head{display:flex;justify-content:space-between;align-items:center;gap:12px}
        .assign-dialog-head h3{margin:0;font-size:18px;color:#20384e}
        .dialog-close{border:0;background:transparent;color:#607287;font-size:24px;line-height:1;cursor:pointer}
        .assign-dialog-summary{margin:14px 0;padding:12px;border-radius:10px;background:#f6f9fb;color:#52677b;font-size:12px}
        .assign-dialog-summary p{margin:4px 0}
        .assign-dialog-form{display:grid;grid-template-columns:1fr 1fr;gap:10px}
        .assign-dialog-form label{display:grid;gap:5px;font-size:11px;font-weight:700;color:#66788a}
        .assign-dialog-form input,.assign-dialog-form textarea{width:100%;min-width:0;border:1px solid #dce5eb;border-radius:8px;padding:8px;font:inherit;font-size:13px;color:#24394e}
        .assign-dialog-form textarea{min-height:70px;resize:vertical}
        .assign-dialog-note,.assign-dialog-actions{grid-column:1/-1}
        .assign-dialog-actions{display:flex;justify-content:flex-end;gap:8px}
        .current-tasks{display:grid;gap:8px;margin:0 0 12px}
        .current-task{padding:10px;border:1px solid #dcece8;border-radius:10px;background:#f8fcfb}
        .current-task-head{display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:12px}
        .current-task-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
        .inline-action{display:grid;gap:8px;margin-top:9px;padding-top:9px;border-top:1px solid #e3ece9}
        .inline-action input,.inline-action select{min-width:0;padding:7px 8px;border:1px solid #dce5eb;border-radius:8px;font-size:12px}
        .task-section{margin-top:26px}
        .task-section h3{margin:0}
        .task-section .section-head{display:flex;justify-content:space-between;align-items:end;gap:12px;margin-bottom:12px}
        .task-table{width:100%;border-collapse:collapse;font-size:12px}
        .task-table th,.task-table td{padding:10px 12px;border-bottom:1px solid #edf1f5;text-align:left;vertical-align:middle}
        .task-table th{color:#6b7b8d;font-size:10px;text-transform:uppercase;letter-spacing:.04em;background:#f8fafc}
        .task-table-wrap{overflow:auto;border:1px solid #e7edf2;border-radius:13px}
        .task-actions{display:flex;gap:6px;white-space:nowrap}
        .task-icon-button{display:inline-grid;place-items:center;width:34px;height:34px;border:1px solid #dce5eb;border-radius:9px;background:#fff;color:#35546d;cursor:pointer}
        .task-icon-button:hover:not(:disabled){background:#f1f6f9;color:var(--water-deep)}
        .task-icon-button:disabled{color:#a6b0ba;background:#f3f5f7;cursor:not-allowed}
        .task-action-tooltip{display:inline-flex}
        .task-dialog{width:min(600px,calc(100vw - 32px));max-width:none;max-height:calc(100vh - 32px);overflow:auto;padding:0;border:0;border-radius:16px;box-shadow:0 18px 60px rgba(13,31,49,.28)}
        .task-dialog::backdrop{background:rgba(15,31,47,.52)}
        .task-dialog-content{padding:20px}
        .task-dialog-head{display:flex;justify-content:space-between;align-items:center;gap:12px}
        .task-dialog-head h3{margin:0;font-size:18px;color:#20384e}
        .task-dialog-summary{display:grid;grid-template-columns:150px 1fr;gap:8px 12px;margin:16px 0;font-size:12px}
        .task-dialog-summary dt{font-weight:700;color:#66788a}
        .task-dialog-summary dd{margin:0;color:#263d53}
        .task-edit-form{display:grid;grid-template-columns:1fr 1fr;gap:10px}
        .task-edit-form label{display:grid;gap:5px;font-size:11px;font-weight:700;color:#66788a}
        .task-edit-form input,.task-edit-form select,.task-edit-form textarea{width:100%;min-width:0;border:1px solid #dce5eb;border-radius:8px;padding:8px;font:inherit;font-size:13px;color:#24394e}
        .task-edit-form textarea{min-height:70px;resize:vertical}
        .task-edit-note,.task-edit-reason,.task-dialog-actions{grid-column:1/-1}
        .task-dialog-actions{display:flex;justify-content:flex-end;gap:8px}
        .task-delete-form{display:grid;gap:10px;margin-top:12px}
        .task-delete-form input{width:100%;padding:8px;border:1px solid #dce5eb;border-radius:8px;font:inherit;font-size:13px}
        .compact-empty{padding:17px;border:1px dashed #d8e2e9;border-radius:12px;text-align:center;color:#718197;font-size:12px}
        .pagination-wrap{margin-top:14px}
        @media(max-width:1050px){.report-grid{grid-template-columns:1fr}}
        @media(max-width:600px){
            .assign-intro{display:block}
            .assign-tabs{width:100%;gap:3px}
            .assign-tabs a{flex:1;justify-content:center;padding:8px 6px;font-size:11px}
            .report-card{padding:14px}
            .report-top{gap:8px}
            .report-status .tag{white-space:normal;text-align:center}
            .candidate-head{flex-wrap:wrap}
            .candidate-controls{flex:1;justify-content:flex-end}
            .assign-dialog-content{padding:16px}
            .task-table{min-width:850px}
            .task-dialog-content{padding:16px}
            .task-dialog-summary{grid-template-columns:1fr;gap:3px}
            .task-dialog-summary dd{margin-bottom:8px}
        }
    </style>
@endpush

@section('content')
    <div class="assign-intro">
        <div>
            <span class="kicker">LAPORAN</span>
            <h2>Penugasan Relawan</h2>
            <p class="muted">Rekomendasi komunitas diurutkan berdasarkan jarak (Haversine). ETA dihitung 15 km/jam.</p>
        </div>
    </div>

    <nav class="assign-tabs" aria-label="Filter status penugasan">
        @foreach (['semua' => 'Semua', 'belum_ditugaskan' => 'Belum ditugaskan', 'sudah_ditugaskan' => 'Sudah ditugaskan'] as $key => $label)
            <a href="{{ route('admin.penugasan.index', ['status' => $key]) }}" class="{{ $statusFilter === $key ? 'active' : '' }}" @if ($statusFilter === $key) aria-current="page" @endif>
                {{ $label }} <span class="count">{{ $jumlahLaporan[$key] }}</span>
            </a>
        @endforeach
    </nav>

    @if ($laporan->isEmpty())
        <div class="compact-empty">Tidak ada laporan untuk filter ini.</div>
    @else
        <div class="report-grid">
            @foreach ($laporan as $item)
                @php
                    $dataRekomendasi = $rekomendasiLaporan[$item->id];
                    $aktif = $item->penugasan;
                    $prioritasWilayah = $dataRekomendasi['wilayah_laporan'];
                    $severityClass = match ($item->severity) {
                        'ringan' => 'teal',
                        'sedang' => 'amber',
                        'berat' => 'red',
                    };
                    $semuaKandidat = collect($dataRekomendasi['kelompok'])->flatMap(fn ($group) => $group['kandidat']);
                    $kandidatTerbaik = $semuaKandidat
                        ->filter(fn ($candidate) => $candidate['bisa_ditugaskan'])
                        ->sortByDesc('skor_rekomendasi')
                        ->first();
                    $bestCandidateId = $kandidatTerbaik ? (int) $kandidatTerbaik['komunitas']->id : null;
                    $tersediaUntukAlih = $semuaKandidat->filter(fn ($candidate) => $candidate['bisa_ditugaskan']);
                    $statusLaporan = $aktif->isNotEmpty()
                        ? 'Ditugaskan'
                        : ($item->status === 'terverifikasi' ? 'Belum ditugaskan' : ucfirst(str_replace('_', ' ', $item->status)));
                @endphp
                <article class="report-card" id="laporan-{{ $item->id }}">
                    <div class="report-top">
                        <div>
                            <h3 class="report-title">P-{{ $item->kode_pelacakan }} &middot; {{ $item->wilayah?->nama_kelurahan ?? 'Wilayah tidak diketahui' }}</h3>
                            <div class="report-subtitle">
                                {{ $item->alamat_singkat ?: 'Lokasi laporan' }}
                                &middot; {{ $item->created_at?->translatedFormat('d M Y, H:i') ?? '-' }}
                            </div>
                        </div>
                        <div class="report-status">
                            <span class="tag {{ $aktif->isNotEmpty() ? 'teal' : 'amber' }}">{{ $statusLaporan }}</span>
                        </div>
                    </div>
                    <div class="report-facts">
                        <span><strong>{{ $item->jumlah_warga }}</strong> warga</span>
                        <span>Kepercayaan <strong>{{ ucfirst($item->tingkat_kepercayaan ?? 'belum dinilai') }}</strong></span>
                        <span class="tag {{ $severityClass }}">Keparahan {{ ucfirst($item->severity) }}</span>
                    </div>

                    @if ($aktif->isEmpty() && $dataRekomendasi['di_luar_cakupan'])
                        <div class="scope-note"><i data-lucide="map-pin-off"></i>Di luar cakupan ALIR</div>
                    @endif

                    @if ($aktif->isNotEmpty())
                        <div class="current-tasks" aria-label="Penugasan aktif">
                            @foreach ($aktif as $tugas)
                                <div class="current-task">
                                    <div class="current-task-head">
                                        <span><strong>{{ $tugas->komunitasRelawan?->nama ?? 'Komunitas' }}</strong> &middot; {{ $tugas->jumlah_personil_tugas }} personel, {{ $tugas->armada_perahu_tugas }} perahu</span>
                                        <span class="tag {{ $tugas->stage === 'menunggu' ? 'amber' : 'teal' }}">{{ ucfirst(str_replace('_', ' ', $tugas->stage)) }}</span>
                                    </div>
                                    <div class="report-subtitle">Ditugaskan {{ $tugas->created_at?->translatedFormat('d M Y, H:i') ?? '-' }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($aktif->isEmpty())
                        <div class="groups">
                            @foreach ($dataRekomendasi['kelompok'] as $group)
                                @php
                                    $prioritas = ! $dataRekomendasi['di_luar_cakupan'] && $group['prioritas'];
                                    $displayed = $group['kandidat']->take(3);
                                    $remaining = $group['kandidat']->slice(3);
                                @endphp
                                <details class="rec-group {{ $prioritas ? 'priority' : '' }}" @if ($prioritas) open @endif>
                                    <summary>
                                        <span class="group-name">
                                            {{ $group['nama'] }}
                                            @if ($prioritas)<span class="tag teal">Wilayah laporan</span>@endif
                                        </span>
                                        <span class="group-hint">{{ $group['kandidat']->count() }} komunitas</span>
                                    </summary>
                                    <div class="candidate-list">
                                        @forelse ($displayed as $candidate)
                                            @include('admin.penugasan._candidate', [
                                                'candidate' => $candidate,
                                                'laporan' => $item,
                                                'bestCandidateId' => $bestCandidateId,
                                            ])
                                        @empty
                                            <div class="candidate-reason">Belum ada komunitas terdaftar pada kelompok ini.</div>
                                        @endforelse
                                    </div>
                                    @if ($remaining->isNotEmpty())
                                        <details class="more-candidates">
                                            <summary>Lihat semua ({{ $group['kandidat']->count() }})</summary>
                                            <div class="candidate-list">
                                                @foreach ($remaining as $candidate)
                                                    @include('admin.penugasan._candidate', [
                                                        'candidate' => $candidate,
                                                        'laporan' => $item,
                                                        'bestCandidateId' => $bestCandidateId,
                                                    ])
                                                @endforeach
                                            </div>
                                        </details>
                                    @endif
                                </details>
                            @endforeach
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
        <div class="pagination-wrap">{{ $laporan->links() }}</div>
    @endif

    @if ($statusFilter === 'sudah_ditugaskan')
    <section class="task-section">
        <div class="section-head">
            <div>
                <span class="kicker">RIWAYAT OPERASI</span>
                <h3>Daftar Tugas</h3>
            </div>
            <span class="xs muted">Termasuk penugasan yang sudah selesai, ditolak, atau dibatalkan</span>
        </div>
        @if ($daftarTugas->isEmpty())
            <div class="compact-empty">Belum ada penugasan yang tersimpan.</div>
        @else
            <div class="task-table-wrap">
                <table class="task-table">
                    <thead>
                        <tr>
                            <th>Kode laporan</th>
                            <th>Komunitas</th>
                            <th>Personel</th>
                            <th>Perahu</th>
                            <th>Status tugas</th>
                            <th>Waktu penugasan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($daftarTugas as $tugas)
                            @php
                                $kodeLaporanTugas = $tugas->laporanBanjir?->kode_pelacakan ?? '-';
                                $alasanAksiNonaktif = 'Hanya penugasan berstatus menunggu yang dapat diedit atau dibatalkan. Status saat ini: '.ucfirst(str_replace('_', ' ', $tugas->stage)).'.';
                                $opsiEdit = $opsiEditPenugasan[$tugas->id] ?? null;
                            @endphp
                            <tr>
                                <td><a href="#laporan-{{ $tugas->laporan_banjir_id }}" class="font-bold text-[var(--water-deep)]">P-{{ $kodeLaporanTugas }}</a></td>
                                <td>{{ $tugas->komunitasRelawan?->nama ?? '-' }}</td>
                                <td>{{ $tugas->jumlah_personil_tugas }}</td>
                                <td>{{ $tugas->armada_perahu_tugas }}</td>
                                <td><span class="tag {{ in_array($tugas->stage, ['menunggu', 'diterima', 'menuju_lokasi', 'ditangani'], true) ? 'teal' : ($tugas->stage === 'ditolak' ? 'red' : 'navy') }}">{{ ucfirst(str_replace('_', ' ', $tugas->stage)) }}</span></td>
                                <td>{{ $tugas->created_at?->translatedFormat('d M Y, H:i') ?? '-' }}</td>
                                <td>
                                    <div class="task-actions">
                                        <button type="button" class="task-icon-button" data-dialog-id="lihat-penugasan-{{ $tugas->id }}" title="Lihat" aria-label="Lihat penugasan P-{{ $kodeLaporanTugas }}"><i data-lucide="eye"></i></button>
                                    @if ($tugas->stage === 'menunggu')
                                        <button type="button" class="task-icon-button" data-dialog-id="edit-penugasan-{{ $tugas->id }}" title="Edit" aria-label="Edit penugasan P-{{ $kodeLaporanTugas }}"><i data-lucide="pencil"></i></button>
                                        <button type="button" class="task-icon-button" data-dialog-id="hapus-penugasan-{{ $tugas->id }}" title="Hapus" aria-label="Hapus penugasan P-{{ $kodeLaporanTugas }}"><i data-lucide="trash-2"></i></button>
                                    @else
                                        <span class="task-action-tooltip" title="{{ $alasanAksiNonaktif }}">
                                            <button type="button" class="task-icon-button" disabled title="{{ $alasanAksiNonaktif }}" aria-label="Edit dinonaktifkan. {{ $alasanAksiNonaktif }}"><i data-lucide="pencil"></i></button>
                                        </span>
                                        <span class="task-action-tooltip" title="{{ $alasanAksiNonaktif }}">
                                            <button type="button" class="task-icon-button" disabled title="{{ $alasanAksiNonaktif }}" aria-label="Hapus dinonaktifkan. {{ $alasanAksiNonaktif }}"><i data-lucide="trash-2"></i></button>
                                        </span>
                                    @endif
                                    </div>

                            <dialog class="task-dialog" id="lihat-penugasan-{{ $tugas->id }}" aria-labelledby="lihat-penugasan-{{ $tugas->id }}-title">
                                <div class="task-dialog-content">
                                    <div class="task-dialog-head">
                                        <h3 id="lihat-penugasan-{{ $tugas->id }}-title">Detail penugasan</h3>
                                        <button type="button" class="dialog-close" data-close-dialog aria-label="Tutup">&times;</button>
                                    </div>
                                    <dl class="task-dialog-summary">
                                        <dt>Laporan</dt><dd>P-{{ $kodeLaporanTugas }} &middot; {{ $tugas->laporanBanjir?->wilayah?->nama_kelurahan ?? 'Wilayah tidak diketahui' }}</dd>
                                        <dt>Komunitas</dt><dd>{{ $tugas->komunitasRelawan?->nama ?? '-' }}</dd>
                                        <dt>Personel</dt><dd>{{ $tugas->jumlah_personil_tugas }}</dd>
                                        <dt>Perahu</dt><dd>{{ $tugas->armada_perahu_tugas }}</dd>
                                        <dt>Catatan</dt><dd>{{ $tugas->catatan ?: '-' }}</dd>
                                        <dt>Status</dt><dd>{{ ucfirst(str_replace('_', ' ', $tugas->stage)) }}</dd>
                                        <dt>Jarak snapshot</dt><dd>{{ $tugas->jarak_km !== null ? number_format((float) $tugas->jarak_km, 3).' km' : '-' }}</dd>
                                        <dt>Skor snapshot</dt><dd>{{ $tugas->skor_rekomendasi !== null ? number_format((float) $tugas->skor_rekomendasi, 2).'/100' : '-' }}</dd>
                                        <dt>Ditugaskan</dt><dd>{{ $tugas->created_at?->translatedFormat('d M Y, H:i') ?? '-' }}</dd>
                                        <dt>Diterima</dt><dd>{{ $tugas->waktu_diterima?->translatedFormat('d M Y, H:i') ?? '-' }}</dd>
                                        <dt>Menuju lokasi</dt><dd>{{ $tugas->waktu_menuju_lokasi?->translatedFormat('d M Y, H:i') ?? '-' }}</dd>
                                        <dt>Ditangani</dt><dd>{{ $tugas->waktu_ditangani?->translatedFormat('d M Y, H:i') ?? '-' }}</dd>
                                        <dt>Selesai</dt><dd>{{ $tugas->waktu_selesai?->translatedFormat('d M Y, H:i') ?? '-' }}</dd>
                                    </dl>
                                    <div class="task-dialog-actions">
                                        <button type="button" class="btn btn-navy btn-sm" data-close-dialog>Tutup</button>
                                    </div>
                                </div>
                            </dialog>

                            @if ($tugas->stage === 'menunggu' && $opsiEdit)
                                <dialog class="task-dialog" id="edit-penugasan-{{ $tugas->id }}" aria-labelledby="edit-penugasan-{{ $tugas->id }}-title">
                                    <div class="task-dialog-content">
                                        <div class="task-dialog-head">
                                            <h3 id="edit-penugasan-{{ $tugas->id }}-title">Edit penugasan</h3>
                                            <button type="button" class="dialog-close" data-close-dialog aria-label="Tutup">&times;</button>
                                        </div>
                                        <form method="POST" action="{{ route('admin.penugasan.update', $tugas) }}" class="task-edit-form" data-submit-state>
                                            @csrf
                                            @method('PATCH')
                                            <label>Personel
                                                <input type="number" name="jumlah_personil_tugas" min="1" max="{{ $opsiEdit['maks_personil_saat_ini'] }}" value="{{ $tugas->jumlah_personil_tugas }}" data-transfer-personnel required>
                                            </label>
                                            <label>Perahu
                                                <input type="number" name="armada_perahu_tugas" min="0" max="{{ $opsiEdit['maks_perahu_saat_ini'] }}" value="{{ $tugas->armada_perahu_tugas }}" data-transfer-boats>
                                            </label>
                                            <label class="task-edit-note">Catatan
                                                <textarea name="catatan" maxlength="1000">{{ $tugas->catatan }}</textarea>
                                            </label>
                                            <label class="task-edit-note">Alihkan ke komunitas lain (opsional)
                                                <select name="komunitas_relawan_id" data-transfer-community>
                                                    <option value="" data-max-personil="{{ $opsiEdit['maks_personil_saat_ini'] }}" data-max-perahu="{{ $opsiEdit['maks_perahu_saat_ini'] }}">Tetap di {{ $tugas->komunitasRelawan?->nama }}</option>
                                                    @foreach ($opsiEdit['kandidat'] as $candidate)
                                                        <option value="{{ $candidate['komunitas']->id }}" data-max-personil="{{ $candidate['sisa_personil'] }}" data-max-perahu="{{ $candidate['sisa_perahu'] }}">
                                                            {{ $candidate['komunitas']->nama }} &middot; skor {{ number_format(round($candidate['skor_rekomendasi'])) }}/100
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            <label class="task-edit-reason">Alasan pengalihan
                                                <input type="text" name="alasan_pengalihan" maxlength="1000" data-transfer-reason>
                                            </label>
                                            <div class="task-dialog-actions">
                                                <button type="button" class="btn btn-navy btn-sm" data-close-dialog>Batal</button>
                                                <button type="submit" class="btn btn-coral btn-sm">Simpan perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </dialog>

                                <dialog class="task-dialog" id="hapus-penugasan-{{ $tugas->id }}" aria-labelledby="hapus-penugasan-{{ $tugas->id }}-title">
                                    <div class="task-dialog-content">
                                        <div class="task-dialog-head">
                                            <h3 id="hapus-penugasan-{{ $tugas->id }}-title">Batalkan penugasan</h3>
                                            <button type="button" class="dialog-close" data-close-dialog aria-label="Tutup">&times;</button>
                                        </div>
                                        <p>Penugasan P-{{ $kodeLaporanTugas }} tidak akan dihapus permanen; statusnya akan menjadi dibatalkan.</p>
                                        <form method="POST" action="{{ route('admin.penugasan.batalkan', $tugas) }}" class="task-delete-form" data-submit-state>
                                            @csrf
                                            <label for="alasan-batal-{{ $tugas->id }}">Alasan pembatalan</label>
                                            <input id="alasan-batal-{{ $tugas->id }}" name="alasan" required maxlength="1000">
                                            <div class="task-dialog-actions">
                                                <button type="button" class="btn btn-navy btn-sm" data-close-dialog>Batal</button>
                                                <button type="submit" class="btn btn-red btn-sm">Konfirmasi pembatalan</button>
                                            </div>
                                        </form>
                                    </div>
                                </dialog>
                            @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination-wrap">{{ $daftarTugas->links() }}</div>
        @endif
    </section>
    @endif
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-dialog-id]').forEach((button) => {
            button.addEventListener('click', () => {
                const dialog = document.getElementById(button.dataset.dialogId);
                if (dialog && typeof dialog.showModal === 'function') dialog.showModal();
            });
        });
        document.querySelectorAll('[data-close-dialog]').forEach((button) => {
            button.addEventListener('click', () => {
                const dialog = button.closest('dialog');
                if (dialog) dialog.close();
            });
        });
        document.querySelectorAll('[data-transfer-community]').forEach((select) => {
            const form = select.closest('form');
            if (!form) return;
            const personnel = form.querySelector('[data-transfer-personnel]');
            const boats = form.querySelector('[data-transfer-boats]');
            const reason = form.querySelector('[data-transfer-reason]');
            const updateLimits = () => {
                const option = select.options[select.selectedIndex];
                if (!option) return;
                if (personnel) personnel.max = option.dataset.maxPersonil;
                if (boats) boats.max = option.dataset.maxPerahu;
                if (reason) reason.required = select.value !== '';
            };
            select.addEventListener('change', updateLimits);
            updateLimits();
        });
        document.querySelectorAll('[data-submit-state]').forEach((form) => {
            form.addEventListener('submit', () => {
                const button = form.querySelector('button[type="submit"]');
                if (!button || !form.reportValidity()) return;
                button.disabled = true;
                button.dataset.originalText = button.textContent;
                button.textContent = 'Menyimpan...';
            });
        });
        if (window.lucide) window.lucide.createIcons();
    </script>
@endpush
