<div class="pen-modal-backdrop" id="modal-batalkan" role="dialog" aria-modal="true" aria-labelledby="modal-batal-title" hidden dusk="modal-batalkan">
    <div class="pen-modal">
        <div class="pen-modal-icon red">
            <i data-lucide="triangle-alert"></i>
        </div>
        <h3 class="pen-modal-title" id="modal-batal-title">Batalkan penugasan <span id="mb-kode">TK-1</span>?</h3>
        <p class="pen-modal-desc">Kapasitas komunitas akan dikembalikan dan laporan kembali berstatus Belum ditugaskan.</p>

        <div class="field" style="margin-top:14px">
            <label for="mb-alasan">Alasan pembatalan (opsional)</label>
            <textarea id="mb-alasan" class="input" rows="2" maxlength="500" placeholder="Tuliskan alasan pembatalan jika ada..."></textarea>
        </div>

        <div class="pen-modal-error" id="mb-error" hidden></div>

        <div class="pen-modal-actions">
            <button type="button" class="btn btn-ghost btn-sm" data-modal-close>Tutup</button>
            <button type="button" class="btn btn-red btn-sm" id="mb-submit" dusk="btn-konfirmasi-batalkan">Batalkan penugasan</button>
        </div>
    </div>
</div>
