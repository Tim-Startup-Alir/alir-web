<div class="peta-selection-grid">
    <div id="detail-photo" class="peta-photo-empty" role="img" aria-label="Tidak ada foto">Tidak ada foto</div>
    <div id="{{ $mapId ?? 'peta-map' }}" class="peta-map" role="application" aria-label="Peta interaktif titik laporan" tabindex="0" dusk="peta-map"></div>
</div>
<div class="peta-info-grid" id="detail-content" aria-live="polite" dusk="peta-detail">
    <section class="peta-info-card peta-confidence" aria-labelledby="trust-title">
        <h3 id="trust-title">Tingkat kepercayaan</h3>
        <div id="detail-confidence" class="empty">Pilih titik laporan.</div>
    </section>
    <section class="peta-info-card peta-report-data" aria-labelledby="report-data-title">
        <h3 id="report-data-title">Data laporan</h3>
        <div id="detail-facts" class="empty">Pilih titik laporan.</div>
    </section>
</div>
