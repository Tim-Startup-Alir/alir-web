@extends('layouts.admin')

@section('title', 'Verifikasi Laporan')
@section('topbar_title', 'Verifikasi Laporan')
@section('topbar_subtitle', 'Periksa bukti, lihat laporan di sekitar, lalu tentukan keputusan.')

@section('content')
    <div class="verif-topbar-extras">
        <form method="GET" action="{{ route('admin.laporan.verifikasi.index') }}" class="verif-top-search">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="hidden" name="sort" value="{{ $sort }}">
            <i data-lucide="search"></i>
            <input type="search" name="q" value="{{ $search }}" placeholder="Cari laporan, relawan, donasi…" aria-label="Cari laporan" dusk="verif-search-input">
        </form>
        <div class="row wrapx" style="gap:8px;margin-left:auto">
            <button type="button" class="btn btn-ghost btn-sm" title="Segera hadir"><i data-lucide="file-text"></i>Ekspor PDF</button>
            <button type="button" class="btn btn-ghost btn-sm" title="Segera hadir"><i data-lucide="table"></i>Ekspor Excel</button>
        </div>
    </div>

    <div class="pg-h">
        <div>
            <span class="kicker">LAPORAN</span>
            <h1 class="verif-h1">Verifikasi Laporan</h1>
            <p class="muted" style="margin-top:4px;font-size:14px">Periksa bukti, lihat laporan di sekitar, lalu tentukan keputusan.</p>
        </div>
    </div>

    <div class="verif-layout" id="verif-app"
         data-tab="{{ $tab }}"
         data-detail-base="{{ url('/admin/laporan/verifikasi') }}">

        {{-- KOLOM KIRI: DAFTAR ANTREAN --}}
        <aside class="verif-queue-col" aria-label="Daftar antrean laporan">
            <div class="verif-tabs" role="tablist" aria-label="Filter status laporan">
                <a href="{{ route('admin.laporan.verifikasi.index', ['tab' => 'menunggu_verifikasi', 'q' => $search, 'sort' => $sort]) }}"
                   class="verif-tab {{ $tab === 'menunggu_verifikasi' ? 'is-active' : '' }}" role="tab" aria-selected="{{ $tab === 'menunggu_verifikasi' ? 'true' : 'false' }}" dusk="tab-menunggu">
                    Menunggu <span class="verif-tab-count" id="count-menunggu">{{ $counts['menunggu_verifikasi'] }}</span>
                </a>
                <a href="{{ route('admin.laporan.verifikasi.index', ['tab' => 'terverifikasi', 'q' => $search, 'sort' => $sort]) }}"
                   class="verif-tab {{ $tab === 'terverifikasi' ? 'is-active' : '' }}" role="tab" aria-selected="{{ $tab === 'terverifikasi' ? 'true' : 'false' }}" dusk="tab-terverifikasi">
                    Terverifikasi <span class="verif-tab-count" id="count-terverifikasi">{{ $counts['terverifikasi'] }}</span>
                </a>
                <a href="{{ route('admin.laporan.verifikasi.index', ['tab' => 'ditolak', 'q' => $search, 'sort' => $sort]) }}"
                   class="verif-tab {{ $tab === 'ditolak' ? 'is-active' : '' }}" role="tab" aria-selected="{{ $tab === 'ditolak' ? 'true' : 'false' }}" dusk="tab-ditolak">
                    Ditolak <span class="verif-tab-count" id="count-ditolak">{{ $counts['ditolak'] }}</span>
                </a>
            </div>

            <form method="GET" action="{{ route('admin.laporan.verifikasi.index') }}" class="verif-filter-row">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <input type="hidden" name="q" value="{{ $search }}">
                <label class="xs muted" for="verif-sort">Urutkan:</label>
                <select id="verif-sort" name="sort" class="verif-sort-select" onchange="this.form.submit()" aria-label="Urutkan laporan">
                    <option value="terbaru" {{ $sort === 'terbaru' ? 'selected' : '' }}>Terbaru</option>
                    <option value="keparahan" {{ $sort === 'keparahan' ? 'selected' : '' }}>Keparahan tertinggi</option>
                    <option value="kepercayaan" {{ $sort === 'kepercayaan' ? 'selected' : '' }}>Kepercayaan tertinggi</option>
                </select>
            </form>

            <div class="verif-cards" id="verif-card-list" dusk="verif-card-list">
                @forelse ($laporans as $item)
                    @php
                        $sevColor = match($item->severity) {
                            'berat' => 'red',
                            'sedang' => 'yellow',
                            default => 'teal',
                        };
                        $sevLabel = ucfirst($item->severity);
                    @endphp
                    <button type="button"
                            class="verif-card {{ $selectedId === $item->id ? 'is-active' : '' }}"
                            data-report-id="{{ $item->id }}"
                            data-show-url="{{ route('admin.laporan.verifikasi.show', $item) }}"
                            dusk="verif-card-{{ $item->id }}">
                        <span class="verif-dot {{ $sevColor }}" aria-label="Keparahan {{ $sevLabel }}" title="Keparahan: {{ $sevLabel }}"></span>
                        <span class="verif-card-body">
                            <span class="verif-card-title">{{ $item->kode_pelacakan }} &middot; {{ $item->wilayah?->nama_kelurahan ?? $item->alamat_singkat }}</span>
                            <span class="verif-card-meta">
                                {{ $item->jumlah_warga }} warga &middot; {{ $item->created_at?->diffForHumans() }} &middot; <span class="verif-sev-txt {{ $sevColor }}">{{ $sevLabel }}</span>
                            </span>
                        </span>
                    </button>
                @empty
                    <div class="verif-empty-queue" id="verif-empty-queue" dusk="verif-empty-state">
                        <i data-lucide="inbox"></i>
                        <p>Semua laporan sudah diverifikasi</p>
                        <span class="xs muted">Tidak ada laporan pada kategori ini.</span>
                    </div>
                @endforelse
            </div>

            @if ($laporans->hasPages())
                <div class="verif-pagination" dusk="verif-pagination">
                    {{ $laporans->links() }}
                </div>
            @endif
        </aside>

        {{-- KOLOM KANAN: PANEL DETAIL --}}
        <section class="verif-detail-col" id="verif-detail-panel" aria-live="polite" dusk="verif-detail-panel">
            @if ($detailPayload)
                <div id="detail-populated">
                    {{-- Header Detail --}}
                    <div class="verif-detail-head">
                        <div>
                            <span class="kicker" id="d-kode">{{ $detailPayload['kode'] }}</span>
                            <h2 class="verif-detail-title" id="d-kelurahan">{{ $detailPayload['kelurahan'] }}</h2>
                            <p class="verif-detail-meta" id="d-meta">
                                {{ $detailPayload['waktu_relatif'] }} &middot; {{ $detailPayload['alamat_singkat'] }}
                                @if ($detailPayload['catatan'])
                                    &middot; {{ $detailPayload['catatan'] }}
                                @endif
                            </p>
                        </div>
                        <span class="verif-sev-pill" id="d-severity-pill"></span>
                    </div>

                    {{-- Baris Media: Foto Bukti & Peta Leaflet --}}
                    <div class="verif-media-grid">
                        <div class="verif-photo-box" id="d-photo-box">
                            <div class="verif-skeleton" id="d-photo-skeleton" hidden></div>
                            <img id="d-photo-img" src="" alt="Foto bukti banjir" hidden>
                            <div class="verif-photo-empty" id="d-photo-empty" hidden>
                                <i data-lucide="image-off"></i>
                                <span>Tidak ada foto terlampir</span>
                            </div>
                            <div class="verif-photo-error" id="d-photo-error" hidden>
                                <span>Gagal memuat foto.</span>
                                <button type="button" class="btn btn-ghost btn-sm" id="d-photo-retry">Coba lagi</button>
                            </div>
                            <span class="verif-photo-time" id="d-photo-time" hidden></span>
                            <div class="verif-photo-nav" id="d-photo-nav" hidden>
                                <button type="button" id="d-photo-prev" aria-label="Foto sebelumnya"><i data-lucide="chevron-left"></i></button>
                                <span id="d-photo-counter" class="mono xs">1/1</span>
                                <button type="button" id="d-photo-next" aria-label="Foto berikutnya"><i data-lucide="chevron-right"></i></button>
                            </div>
                        </div>

                        <div class="verif-map-box">
                            <div id="verif-map" class="verif-map" role="application" aria-label="Peta lokasi laporan banjir" dusk="verif-map"></div>
                        </div>
                    </div>

                    {{-- Baris Kartu Info: Tingkat Kepercayaan & Data Pelapor --}}
                    <div class="verif-info-grid">
                        <div class="verif-info-card">
                            <div class="verif-conf-wrap">
                                <div class="verif-ring-wrap">
                                    <svg viewBox="0 0 100 100" class="verif-ring-svg" aria-hidden="true">
                                        <circle cx="50" cy="50" r="42" class="verif-ring-track"></circle>
                                        <circle cx="50" cy="50" r="42" class="verif-ring-prog" id="d-ring-prog"></circle>
                                    </svg>
                                    <div class="verif-ring-center">
                                        <strong class="mono" id="d-ring-value">1/3</strong>
                                        <span>independen</span>
                                    </div>
                                </div>
                                <div class="verif-conf-details">
                                    <span class="verif-conf-badge" id="d-conf-badge">Kepercayaan Rendah</span>
                                    <div class="verif-conf-rows" id="d-conf-rows"></div>
                                </div>
                            </div>
                        </div>

                        <div class="verif-info-card verif-reporter-card">
                            <dl class="verif-dl">
                                <div><dt>Pelapor</dt><dd id="d-pelapor">—</dd></div>
                                <div><dt>Warga terdampak</dt><dd class="mono" id="d-warga">—</dd></div>
                                <div><dt>Laporan 500 m</dt><dd class="mono" id="d-sekitar-count">—</dd></div>
                                <div><dt>WhatsApp</dt><dd class="mono" id="d-wa">—</dd></div>
                                <div>
                                    <dt>Koordinat</dt>
                                    <dd>
                                        <button type="button" class="verif-copy-btn mono" id="d-coords-btn" title="Klik untuk salin koordinat">
                                            <span id="d-coords">—</span>
                                            <i data-lucide="copy"></i>
                                        </button>
                                    </dd>
                                </div>
                            </dl>
                            <div class="verif-wa-pill">
                                <i data-lucide="check-circle-2"></i> Terkirim via WhatsApp
                            </div>
                        </div>
                    </div>

                    {{-- Action Bar --}}
                    <div class="verif-action-bar" id="verif-action-bar">
                        <button type="button" class="btn btn-teal btn-sm" id="btn-open-valid" title="Pintasan keyboard: V" dusk="btn-valid">
                            <i data-lucide="check"></i> Valid
                        </button>
                        <button type="button" class="btn btn-ghost btn-sm verif-btn-tolak-trigger" id="btn-open-tolak" title="Pintasan keyboard: T" dusk="btn-tidak-valid">
                            <i data-lucide="x"></i> Tidak valid
                        </button>
                        <button type="button" class="btn btn-ghost btn-sm" id="btn-open-duplikat" title="Pintasan keyboard: D" dusk="btn-duplikat">
                            <i data-lucide="copy"></i> Duplikat
                        </button>
                        <span class="xs muted verif-shortcut-hint">Pintasan: <kbd>V</kbd> Valid &middot; <kbd>T</kbd> Tolak &middot; <kbd>D</kbd> Duplikat</span>
                    </div>
                </div>
            @else
                <div class="verif-empty-detail">
                    <i data-lucide="shield-check"></i>
                    <h3>Belum ada laporan yang dipilih</h3>
                    <p class="muted">Semua laporan pada antrean ini sudah diproses atau belum ada data.</p>
                </div>
            @endif
        </section>
    </div>

    {{-- Lightbox Foto --}}
    <div class="verif-modal-backdrop" id="modal-lightbox" role="dialog" aria-modal="true" aria-label="Pratinjau foto" hidden>
        <div class="verif-lightbox">
            <button type="button" class="verif-lightbox-close" data-modal-close aria-label="Tutup pratinjau"><i data-lucide="x"></i></button>
            <img id="lightbox-img" src="" alt="Foto bukti ukuran penuh">
        </div>
    </div>

    {{-- Toast Notification --}}
    <div class="verif-toast" id="verif-toast" role="status" aria-live="polite" hidden></div>

    @include('admin.laporan._modal_valid')
    @include('admin.laporan._modal_tidak_valid')
    @include('admin.laporan._modal_duplikat')
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
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
    --bg-soft: #F1F4F9;
    --border: #E6E9F0;
}
.verif-topbar-extras { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.verif-top-search { position:relative; width:min(340px,100%); }
.verif-top-search [data-lucide] { position:absolute; left:14px; top:50%; transform:translateY(-50%); width:16px; height:16px; color:var(--ink-3); }
.verif-top-search input { width:100%; height:40px; padding:0 16px 0 40px; border-radius:999px; border:1px solid var(--border); background:#fff; font-size:13.5px; outline:none; }
.verif-top-search input:focus { border-color:var(--orange-500); box-shadow:0 0 0 3px rgba(255,107,53,.15); }
.verif-h1 { font-size:32px; font-weight:800; letter-spacing:-0.02em; color:var(--ink); margin-top:4px; line-height:1.15; }
.verif-layout { display:grid; grid-template-columns:320px minmax(0,1fr); gap:20px; align-items:start; }
.verif-queue-col { display:flex; flex-direction:column; gap:12px; }
.verif-tabs { display:grid; grid-template-columns:repeat(3,1fr); gap:4px; padding:4px; border-radius:999px; background:var(--bg-soft); border:1px solid var(--border); }
.verif-tab { display:flex; align-items:center; justify-content:center; gap:5px; padding:7px 8px; border-radius:999px; font-size:12px; font-weight:700; color:var(--ink-2); transition:all .2s; white-space:nowrap; }
.verif-tab.is-active { background:#fff; color:var(--ink); box-shadow:0 1px 3px rgba(15,27,51,.08); }
.verif-tab-count { font-size:11px; color:var(--ink-3); font-variant-numeric:tabular-nums; }
.verif-tab.is-active .verif-tab-count { color:var(--orange-500); }
.verif-filter-row { display:flex; align-items:center; justify-content:space-between; gap:8px; }
.verif-sort-select { height:34px; padding:0 30px 0 12px !important; border-radius:10px; border:1px solid var(--border); background:#fff; font-size:12.5px; font-weight:600; color:var(--ink); }
.verif-cards { display:flex; flex-direction:column; gap:10px; }
.verif-card { display:flex; align-items:flex-start; gap:10px; width:100%; text-align:left; padding:14px; border-radius:14px; background:#fff; border:1px solid var(--border); transition:transform .15s, box-shadow .15s, border-color .15s, background .15s; }
.verif-card:hover { transform:translateY(-1px); box-shadow:0 6px 16px rgba(15,27,51,.06); }
.verif-card.is-active { background:var(--orange-50); border:1.5px solid var(--orange-200); }
.verif-card:focus-visible { outline:2px solid var(--orange-500); outline-offset:2px; }
.verif-dot { width:10px; height:10px; border-radius:50%; margin-top:5px; flex:none; }
.verif-dot.red { background:var(--red-500); }
.verif-dot.yellow { background:var(--yellow-400); }
.verif-dot.teal { background:var(--teal-500); }
.verif-card-body { display:flex; flex-direction:column; gap:3px; min-width:0; }
.verif-card-title { font-size:14px; font-weight:700; color:var(--ink); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.verif-card-meta { font-size:12px; color:var(--ink-2); font-variant-numeric:tabular-nums; }
.verif-sev-txt.red { color:var(--red-500); font-weight:700; }
.verif-sev-txt.yellow { color:#B45309; font-weight:700; }
.verif-sev-txt.teal { color:var(--teal-700); font-weight:700; }
.verif-empty-queue, .verif-empty-detail { padding:48px 24px; text-align:center; background:#fff; border:1px solid var(--border); border-radius:16px; display:flex; flex-direction:column; align-items:center; gap:8px; color:var(--ink-2); }
.verif-empty-queue [data-lucide], .verif-empty-detail [data-lucide] { width:36px; height:36px; color:var(--ink-3); }
.verif-detail-col { background:#fff; border-radius:16px; border:1px solid var(--border); box-shadow:0 1px 2px rgba(15,27,51,.04), 0 8px 24px rgba(15,27,51,.05); padding:20px; }
.verif-detail-head { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:16px; }
.verif-detail-title { font-size:24px; font-weight:700; color:var(--ink); margin-top:2px; }
.verif-detail-meta { font-size:13px; color:var(--ink-2); margin-top:4px; }
.verif-sev-pill { display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:999px; font-size:12.5px; font-weight:700; flex:none; }
.verif-sev-pill.red { background:var(--red-100); color:var(--red-500); }
.verif-sev-pill.yellow { background:var(--yellow-100); color:#92400E; }
.verif-sev-pill.teal { background:var(--teal-100); color:var(--teal-700); }
.verif-media-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
.verif-photo-box, .verif-map-box { height:420px; border-radius:16px; overflow:hidden; position:relative; border:1px solid var(--border); background:var(--bg-soft); }
.verif-photo-box img { width:100%; height:100%; object-fit:cover; cursor:zoom-in; }
.verif-skeleton { position:absolute; inset:0; background:linear-gradient(90deg,#eef2f7 25%,#e2e8f0 50%,#eef2f7 75%); background-size:200% 100%; animation:verifShimmer 1.3s infinite; }
@keyframes verifShimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
.verif-photo-empty, .verif-photo-error { position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; color:var(--ink-2); font-size:13px; }
.verif-photo-time { position:absolute; left:12px; bottom:12px; padding:4px 10px; border-radius:8px; background:rgba(15,27,51,.72); color:#FDE047; font-size:12px; font-weight:700; font-variant-numeric:tabular-nums; }
.verif-photo-nav { position:absolute; right:12px; bottom:12px; display:flex; align-items:center; gap:6px; padding:4px 8px; border-radius:999px; background:rgba(15,27,51,.75); color:#fff; }
.verif-photo-nav button { width:24px; height:24px; display:grid; place-items:center; border-radius:50%; color:#fff; }
.verif-photo-nav button:hover { background:rgba(255,255,255,.2); }
.verif-map { width:100%; height:100%; z-index:0; }
.verif-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
.verif-info-card { border-radius:12px; border:1px solid var(--border); padding:16px; background:#fff; }
.verif-reporter-card { background:var(--bg-soft); border-color:transparent; display:flex; flex-direction:column; justify-content:space-between; gap:12px; }
.verif-conf-wrap { display:flex; align-items:center; gap:18px; }
.verif-ring-wrap { position:relative; width:96px; height:96px; flex:none; }
.verif-ring-svg { width:96px; height:96px; transform:rotate(-90deg); }
.verif-ring-track { fill:none; stroke:#E6E9F0; stroke-width:8; }
.verif-ring-prog { fill:none; stroke:var(--teal-500); stroke-width:8; stroke-linecap:round; stroke-dasharray:263.89; stroke-dashoffset:263.89; transition:stroke-dashoffset .6s ease, stroke .3s; }
.verif-ring-center { position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; }
.verif-ring-center strong { font-size:20px; font-weight:800; color:var(--ink); line-height:1; }
.verif-ring-center span { font-size:10px; color:var(--ink-3); font-weight:600; margin-top:2px; }
.verif-conf-details { flex:1; min-width:0; display:flex; flex-direction:column; gap:8px; }
.verif-conf-badge { display:inline-flex; align-self:flex-start; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; }
.verif-conf-badge.tinggi { background:var(--teal-100); color:var(--teal-700); }
.verif-conf-badge.sedang { background:var(--yellow-100); color:#92400E; }
.verif-conf-badge.rendah { background:var(--red-100); color:var(--red-500); }
.verif-conf-rows { display:flex; flex-direction:column; gap:6px; }
.verif-conf-row { display:flex; justify-content:space-between; align-items:center; font-size:12px; gap:8px; }
.verif-conf-row span { color:var(--ink-2); }
.verif-conf-row strong { color:var(--ink); font-variant-numeric:tabular-nums; }
.verif-dl { display:flex; flex-direction:column; gap:8px; margin:0; }
.verif-dl > div { display:flex; justify-content:space-between; align-items:center; gap:12px; font-size:13px; }
.verif-dl dt { color:var(--ink-2); }
.verif-dl dd { margin:0; font-weight:700; color:var(--ink); text-align:right; }
.verif-copy-btn { display:inline-flex; align-items:center; gap:6px; padding:2px 6px; border-radius:6px; font-weight:700; color:var(--ink); }
.verif-copy-btn:hover { background:rgba(15,27,51,.06); }
.verif-wa-pill { display:flex; align-items:center; justify-content:center; gap:6px; width:100%; height:36px; border-radius:999px; border:1px solid var(--border); background:#fff; font-size:12.5px; font-weight:700; color:var(--teal-700); }
.verif-action-bar { position:sticky; bottom:12px; z-index:10; display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding-top:12px; border-top:1px solid var(--border); background:#fff; }
.verif-btn-tolak-trigger:hover, .verif-btn-tolak-trigger:focus-visible { border-color:var(--red-500); color:var(--red-500); }
.verif-shortcut-hint { margin-left:auto; }
.verif-shortcut-hint kbd { padding:2px 6px; border-radius:6px; background:var(--bg-soft); border:1px solid var(--border); font-family:inherit; font-weight:700; }

/* Modals */
.verif-modal-backdrop { position:fixed; inset:0; z-index:200; background:rgba(11,27,51,.55); backdrop-filter:blur(4px); display:grid; place-items:center; padding:16px; animation:verifFade .15s ease; }
.verif-modal-backdrop[hidden] { display:none; }
.verif-modal { width:min(380px,100%); background:#fff; border-radius:20px; padding:22px; box-shadow:0 24px 60px rgba(11,27,51,.22); animation:verifScale .15s ease; }
@keyframes verifFade { from{opacity:0} to{opacity:1} }
@keyframes verifScale { from{opacity:0;transform:scale(.96)} to{opacity:1;transform:scale(1)} }
.verif-modal-icon { width:44px; height:44px; border-radius:50%; display:grid; place-items:center; margin-bottom:14px; }
.verif-modal-icon.teal { background:var(--teal-100); color:var(--teal-700); }
.verif-modal-icon.red { background:var(--red-100); color:var(--red-500); }
.verif-modal-icon.yellow { background:var(--yellow-100); color:#B45309; }
.verif-modal-title { font-size:18px; font-weight:800; color:var(--ink); }
.verif-modal-desc { font-size:13px; color:var(--ink-2); margin-top:4px; }
.verif-modal-summary { margin-top:14px; padding:12px; border-radius:12px; background:var(--bg-soft); display:flex; flex-direction:column; gap:6px; }
.verif-summary-row { display:flex; justify-content:space-between; gap:10px; font-size:13px; }
.verif-summary-row span { color:var(--ink-2); }
.verif-summary-row strong { color:var(--ink); text-align:right; }
.verif-chips { display:flex; flex-wrap:wrap; gap:6px; margin-top:12px; }
.verif-chip { padding:5px 10px; border-radius:999px; border:1px solid var(--border); background:#fff; font-size:12px; font-weight:600; color:var(--ink-2); transition:all .15s; }
.verif-chip:hover { border-color:var(--orange-500); color:var(--orange-600); background:var(--orange-50); }
.verif-modal-error { margin-top:10px; padding:10px 12px; border-radius:10px; background:var(--red-100); color:var(--red-500); font-size:12.5px; font-weight:600; }
.verif-modal-actions { display:flex; justify-content:flex-end; gap:8px; margin-top:18px; }
.verif-btn-orange { background:var(--orange-500); color:#fff; box-shadow:0 6px 18px rgba(255,107,53,.35); }
.verif-btn-orange:hover { background:var(--orange-600); }
.verif-lightbox { position:relative; max-width:90vw; max-height:90vh; }
.verif-lightbox img { max-width:90vw; max-height:85vh; border-radius:16px; object-fit:contain; }
.verif-lightbox-close { position:absolute; top:-14px; right:-14px; width:36px; height:36px; border-radius:50%; background:#fff; color:var(--ink); display:grid; place-items:center; box-shadow:0 4px 12px rgba(0,0,0,.2); }
.verif-toast { position:fixed; right:24px; bottom:24px; z-index:300; padding:12px 18px; border-radius:12px; background:var(--ink); color:#fff; font-size:13.5px; font-weight:700; box-shadow:0 12px 28px rgba(11,27,51,.25); }

@media (max-width: 1080px) {
    .verif-layout { grid-template-columns:1fr; }
    .verif-media-grid, .verif-info-grid { grid-template-columns:1fr; }
    .verif-photo-box, .verif-map-box { height:320px; }
}
@media (max-width: 640px) {
    .verif-shortcut-hint { display:none; }
    .verif-action-bar { position:fixed; left:0; right:0; bottom:0; padding:12px 16px; box-shadow:0 -4px 18px rgba(11,27,51,.1); }
}
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('verif-app');
    if (!app) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let currentReport = @json($detailPayload);
    let currentPhotoIdx = 0;
    let map = null;
    let mapLayerGroup = null;
    let activeTriggerEl = null;

    const toastEl = document.getElementById('verif-toast');
    const showToast = (msg) => {
        if (!toastEl) return;
        toastEl.textContent = msg;
        toastEl.hidden = false;
        clearTimeout(toastEl._timer);
        toastEl._timer = setTimeout(() => { toastEl.hidden = true; }, 3200);
    };

    const setPhoto = (idx) => {
        const img = document.getElementById('d-photo-img');
        const empty = document.getElementById('d-photo-empty');
        const err = document.getElementById('d-photo-error');
        const skel = document.getElementById('d-photo-skeleton');
        const timeEl = document.getElementById('d-photo-time');
        const nav = document.getElementById('d-photo-nav');
        const counter = document.getElementById('d-photo-counter');
        if (!currentReport || !img) return;

        const fotos = currentReport.fotos || [];
        if (!fotos.length) {
            img.hidden = true; skel.hidden = true; err.hidden = true; timeEl.hidden = true; nav.hidden = true;
            empty.hidden = false;
            return;
        }

        currentPhotoIdx = (idx + fotos.length) % fotos.length;
        const foto = fotos[currentPhotoIdx];
        empty.hidden = true; err.hidden = true; skel.hidden = false; img.hidden = true;

        img.onload = () => { skel.hidden = true; img.hidden = false; };
        img.onerror = () => { skel.hidden = true; img.hidden = true; err.hidden = false; };
        img.src = foto.url;

        timeEl.textContent = foto.waktu || '';
        timeEl.hidden = !foto.waktu;

        if (fotos.length > 1) {
            nav.hidden = false;
            counter.textContent = `${currentPhotoIdx + 1}/${fotos.length}`;
        } else {
            nav.hidden = true;
        }
    };

    const renderMap = (data) => {
        const mapEl = document.getElementById('verif-map');
        if (!mapEl || !window.L || !data) return;

        if (!map) {
            map = L.map(mapEl).setView([data.latitude, data.longitude], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap',
            }).addTo(map);
            mapLayerGroup = L.layerGroup().addTo(map);
        } else {
            mapLayerGroup.clearLayers();
            map.setView([data.latitude, data.longitude], 15);
        }

        L.circle([data.latitude, data.longitude], {
            radius: data.radius_m || 500,
            color: '#FF6B35',
            weight: 2,
            dashArray: '6 6',
            fillColor: '#FF6B35',
            fillOpacity: 0.12,
        }).addTo(mapLayerGroup);

        L.circleMarker([data.latitude, data.longitude], {
            radius: 9,
            color: '#FFFFFF',
            weight: 3,
            fillColor: '#E5484D',
            fillOpacity: 1,
        }).bindPopup(`<strong>${data.kode}</strong><br>${data.kelurahan}`).addTo(mapLayerGroup);

        (data.sekitar || []).forEach((item) => {
            L.circleMarker([item.latitude, item.longitude], {
                radius: 6,
                color: '#FFFFFF',
                weight: 2,
                fillColor: '#64748B',
                fillOpacity: 0.9,
            }).bindPopup(`<strong>${item.kode}</strong> &middot; ${item.jarak_m} m<br>${item.kelurahan}`).addTo(mapLayerGroup);
        });

        setTimeout(() => map.invalidateSize(), 60);
    };

    const renderDetail = (data) => {
        currentReport = data;
        if (!data) return;

        document.getElementById('d-kode').textContent = data.kode;
        document.getElementById('d-kelurahan').textContent = data.kelurahan;
        document.getElementById('d-meta').textContent = `${data.waktu_relatif} · ${data.alamat_singkat}${data.catatan ? ' · ' + data.catatan : ''}`;

        const sevPill = document.getElementById('d-severity-pill');
        const sevMap = { berat: ['red', 'Berat'], sedang: ['yellow', 'Sedang'], ringan: ['teal', 'Ringan'] };
        const [sevCls, sevTxt] = sevMap[data.severity] || ['teal', 'Ringan'];
        sevPill.className = `verif-sev-pill ${sevCls}`;
        sevPill.innerHTML = `<i data-lucide="alert-circle"></i> ${sevTxt}`;

        setPhoto(0);
        renderMap(data);

        // Kepercayaan PBI-008
        const kep = data.kepercayaan || {};
        const level = kep.level || 'rendah';
        const indep = kep.jumlah_pelapor_independen ?? 1;
        const target = kep.ambang?.laporan_tinggi ?? 3;
        const ratio = Math.min(1, indep / Math.max(1, target));
        const circ = 263.89;
        const ring = document.getElementById('d-ring-prog');
        const ringColors = { tinggi: '#14B8A6', sedang: '#F5B82E', rendah: '#E5484D' };
        ring.style.stroke = ringColors[level] || '#E5484D';
        ring.style.strokeDashoffset = String(circ * (1 - ratio));
        document.getElementById('d-ring-value').textContent = `${Math.min(indep, target)}/${target}`;

        const badge = document.getElementById('d-conf-badge');
        badge.className = `verif-conf-badge ${level}`;
        badge.textContent = `Kepercayaan ${level.charAt(0).toUpperCase() + level.slice(1)}`;

        const rows = document.getElementById('d-conf-rows');
        const konfTxt = kep.konfirmasi ? `${kep.konfirmasi.hasil}` : 'Belum';
        rows.innerHTML = `
            <div class="verif-conf-row"><span>Konfirmasi lapangan</span><strong>${konfTxt}</strong></div>
            <div class="verif-conf-row"><span>Laporan independen (500 m, 24 jam)</span><strong>${indep}/${target}</strong></div>
            <div class="verif-conf-row"><span>Foto terlampir</span><strong>${(data.fotos || []).length} foto</strong></div>
            <div class="verif-conf-row"><span>Koordinat GPS</span><strong>${data.sumber_lokasi === 'pin' ? 'Pin Peta' : 'GPS Perangkat'}</strong></div>
        `;

        // Reporter
        document.getElementById('d-pelapor').textContent = data.nama_pelapor;
        document.getElementById('d-warga').textContent = `${data.jumlah_warga} warga`;
        document.getElementById('d-sekitar-count').textContent = `${data.jumlah_sekitar} laporan`;
        document.getElementById('d-wa').textContent = data.nomor_wa_masked;
        document.getElementById('d-coords').textContent = `${Number(data.latitude).toFixed(4)}, ${Number(data.longitude).toFixed(4)}`;

        // Action bar state
        const isPending = data.status === 'menunggu_verifikasi';
        ['btn-open-valid', 'btn-open-tolak', 'btn-open-duplikat'].forEach((id) => {
            const btn = document.getElementById(id);
            if (btn) btn.disabled = !isPending;
        });

        window.alirIcons();
    };

    if (currentReport) {
        renderDetail(currentReport);
    }

    // Photo gallery controls
    document.getElementById('d-photo-prev')?.addEventListener('click', () => setPhoto(currentPhotoIdx - 1));
    document.getElementById('d-photo-next')?.addEventListener('click', () => setPhoto(currentPhotoIdx + 1));
    document.getElementById('d-photo-retry')?.addEventListener('click', () => setPhoto(currentPhotoIdx));
    document.getElementById('d-photo-img')?.addEventListener('click', () => {
        const lb = document.getElementById('modal-lightbox');
        const lbImg = document.getElementById('lightbox-img');
        if (lb && lbImg && currentReport?.fotos?.[currentPhotoIdx]) {
            lbImg.src = currentReport.fotos[currentPhotoIdx].url;
            lb.hidden = false;
        }
    });

    // Copy coordinates
    document.getElementById('d-coords-btn')?.addEventListener('click', async () => {
        if (!currentReport) return;
        const txt = `${currentReport.latitude}, ${currentReport.longitude}`;
        try {
            await navigator.clipboard.writeText(txt);
            showToast(`Koordinat ${txt} disalin`);
        } catch {
            showToast(txt);
        }
    });

    // Card click -> AJAX fetch detail
    document.getElementById('verif-card-list')?.addEventListener('click', async (e) => {
        const card = e.target.closest('.verif-card');
        if (!card) return;
        document.querySelectorAll('.verif-card').forEach((c) => c.classList.remove('is-active'));
        card.classList.add('is-active');

        try {
            const res = await fetch(card.dataset.showUrl, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Gagal memuat detail');
            const data = await res.json();
            renderDetail(data);
        } catch {
            showToast('Gagal memuat detail laporan.');
        }
    });

    // Modal helpers
    const openModal = (modalId, trigger) => {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        activeTriggerEl = trigger || document.activeElement;
        modal.hidden = false;
        const focusable = modal.querySelector('textarea, select, button:not([disabled])');
        focusable?.focus();
    };

    const closeModal = (modal) => {
        if (!modal) return;
        modal.hidden = true;
        activeTriggerEl?.focus();
    };

    document.querySelectorAll('.verif-modal-backdrop').forEach((backdrop) => {
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop || e.target.closest('[data-modal-close]')) {
                closeModal(backdrop);
            }
        });
    });

    document.addEventListener('keydown', (e) => {
        const openBackdrop = document.querySelector('.verif-modal-backdrop:not([hidden])');
        if (e.key === 'Escape' && openBackdrop) {
            closeModal(openBackdrop);
            return;
        }
        if (openBackdrop) return;
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) return;
        if (!currentReport || currentReport.status !== 'menunggu_verifikasi') return;

        if (e.key.toLowerCase() === 'v') document.getElementById('btn-open-valid')?.click();
        if (e.key.toLowerCase() === 't') document.getElementById('btn-open-tolak')?.click();
        if (e.key.toLowerCase() === 'd') document.getElementById('btn-open-duplikat')?.click();
    });

    // Open Valid Modal
    document.getElementById('btn-open-valid')?.addEventListener('click', (e) => {
        if (!currentReport) return;
        document.getElementById('mv-laporan').textContent = `${currentReport.kode} ${currentReport.kelurahan}`;
        document.getElementById('mv-kepercayaan').textContent = (currentReport.kepercayaan?.level || 'rendah').toUpperCase();
        document.getElementById('mv-error').hidden = true;
        openModal('modal-valid', e.currentTarget);
    });

    // Open Tidak Valid Modal
    const alasanInput = document.getElementById('mt-alasan');
    const mtSubmit = document.getElementById('mt-submit');
    const mtCounter = document.getElementById('mt-counter');
    const syncTolakValidity = () => {
        const len = (alasanInput?.value || '').trim().length;
        if (mtCounter) mtCounter.textContent = `${(alasanInput?.value || '').length}/500`;
        if (mtSubmit) mtSubmit.disabled = len < 5;
        const inlineErr = document.getElementById('mt-inline-err');
        if (inlineErr) inlineErr.hidden = len === 0 || len >= 5;
    };
    alasanInput?.addEventListener('input', syncTolakValidity);
    document.querySelectorAll('[data-reason-chip]').forEach((chip) => {
        chip.addEventListener('click', () => {
            if (!alasanInput) return;
            alasanInput.value = chip.dataset.reasonChip;
            syncTolakValidity();
            alasanInput.focus();
        });
    });

    document.getElementById('btn-open-tolak')?.addEventListener('click', (e) => {
        if (!currentReport) return;
        document.getElementById('mt-laporan').textContent = `${currentReport.kode} ${currentReport.kelurahan}`;
        document.getElementById('mt-kepercayaan').textContent = (currentReport.kepercayaan?.level || 'rendah').toUpperCase();
        alasanInput.value = '';
        document.getElementById('mt-error').hidden = true;
        syncTolakValidity();
        openModal('modal-tidak-valid', e.currentTarget);
    });

    // Open Duplikat Modal
    const mdSelect = document.getElementById('md-induk-select');
    const mdSubmit = document.getElementById('md-submit');
    mdSelect?.addEventListener('change', () => {
        if (mdSubmit) mdSubmit.disabled = !mdSelect.value;
    });

    document.getElementById('btn-open-duplikat')?.addEventListener('click', (e) => {
        if (!currentReport) return;
        document.getElementById('md-laporan').textContent = `${currentReport.kode} ${currentReport.kelurahan}`;
        document.getElementById('md-kepercayaan').textContent = (currentReport.kepercayaan?.level || 'rendah').toUpperCase();
        document.getElementById('md-error').hidden = true;

        const eligible = (currentReport.sekitar || []).filter((s) => s.dapat_jadi_induk);
        mdSelect.innerHTML = '';
        if (!eligible.length) {
            mdSelect.innerHTML = '<option value="">Tidak ada laporan induk dalam radius 500 m</option>';
            mdSubmit.disabled = true;
        } else {
            eligible.forEach((s, idx) => {
                const opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = `${s.kode} ${s.kelurahan} (${s.jarak_m} m)`;
                if (idx === 0) opt.selected = true;
                mdSelect.appendChild(opt);
            });
            mdSubmit.disabled = false;
        }
        openModal('modal-duplikat', e.currentTarget);
    });

    // Submit AJAX helper
    const submitAction = async ({ url, body, btn, errorEl, modalId }) => {
        btn.disabled = true;
        errorEl.hidden = true;
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(body || {}),
            });

            if (res.status === 419) {
                throw new Error('Sesi berakhir. Muat ulang halaman lalu coba lagi.');
            }
            const json = await res.json().catch(() => ({}));
            if (!res.ok) {
                const firstErr = json?.errors ? Object.values(json.errors).flat()[0] : null;
                throw new Error(firstErr || json?.message || 'Terjadi kesalahan saat memproses laporan.');
            }

            closeModal(document.getElementById(modalId));
            showToast(json.message || 'Berhasil memproses laporan.');

            // Update tab counts
            if (json.counts) {
                document.getElementById('count-menunggu').textContent = json.counts.menunggu_verifikasi;
                document.getElementById('count-terverifikasi').textContent = json.counts.terverifikasi;
                document.getElementById('count-ditolak').textContent = json.counts.ditolak;
            }

            // Remove active card and select next
            const activeCard = document.querySelector(`.verif-card[data-report-id="${currentReport.id}"]`);
            const nextCard = activeCard?.nextElementSibling?.classList.contains('verif-card')
                ? activeCard.nextElementSibling
                : activeCard?.previousElementSibling;
            activeCard?.remove();

            if (nextCard && nextCard.classList.contains('verif-card')) {
                nextCard.click();
            } else {
                window.location.reload();
            }
        } catch (err) {
            errorEl.textContent = err.message;
            errorEl.hidden = false;
            btn.disabled = false;
        }
    };

    document.getElementById('mv-submit')?.addEventListener('click', (e) => {
        if (!currentReport) return;
        submitAction({
            url: currentReport.urls.valid,
            body: {},
            btn: e.currentTarget,
            errorEl: document.getElementById('mv-error'),
            modalId: 'modal-valid',
        });
    });

    document.getElementById('mt-submit')?.addEventListener('click', (e) => {
        if (!currentReport) return;
        submitAction({
            url: currentReport.urls.tolak,
            body: { alasan: alasanInput.value.trim() },
            btn: e.currentTarget,
            errorEl: document.getElementById('mt-error'),
            modalId: 'modal-tidak-valid',
        });
    });

    document.getElementById('md-submit')?.addEventListener('click', (e) => {
        if (!currentReport) return;
        submitAction({
            url: currentReport.urls.duplikat,
            body: { laporan_banjir_induk_id: Number(mdSelect.value) },
            btn: e.currentTarget,
            errorEl: document.getElementById('md-error'),
            modalId: 'modal-duplikat',
        });
    });
});
</script>
@endpush
