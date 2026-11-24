@extends('layouts.admin')

@section('title', 'Penugasan Relawan')
@section('topbar_title', 'Penugasan Relawan')
@section('topbar_subtitle', 'Rekomendasi komunitas diurutkan berdasarkan jarak (Haversine). ETA dihitung 15 km/jam.')

@section('content')
    <div class="pg-h" style="margin-bottom:2px">
        <div>
            <span class="kicker">LAPORAN</span>
            <h1 class="pen-h1">Penugasan Relawan</h1>
            <p class="muted" style="margin-top:4px;font-size:14px">Rekomendasi komunitas diurutkan berdasarkan jarak (Haversine). ETA dihitung 15 km/jam.</p>
        </div>
    </div>

    <div class="pen-container" id="penugasan-app">
        {{-- Segmented Tabs --}}
        <div class="pen-tabs" role="tablist" aria-label="Filter status penugasan laporan">
            <a href="{{ route('admin.penugasan.index', ['tab' => 'semua', 'q' => $search, 'stage' => $stageFilter]) }}"
               class="pen-tab {{ $tab === 'semua' ? 'is-active' : '' }}" role="tab" dusk="tab-semua">
                Semua <span class="pen-tab-count">({{ $counts['semua'] }})</span>
            </a>
            <a href="{{ route('admin.penugasan.index', ['tab' => 'belum_ditugaskan', 'q' => $search, 'stage' => $stageFilter]) }}"
               class="pen-tab {{ $tab === 'belum_ditugaskan' ? 'is-active' : '' }}" role="tab" dusk="tab-belum">
                Belum ditugaskan <span class="pen-tab-count">({{ $counts['belum_ditugaskan'] }})</span>
            </a>
            <a href="{{ route('admin.penugasan.index', ['tab' => 'sudah_ditugaskan', 'q' => $search, 'stage' => $stageFilter]) }}"
               class="pen-tab {{ $tab === 'sudah_ditugaskan' ? 'is-active' : '' }}" role="tab" dusk="tab-sudah">
                Sudah ditugaskan <span class="pen-tab-count">({{ $counts['sudah_ditugaskan'] }})</span>
            </a>
        </div>

        {{-- Daftar Kartu Laporan + Rekomendasi Komunitas --}}
        <div class="pen-cards-wrap" dusk="pen-cards-wrap">
            @forelse ($laporans as $lap)
                @php
                    $rekomendasi = $rekomendasiPerLaporan[$lap->id] ?? collect();
                    $sudahDitugaskan = in_array($lap->status, ['ditugaskan', 'ditangani'], true);
                    $penugasanAktif = $lap->penugasan->firstWhere(fn ($p) => in_array($p->stage, \App\Models\Penugasan::STAGE_AKTIF, true));
                    $penolakanTerakhir = $lap->penugasan->firstWhere('stage', 'ditolak');
                    $adaSiaga = $rekomendasi->contains(fn ($r) => $r['bisa_ditugaskan']);
                    $sevBadge = match ($lap->severity) {
                        'berat' => ['red', 'flame', 'Berat'],
                        'sedang' => ['yellow', 'alert-triangle', 'Sedang'],
                        default => ['teal', 'droplets', 'Ringan'],
                    };
                @endphp
                <article class="pen-card" data-laporan-id="{{ $lap->id }}" dusk="pen-card-{{ $lap->id }}">
                    <div class="pen-card-head">
                        <div>
                            <h2 class="pen-card-title">{{ $lap->kode_pelacakan }} &middot; {{ $lap->wilayah?->nama_kelurahan ?? $lap->alamat_singkat }}</h2>
                            <div class="pen-card-sub">
                                <span class="mono">{{ $lap->jumlah_warga }} warga</span>
                                <span class="pen-sev-pill {{ $sevBadge[0] }}">
                                    <i data-lucide="{{ $sevBadge[1] }}"></i> {{ $sevBadge[2] }}
                                </span>
                                @if (! $sudahDitugaskan && $penolakanTerakhir)
                                    <span class="pen-sev-pill red" title="Alasan: {{ $penolakanTerakhir->alasan_tolak }}">
                                        <i data-lucide="rotate-ccw"></i> Ditolak komunitas: {{ \Illuminate\Support\Str::limit($penolakanTerakhir->alasan_tolak, 50) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <span class="pen-assign-badge {{ $sudahDitugaskan ? 'teal' : 'yellow' }}">
                            <i data-lucide="{{ $sudahDitugaskan ? 'check-circle-2' : 'clock' }}"></i>
                            {{ $sudahDitugaskan ? 'Sudah ditugaskan' : 'Belum ditugaskan' }}
                        </span>
                    </div>

                    @if (! $sudahDitugaskan && ! $adaSiaga)
                        <div class="pen-banner-warn">
                            <i data-lucide="info"></i>
                            <span>Belum ada komunitas siaga untuk laporan ini.</span>
                        </div>
                    @endif

                    <div class="pen-komunitas-list">
                        @forelse ($rekomendasi as $rek)
                            @php
                                $statusCls = match ($rek['status_kode']) {
                                    'siaga' => 'teal',
                                    'sibuk' => 'yellow',
                                    default => 'gray',
                                };
                                $statusIcon = match ($rek['status_kode']) {
                                    'siaga' => 'shield-check',
                                    'sibuk' => 'clock',
                                    default => 'shield-off',
                                };
                                $bolehKlik = ! $sudahDitugaskan && $rek['bisa_ditugaskan'];
                                $alasanTooltip = $sudahDitugaskan ? 'Laporan sudah ditugaskan' : ($rek['alasan_disabled'] ?? '');
                            @endphp
                            <div class="pen-kom-row {{ $rek['is_terbaik'] && ! $sudahDitugaskan ? 'is-best' : '' }}" dusk="pen-kom-row-{{ $lap->id }}-{{ $rek['komunitas_id'] }}">
                                <div class="pen-kom-info">
                                    <div class="pen-kom-name-row">
                                        <strong>{{ $rek['nama'] }}</strong>
                                        @if ($rek['is_terbaik'] && ! $sudahDitugaskan)
                                            <span class="pen-best-pill"><i data-lucide="star"></i> Rekomendasi terbaik</span>
                                        @endif
                                        @if ($penugasanAktif && (int) $penugasanAktif->komunitas_relawan_id === (int) $rek['komunitas_id'])
                                            <span class="pen-best-pill teal"><i data-lucide="check"></i> Sedang ditugaskan</span>
                                        @endif
                                    </div>
                                    <div class="pen-kom-meta mono">
                                        <span><i data-lucide="award"></i> Skor {{ $rek['skor'] }}/100</span>
                                        <span><i data-lucide="map-pin"></i> {{ number_format($rek['jarak_km'], 1, ',', '.') }} km</span>
                                        <span><i data-lucide="clock"></i> ETA {{ $rek['eta_menit'] }} mnt</span>
                                        <span><i data-lucide="users"></i> kapasitas {{ $rek['terpakai'] }}/{{ $rek['kapasitas_max'] }}</span>
                                        <span><i data-lucide="sailboat"></i> {{ $rek['sisa_perahu'] }} perahu</span>
                                    </div>
                                </div>

                                <div class="pen-kom-status">
                                    <span class="pen-status-pill {{ $statusCls }}">
                                        <i data-lucide="{{ $statusIcon }}"></i> {{ $rek['status_label'] }}
                                    </span>
                                </div>

                                <div class="pen-kom-action">
                                    @if ($bolehKlik)
                                        <button type="button"
                                                class="btn btn-sm pen-btn-orange btn-open-tugaskan"
                                                data-store-url="{{ route('admin.penugasan.store', $lap) }}"
                                                data-laporan-label="{{ $lap->kode_pelacakan }} {{ $lap->wilayah?->nama_kelurahan }}"
                                                data-komunitas-id="{{ $rek['komunitas_id'] }}"
                                                data-komunitas-nama="{{ $rek['nama'] }}"
                                                data-jarak-eta="{{ number_format($rek['jarak_km'], 1, ',', '.') }} km (ETA {{ $rek['eta_menit'] }} mnt)"
                                                data-sisa-kapasitas="{{ $rek['sisa_kapasitas'] }}"
                                                data-sisa-perahu="{{ $rek['sisa_perahu'] }}"
                                                dusk="btn-tugaskan-{{ $lap->id }}-{{ $rek['komunitas_id'] }}">
                                            Tugaskan
                                        </button>
                                    @else
                                        <button type="button"
                                                class="btn btn-sm pen-btn-disabled"
                                                aria-disabled="true"
                                                disabled
                                                title="{{ $alasanTooltip }}"
                                                aria-label="Tugaskan tidak tersedia: {{ $alasanTooltip }}"
                                                dusk="btn-tugaskan-disabled-{{ $lap->id }}-{{ $rek['komunitas_id'] }}">
                                            <i data-lucide="lock"></i> Tugaskan
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="pen-empty-inline">Belum ada komunitas relawan aktif dan terverifikasi.</div>
                        @endforelse
                    </div>
                </article>
            @empty
                <div class="pen-empty-card" dusk="pen-empty-laporan">
                    <i data-lucide="clipboard-check"></i>
                    <p>Tidak ada laporan yang perlu ditugaskan</p>
                    <span class="xs muted">Laporan yang sudah diverifikasi valid akan muncul di sini.</span>
                </div>
            @endforelse

            @if ($laporans->hasPages())
                <div>{{ $laporans->links() }}</div>
            @endif
        </div>

        {{-- Daftar Tugas (Tabel) --}}
        <section class="pen-card pen-tasks-card" aria-labelledby="daftar-tugas-title" dusk="daftar-tugas-section">
            <div class="pen-tasks-head">
                <h2 class="pen-card-title" id="daftar-tugas-title">Daftar tugas</h2>
                <form method="GET" action="{{ route('admin.penugasan.index') }}" class="row" style="gap:8px">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <input type="hidden" name="q" value="{{ $search }}">
                    <label class="xs muted" for="filter-stage">Stage:</label>
                    <select id="filter-stage" name="stage" class="pen-stage-select" onchange="this.form.submit()">
                        <option value="semua" {{ $stageFilter === 'semua' ? 'selected' : '' }}>Semua stage</option>
                        <option value="menunggu" {{ $stageFilter === 'menunggu' ? 'selected' : '' }}>Menunggu</option>
                        <option value="diterima" {{ $stageFilter === 'diterima' ? 'selected' : '' }}>Diterima</option>
                        <option value="menuju_lokasi" {{ $stageFilter === 'menuju_lokasi' ? 'selected' : '' }}>Menuju lokasi</option>
                        <option value="ditangani" {{ $stageFilter === 'ditangani' ? 'selected' : '' }}>Di lokasi</option>
                        <option value="selesai" {{ $stageFilter === 'selesai' ? 'selected' : '' }}>Selesai</option>
                        <option value="dibatalkan" {{ $stageFilter === 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                </form>
            </div>

            @if ($daftarTugas->isEmpty())
                <div class="pen-empty-inline" style="padding:32px 16px;text-align:center" dusk="pen-empty-tugas">
                    Belum ada tugas
                </div>
            @else
                <div class="tbl-wrap">
                    <table class="tbl pen-table">
                        <thead>
                            <tr>
                                <th>TUGAS</th>
                                <th>LOKASI</th>
                                <th>KOMUNITAS</th>
                                <th>STAGE</th>
                                <th>PERSONEL &amp; PERAHU</th>
                                <th style="text-align:right">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($daftarTugas as $tugas)
                                @php
                                    $stageBadge = match ($tugas->stage) {
                                        'menunggu' => ['yellow', 'clock', 'Menunggu'],
                                        'diterima' => ['blue', 'check', 'Diterima'],
                                        'menuju_lokasi' => ['blue', 'navigation', 'Menuju lokasi'],
                                        'ditangani' => ['orange', 'activity', 'Di lokasi'],
                                        'selesai' => ['teal', 'check-circle-2', 'Selesai'],
                                        'ditolak' => ['red', 'x-circle', 'Ditolak'],
                                        default => ['gray', 'ban', 'Dibatalkan'],
                                    };
                                    $bolehUbah = $tugas->stage === 'menunggu';
                                    $rekPengganti = ($rekomendasiPerLaporan[$tugas->laporan_banjir_id] ?? collect())
                                        ->filter(fn ($r) => (int) $r['komunitas_id'] !== (int) $tugas->komunitas_relawan_id && $r['bisa_ditugaskan'])
                                        ->values();
                                @endphp
                                <tr dusk="tugas-row-{{ $tugas->id }}">
                                    <td>
                                        <strong class="mono">TK-{{ $tugas->id }}</strong>
                                        <div class="xs muted mono">{{ $tugas->laporanBanjir?->kode_pelacakan }}</div>
                                    </td>
                                    <td>{{ $tugas->laporanBanjir?->wilayah?->nama_kelurahan ?? $tugas->laporanBanjir?->alamat_singkat }}</td>
                                    <td>
                                        <strong>{{ $tugas->komunitasRelawan?->nama }}</strong>
                                        <div class="xs muted">oleh {{ $tugas->penugas?->name ?? 'Admin Tim ALIR' }}</div>
                                    </td>
                                    <td>
                                        <span class="pen-status-pill {{ $stageBadge[0] }}">
                                            <i data-lucide="{{ $stageBadge[1] }}"></i> {{ $stageBadge[2] }}
                                        </span>
                                    </td>
                                    <td class="mono">
                                        {{ $tugas->jumlah_personil_tugas }} personel &middot; {{ $tugas->armada_perahu_tugas }} perahu
                                    </td>
                                    <td style="text-align:right;white-space:nowrap">
                                        @if ($bolehUbah)
                                            <button type="button"
                                                    class="btn btn-ghost btn-sm btn-open-ganti"
                                                    data-ganti-url="{{ route('admin.penugasan.ganti', $tugas) }}"
                                                    data-kode="TK-{{ $tugas->id }}"
                                                    data-personel="{{ $tugas->jumlah_personil_tugas }}"
                                                    data-perahu="{{ $tugas->armada_perahu_tugas }}"
                                                    data-opsi='@json($rekPengganti)'
                                                    dusk="btn-ganti-{{ $tugas->id }}">
                                                Ganti
                                            </button>
                                            <button type="button"
                                                    class="btn btn-ghost btn-sm pen-btn-batal-trigger btn-open-batalkan"
                                                    data-batal-url="{{ route('admin.penugasan.destroy', $tugas) }}"
                                                    data-kode="TK-{{ $tugas->id }}"
                                                    dusk="btn-batalkan-{{ $tugas->id }}">
                                                Batalkan
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-sm pen-btn-disabled" disabled aria-disabled="true" title="Penugasan sudah diterima komunitas" dusk="btn-ganti-disabled-{{ $tugas->id }}">Ganti</button>
                                            <button type="button" class="btn btn-sm pen-btn-disabled" disabled aria-disabled="true" title="Penugasan sudah diterima komunitas" dusk="btn-batalkan-disabled-{{ $tugas->id }}">Batalkan</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    <div class="pen-toast" id="pen-toast" role="status" aria-live="polite" hidden></div>

    @include('admin.penugasan._modal_tugaskan')
    @include('admin.penugasan._modal_ganti')
    @include('admin.penugasan._modal_batalkan')
@endsection

@push('styles')
<style>
:root {
    --ink: #0F1B33;
    --ink-2: #5B6578;
    --ink-3: #8A93A6;
    --orange-500: #FF6B35;
    --orange-600: #F0561D;
    --orange-50: #FFF1EB;
    --orange-200: #FFC9B3;
    --teal-500: #14B8A6;
    --teal-100: #D5F5F0;
    --teal-700: #0F766E;
    --red-500: #E5484D;
    --red-100: #FDE8E8;
    --yellow-400: #F5B82E;
    --yellow-100: #FFF4D6;
    --yellow-800: #7A5A00;
    --bg-soft: #F1F4F9;
    --border: #E6E9F0;
}
.pen-h1 { font-size:32px; font-weight:800; letter-spacing:-0.02em; color:var(--ink); margin-top:4px; line-height:1.15; }
.pen-container { width:100%; display:flex; flex-direction:column; gap:20px; }
.pen-tabs { display:inline-flex; gap:4px; padding:4px; border-radius:999px; background:var(--bg-soft); border:1px solid var(--border); align-self:flex-start; flex-wrap:wrap; }
.pen-tab { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:999px; font-size:13px; font-weight:600; color:var(--ink-2); transition:all .2s; }
.pen-tab.is-active { background:#fff; color:var(--ink); box-shadow:0 1px 3px rgba(15,27,51,.08); }
.pen-tab-count { font-variant-numeric:tabular-nums; color:var(--ink-3); }
.pen-tab.is-active .pen-tab-count { color:var(--ink); }
.pen-cards-wrap { display:flex; flex-direction:column; gap:20px; }
.pen-card { background:#fff; border-radius:16px; border:1px solid var(--border); box-shadow:0 1px 2px rgba(15,27,51,.04), 0 8px 24px rgba(15,27,51,.05); padding:24px; }
.pen-card-head { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:14px; flex-wrap:wrap; }
.pen-card-title { font-size:16px; font-weight:700; color:var(--ink); }
.pen-card-sub { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:4px; font-size:13px; color:var(--ink-2); }
.pen-sev-pill, .pen-assign-badge, .pen-status-pill { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; white-space:nowrap; }
.pen-sev-pill.red, .pen-status-pill.red { background:var(--red-100); color:var(--red-500); }
.pen-sev-pill.yellow, .pen-assign-badge.yellow, .pen-status-pill.yellow { background:var(--yellow-100); color:var(--yellow-800); }
.pen-sev-pill.teal, .pen-assign-badge.teal, .pen-status-pill.teal { background:var(--teal-100); color:var(--teal-700); }
.pen-status-pill.gray { background:var(--bg-soft); color:var(--ink-2); }
.pen-status-pill.blue { background:#E6F0FC; color:#1D4ED8; }
.pen-status-pill.orange { background:var(--orange-50); color:var(--orange-600); }
.pen-banner-warn { display:flex; align-items:center; gap:8px; padding:10px 14px; border-radius:12px; background:var(--yellow-100); color:var(--yellow-800); font-size:13px; font-weight:600; margin-bottom:12px; }
.pen-komunitas-list { display:flex; flex-direction:column; border-top:1px solid var(--border); }
.pen-kom-row { display:grid; grid-template-columns:minmax(0,1fr) 130px 130px; gap:12px; align-items:center; padding:14px 12px; border-bottom:1px solid var(--border); transition:background .15s; }
.pen-kom-row:last-child { border-bottom:0; }
.pen-kom-row:hover { background:var(--bg-soft); }
.pen-kom-row.is-best { background:var(--orange-50); border-left:3px solid var(--orange-500); }
.pen-kom-name-row { display:flex; align-items:center; gap:8px; flex-wrap:wrap; font-size:14px; color:var(--ink); }
.pen-best-pill { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:999px; background:var(--orange-500); color:#fff; font-size:11px; font-weight:700; }
.pen-best-pill.teal { background:var(--teal-500); }
.pen-kom-meta { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-top:4px; font-size:13px; color:var(--ink-2); }
.pen-kom-meta span { display:inline-flex; align-items:center; gap:4px; }
.pen-kom-meta [data-lucide] { width:14px; height:14px; color:var(--ink-3); }
.pen-kom-action { text-align:right; }
.pen-btn-orange { background:var(--orange-500); color:#fff; box-shadow:0 4px 14px rgba(255,107,53,.28); }
.pen-btn-orange:hover { background:var(--orange-600); }
.pen-btn-disabled { background:var(--bg-soft) !important; color:var(--ink-3) !important; cursor:not-allowed !important; opacity:1 !important; pointer-events:auto !important; box-shadow:none !important; }
.pen-btn-disabled:hover { transform:none !important; }
.pen-btn-batal-trigger:hover { border-color:var(--red-500); color:var(--red-500); }
.pen-tasks-head { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:14px; flex-wrap:wrap; }
.pen-stage-select { height:34px; padding:0 30px 0 12px !important; border-radius:10px; border:1px solid var(--border); background:#fff; font-size:12.5px; font-weight:600; color:var(--ink); }
.pen-table th { font-size:11px; letter-spacing:0.08em; color:var(--ink-3); }
.pen-empty-card { padding:48px 24px; text-align:center; background:#fff; border:1px solid var(--border); border-radius:16px; display:flex; flex-direction:column; align-items:center; gap:8px; color:var(--ink-2); }
.pen-empty-card [data-lucide] { width:36px; height:36px; color:var(--ink-3); }
.pen-empty-inline { padding:18px; color:var(--ink-2); font-size:13.5px; }

/* Modals */
.pen-modal-backdrop { position:fixed; inset:0; z-index:200; background:rgba(11,27,51,.55); backdrop-filter:blur(4px); display:grid; place-items:center; padding:16px; animation:penFade .15s ease; }
.pen-modal-backdrop[hidden] { display:none; }
.pen-modal { width:min(410px,100%); background:#fff; border-radius:20px; padding:22px; box-shadow:0 24px 60px rgba(11,27,51,.22); animation:penScale .15s ease; }
@keyframes penFade { from{opacity:0} to{opacity:1} }
@keyframes penScale { from{opacity:0;transform:scale(.96)} to{opacity:1;transform:scale(1)} }
.pen-modal-icon { width:44px; height:44px; border-radius:50%; display:grid; place-items:center; margin-bottom:14px; }
.pen-modal-icon.orange { background:var(--orange-50); color:var(--orange-500); }
.pen-modal-icon.yellow { background:var(--yellow-100); color:var(--yellow-800); }
.pen-modal-icon.red { background:var(--red-100); color:var(--red-500); }
.pen-modal-title { font-size:18px; font-weight:800; color:var(--ink); }
.pen-modal-desc { font-size:13px; color:var(--ink-2); margin-top:4px; }
.pen-modal-summary { margin-top:14px; padding:12px; border-radius:12px; background:var(--bg-soft); display:flex; flex-direction:column; gap:6px; }
.pen-summary-row { display:flex; justify-content:space-between; gap:10px; font-size:13px; }
.pen-summary-row span { color:var(--ink-2); }
.pen-summary-row strong { color:var(--ink); text-align:right; }
.pen-stepper-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:14px; }
.pen-stepper { display:flex; align-items:center; gap:6px; }
.pen-stepper input { text-align:center; height:40px; padding:0 8px; }
.pen-step-btn { width:36px; height:40px; border-radius:10px; border:1px solid var(--border); background:#fff; font-weight:800; font-size:16px; color:var(--ink); flex:none; }
.pen-step-btn:hover { background:var(--bg-soft); }
.pen-modal-error { margin-top:10px; padding:10px 12px; border-radius:10px; background:var(--red-100); color:var(--red-500); font-size:12.5px; font-weight:600; }
.pen-modal-actions { display:flex; justify-content:flex-end; gap:8px; margin-top:18px; }
.pen-toast { position:fixed; right:24px; bottom:24px; z-index:300; padding:12px 18px; border-radius:12px; background:var(--ink); color:#fff; font-size:13.5px; font-weight:700; box-shadow:0 12px 28px rgba(11,27,51,.25); }

@media (max-width: 720px) {
    .pen-kom-row { grid-template-columns:1fr; gap:8px; }
    .pen-kom-action { text-align:left; }
    .pen-stepper-grid { grid-template-columns:1fr; }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let activeTriggerEl = null;

    const openModal = (id, trigger) => {
        const modal = document.getElementById(id);
        if (!modal) return;
        activeTriggerEl = trigger || document.activeElement;
        modal.hidden = false;
        modal.querySelector('input, select, textarea, button:not([disabled])')?.focus();
    };

    const closeModal = (modal) => {
        if (!modal) return;
        modal.hidden = true;
        activeTriggerEl?.focus();
    };

    document.querySelectorAll('.pen-modal-backdrop').forEach((backdrop) => {
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop || e.target.closest('[data-modal-close]')) {
                closeModal(backdrop);
            }
        });
    });

    document.addEventListener('keydown', (e) => {
        const open = document.querySelector('.pen-modal-backdrop:not([hidden])');
        if (e.key === 'Escape' && open) closeModal(open);
    });

    // Stepper buttons
    document.querySelectorAll('.pen-step-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.stepTarget);
            if (!input) return;
            const min = Number(input.min || 0);
            const max = Number(input.max || 999);
            const next = Math.min(max, Math.max(min, Number(input.value || 0) + Number(btn.dataset.step)));
            input.value = String(next);
        });
    });

    const submitFetch = async ({ url, method, body, btn, errorEl, modalId }) => {
        btn.disabled = true;
        errorEl.hidden = true;
        try {
            const res = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(body || {}),
            });
            if (res.status === 419) throw new Error('Sesi berakhir. Muat ulang halaman lalu coba lagi.');
            const json = await res.json().catch(() => ({}));
            if (!res.ok) {
                const firstErr = json?.errors ? Object.values(json.errors).flat()[0] : null;
                throw new Error(firstErr || json?.message || 'Gagal memproses penugasan.');
            }
            closeModal(document.getElementById(modalId));
            window.location.reload();
        } catch (err) {
            errorEl.textContent = err.message;
            errorEl.hidden = false;
            btn.disabled = false;
        }
    };

    // Modal Tugaskan
    let tugaskanUrl = '';
    let tugaskanKomunitasId = 0;
    const mtugCat = document.getElementById('mtug-catatan');
    const mtugCounter = document.getElementById('mtug-counter');
    mtugCat?.addEventListener('input', () => {
        if (mtugCounter) mtugCounter.textContent = `${mtugCat.value.length}/500`;
    });

    document.querySelectorAll('.btn-open-tugaskan').forEach((btn) => {
        btn.addEventListener('click', () => {
            tugaskanUrl = btn.dataset.storeUrl;
            tugaskanKomunitasId = Number(btn.dataset.komunitasId);
            const sisaKap = Number(btn.dataset.sisaKapasitas || 1);
            const sisaPerahu = Number(btn.dataset.sisaPerahu || 0);

            document.getElementById('mtug-komunitas-title').textContent = btn.dataset.komunitasNama;
            document.getElementById('mtug-laporan').textContent = btn.dataset.laporanLabel;
            document.getElementById('mtug-komunitas').textContent = btn.dataset.komunitasNama;
            document.getElementById('mtug-jarak').textContent = btn.dataset.jarakEta;
            document.getElementById('mtug-kapasitas').textContent = `${sisaKap} personel · ${sisaPerahu} perahu`;

            const pIn = document.getElementById('mtug-personel');
            pIn.max = String(sisaKap);
            pIn.value = String(Math.min(Math.max(1, sisaKap), 5));
            document.getElementById('mtug-personel-max').textContent = `(maks ${sisaKap})`;

            const prIn = document.getElementById('mtug-perahu');
            prIn.max = String(sisaPerahu);
            prIn.value = String(sisaPerahu > 0 ? 1 : 0);
            document.getElementById('mtug-perahu-max').textContent = `(maks ${sisaPerahu})`;

            mtugCat.value = '';
            if (mtugCounter) mtugCounter.textContent = '0/500';
            document.getElementById('mtug-error').hidden = true;
            document.getElementById('mtug-submit').disabled = false;
            openModal('modal-tugaskan', btn);
        });
    });

    document.getElementById('mtug-submit')?.addEventListener('click', (e) => {
        submitFetch({
            url: tugaskanUrl,
            method: 'POST',
            body: {
                komunitas_relawan_id: tugaskanKomunitasId,
                jumlah_personil_tugas: Number(document.getElementById('mtug-personel').value),
                armada_perahu_tugas: Number(document.getElementById('mtug-perahu').value),
                catatan: mtugCat.value.trim() || null,
            },
            btn: e.currentTarget,
            errorEl: document.getElementById('mtug-error'),
            modalId: 'modal-tugaskan',
        });
    });

    // Modal Ganti
    let gantiUrl = '';
    document.querySelectorAll('.btn-open-ganti').forEach((btn) => {
        btn.addEventListener('click', () => {
            gantiUrl = btn.dataset.gantiUrl;
            document.getElementById('mg-kode').textContent = btn.dataset.kode;
            document.getElementById('mg-personel').value = btn.dataset.personel || 1;
            document.getElementById('mg-perahu').value = btn.dataset.perahu || 0;
            document.getElementById('mg-catatan').value = '';
            document.getElementById('mg-error').hidden = true;

            const select = document.getElementById('mg-komunitas');
            const opsi = JSON.parse(btn.dataset.opsi || '[]');
            select.innerHTML = '';
            if (!opsi.length) {
                select.innerHTML = '<option value="">Tidak ada komunitas siaga lain</option>';
                document.getElementById('mg-submit').disabled = true;
            } else {
                opsi.forEach((o) => {
                    const opt = document.createElement('option');
                    opt.value = o.komunitas_id;
                    opt.textContent = `${o.nama} (${o.jarak_km} km · sisa ${o.sisa_kapasitas})`;
                    select.appendChild(opt);
                });
                document.getElementById('mg-submit').disabled = false;
            }
            openModal('modal-ganti', btn);
        });
    });

    document.getElementById('mg-submit')?.addEventListener('click', (e) => {
        submitFetch({
            url: gantiUrl,
            method: 'PATCH',
            body: {
                komunitas_relawan_id: Number(document.getElementById('mg-komunitas').value),
                jumlah_personil_tugas: Number(document.getElementById('mg-personel').value),
                armada_perahu_tugas: Number(document.getElementById('mg-perahu').value),
                catatan: document.getElementById('mg-catatan').value.trim() || null,
            },
            btn: e.currentTarget,
            errorEl: document.getElementById('mg-error'),
            modalId: 'modal-ganti',
        });
    });

    // Modal Batalkan
    let batalUrl = '';
    document.querySelectorAll('.btn-open-batalkan').forEach((btn) => {
        btn.addEventListener('click', () => {
            batalUrl = btn.dataset.batalUrl;
            document.getElementById('mb-kode').textContent = btn.dataset.kode;
            document.getElementById('mb-alasan').value = '';
            document.getElementById('mb-error').hidden = true;
            document.getElementById('mb-submit').disabled = false;
            openModal('modal-batalkan', btn);
        });
    });

    document.getElementById('mb-submit')?.addEventListener('click', (e) => {
        submitFetch({
            url: batalUrl,
            method: 'DELETE',
            body: {
                alasan: document.getElementById('mb-alasan').value.trim() || null,
            },
            btn: e.currentTarget,
            errorEl: document.getElementById('mb-error'),
            modalId: 'modal-batalkan',
        });
    });
});
</script>
@endpush
