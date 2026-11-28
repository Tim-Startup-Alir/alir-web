<?php

namespace App\Http\Requests\Komunitas;

/**
 * Perbaikan dokumentasi yang ditolak. Aturannya sama dengan unggah baru, hanya berkas boleh dikosongkan
 * (berarti berkas lama dipertahankan).
 */
class UpdateDokumentasiRequest extends StoreDokumentasiRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('dokumentasi')) === true;
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['berkas'][0] = 'nullable';

        return $rules;
    }
}
