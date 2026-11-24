<div class="pen-modal-backdrop" id="modal-tugaskan" role="dialog" aria-modal="true" aria-labelledby="modal-tugaskan-title" hidden dusk="modal-tugaskan">
    <div class="pen-modal">
        <div class="pen-modal-icon orange">
            <i data-lucide="send"></i>
        </div>
        <h3 class="pen-modal-title" id="modal-tugaskan-title">Tugaskan ke <span id="mtug-komunitas-title">—</span></h3>
        <p class="pen-modal-desc">Tentukan jumlah personel dan perahu yang akan diberangkatkan.</p>

        <div class="pen-modal-summary">
            <div class="pen-summary-row"><span>Laporan</span><strong id="mtug-laporan">—</strong></div>
            <div class="pen-summary-row"><span>Komunitas</span><strong id="mtug-komunitas">—</strong></div>
            <div class="pen-summary-row"><span>Jarak &amp; ETA</span><strong class="mono" id="mtug-jarak">—</strong></div>
            <div class="pen-summary-row"><span>Sisa kapasitas</span><strong class="mono" id="mtug-kapasitas">—</strong></div>
        </div>

        <div class="pen-stepper-grid">
            <div class="field">
                <label for="mtug-personel">Jumlah personel <span class="xs muted" id="mtug-personel-max">(maks 1)</span></label>
                <div class="pen-stepper">
                    <button type="button" class="pen-step-btn" data-step-target="mtug-personel" data-step="-1" aria-label="Kurangi personel">&minus;</button>
                    <input type="number" id="mtug-personel" class="input mono" min="1" value="1" dusk="input-personel">
                    <button type="button" class="pen-step-btn" data-step-target="mtug-personel" data-step="1" aria-label="Tambah personel">+</button>
                </div>
            </div>
            <div class="field">
                <label for="mtug-perahu">Jumlah perahu <span class="xs muted" id="mtug-perahu-max">(maks 0)</span></label>
                <div class="pen-stepper">
                    <button type="button" class="pen-step-btn" data-step-target="mtug-perahu" data-step="-1" aria-label="Kurangi perahu">&minus;</button>
                    <input type="number" id="mtug-perahu" class="input mono" min="0" value="0" dusk="input-perahu">
                    <button type="button" class="pen-step-btn" data-step-target="mtug-perahu" data-step="1" aria-label="Tambah perahu">+</button>
                </div>
            </div>
        </div>

        <div class="field" style="margin-top:12px">
            <div class="row between">
                <label for="mtug-catatan">Catatan (opsional)</label>
                <span class="xs muted mono" id="mtug-counter">0/500</span>
            </div>
            <textarea id="mtug-catatan" class="input" rows="2" maxlength="500" placeholder="Instruksi tambahan untuk komunitas relawan..."></textarea>
        </div>

        <div class="pen-modal-error" id="mtug-error" hidden></div>

        <div class="pen-modal-actions">
            <button type="button" class="btn btn-ghost btn-sm" data-modal-close>Batal</button>
            <button type="button" class="btn btn-sm pen-btn-orange" id="mtug-submit" dusk="btn-konfirmasi-tugaskan">Tugaskan</button>
        </div>
    </div>
</div>
