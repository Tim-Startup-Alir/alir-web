<div class="verif-modal-backdrop" id="modal-tidak-valid" role="dialog" aria-modal="true" aria-labelledby="modal-tolak-title" hidden dusk="modal-tidak-valid">
    <div class="verif-modal">
        <div class="verif-modal-icon red">
            <i data-lucide="triangle-alert"></i>
        </div>
        <h3 class="verif-modal-title" id="modal-tolak-title">Tandai tidak valid?</h3>
        <p class="verif-modal-desc">Pelapor akan diberi tahu alasannya.</p>

        <div class="verif-modal-summary">
            <div class="verif-summary-row">
                <span>Laporan</span>
                <strong id="mt-laporan">—</strong>
            </div>
            <div class="verif-summary-row">
                <span>Tingkat kepercayaan</span>
                <strong id="mt-kepercayaan">—</strong>
            </div>
        </div>

        <div class="verif-chips" aria-label="Alasan cepat">
            @foreach (['Foto tidak relevan', 'Lokasi tidak sesuai', 'Spam', 'Data kurang'] as $chip)
                <button type="button" class="verif-chip" data-reason-chip="{{ $chip }}">{{ $chip }}</button>
            @endforeach
        </div>

        <div class="field" style="margin-top:12px">
            <div class="row between">
                <label for="mt-alasan">Alasan penolakan <span style="color:var(--red-500,#E5484D)">*</span></label>
                <span class="xs muted mono" id="mt-counter">0/500</span>
            </div>
            <textarea id="mt-alasan" class="input" rows="3" maxlength="500" placeholder="Tuliskan alasan penolakan minimal 5 karakter..." dusk="input-alasan-tolak"></textarea>
            <span class="err-msg" id="mt-inline-err" hidden>Alasan penolakan wajib diisi minimal 5 karakter.</span>
        </div>

        <div class="verif-modal-error" id="mt-error" hidden></div>

        <div class="verif-modal-actions">
            <button type="button" class="btn btn-ghost btn-sm" data-modal-close>Batal</button>
            <button type="button" class="btn btn-red btn-sm" id="mt-submit" disabled dusk="btn-konfirmasi-tolak">
                <span class="btn-label">Tolak laporan</span>
            </button>
        </div>
    </div>
</div>
