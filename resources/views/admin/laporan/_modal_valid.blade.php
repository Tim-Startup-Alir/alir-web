<div class="verif-modal-backdrop" id="modal-valid" role="dialog" aria-modal="true" aria-labelledby="modal-valid-title" hidden dusk="modal-valid">
    <div class="verif-modal">
        <div class="verif-modal-icon teal">
            <i data-lucide="check"></i>
        </div>
        <h3 class="verif-modal-title" id="modal-valid-title">Tandai valid?</h3>
        <p class="verif-modal-desc">Laporan akan tampil di peta publik dan siap ditugaskan.</p>

        <div class="verif-modal-summary">
            <div class="verif-summary-row">
                <span>Laporan</span>
                <strong id="mv-laporan">—</strong>
            </div>
            <div class="verif-summary-row">
                <span>Tingkat kepercayaan</span>
                <strong id="mv-kepercayaan">—</strong>
            </div>
        </div>

        <div class="verif-modal-error" id="mv-error" hidden></div>

        <div class="verif-modal-actions">
            <button type="button" class="btn btn-ghost btn-sm" data-modal-close>Batal</button>
            <button type="button" class="btn btn-sm verif-btn-orange" id="mv-submit" dusk="btn-konfirmasi-valid">
                <span class="btn-label">Tandai valid</span>
            </button>
        </div>
    </div>
</div>
