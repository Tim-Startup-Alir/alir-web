<div class="pen-modal-backdrop" id="modal-ganti" role="dialog" aria-modal="true" aria-labelledby="modal-ganti-title" hidden dusk="modal-ganti">
    <div class="pen-modal">
        <div class="pen-modal-icon yellow">
            <i data-lucide="repeat"></i>
        </div>
        <h3 class="pen-modal-title" id="modal-ganti-title">Alihkan penugasan <span id="mg-kode">—</span></h3>
        <p class="pen-modal-desc">Pilih komunitas relawan pengganti. Kapasitas komunitas sebelumnya akan dikembalikan.</p>

        <div class="field" style="margin-top:14px">
            <label for="mg-komunitas">Komunitas pengganti</label>
            <select id="mg-komunitas" class="input" dusk="select-komunitas-ganti"></select>
        </div>

        <div class="pen-stepper-grid">
            <div class="field">
                <label for="mg-personel">Personel</label>
                <input type="number" id="mg-personel" class="input mono" min="1" value="1">
            </div>
            <div class="field">
                <label for="mg-perahu">Perahu</label>
                <input type="number" id="mg-perahu" class="input mono" min="0" value="0">
            </div>
        </div>

        <div class="field" style="margin-top:12px">
            <label for="mg-catatan">Catatan (opsional)</label>
            <textarea id="mg-catatan" class="input" rows="2" maxlength="500" placeholder="Alasan atau instruksi pengalihan..."></textarea>
        </div>

        <div class="pen-modal-error" id="mg-error" hidden></div>

        <div class="pen-modal-actions">
            <button type="button" class="btn btn-ghost btn-sm" data-modal-close>Batal</button>
            <button type="button" class="btn btn-sm pen-btn-orange" id="mg-submit" dusk="btn-konfirmasi-ganti">Simpan pengalihan</button>
        </div>
    </div>
</div>
