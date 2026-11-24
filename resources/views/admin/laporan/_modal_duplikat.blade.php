<div class="verif-modal-backdrop" id="modal-duplikat" role="dialog" aria-modal="true" aria-labelledby="modal-duplikat-title" hidden dusk="modal-duplikat">
    <div class="verif-modal">
        <div class="verif-modal-icon yellow">
            <i data-lucide="info"></i>
        </div>
        <h3 class="verif-modal-title" id="modal-duplikat-title">Tandai duplikat?</h3>
        <p class="verif-modal-desc">Digabung ke laporan terdekat.</p>

        <div class="verif-modal-summary">
            <div class="verif-summary-row">
                <span>Laporan</span>
                <strong id="md-laporan">—</strong>
            </div>
            <div class="verif-summary-row">
                <span>Tingkat kepercayaan</span>
                <strong id="md-kepercayaan">—</strong>
            </div>
        </div>

        <div class="field" style="margin-top:12px">
            <label for="md-induk-select">Laporan induk (radius &le; 500 m)</label>
            <select id="md-induk-select" class="input" dusk="select-laporan-induk">
                <option value="">Pilih laporan induk...</option>
            </select>
            <p class="xs muted" style="margin-top:4px">Foto dan jumlah terdampak akan digabung ke laporan induk.</p>
        </div>

        <div class="verif-modal-error" id="md-error" hidden></div>

        <div class="verif-modal-actions">
            <button type="button" class="btn btn-ghost btn-sm" data-modal-close>Batal</button>
            <button type="button" class="btn btn-sm verif-btn-orange" id="md-submit" disabled dusk="btn-konfirmasi-duplikat">
                <span class="btn-label">Tandai duplikat</span>
            </button>
        </div>
    </div>
</div>
