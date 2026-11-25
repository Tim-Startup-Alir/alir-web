<?php

namespace App\Http\Requests\Komunitas;

use App\Models\LaporanBanjir;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KonfirmasiLapanganRequest extends FormRequest
{
    public function authorize(): bool
    {
        $laporan = $this->route('laporan');

        return $laporan instanceof LaporanBanjir
            && $this->user()?->can('konfirmasi', $laporan) === true;
    }

    public function rules(): array
    {
        return [
            'hasil' => ['required', 'string', Rule::in(['terkonfirmasi', 'tidak_terkonfirmasi'])],
            'foto' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:'.(int) config('alir.konfirmasi.foto_maks_kb', 5120)],
            'catatan' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'hasil.required' => 'Pilih hasil konfirmasi terlebih dahulu.',
            'hasil.in' => 'Pilihan hasil konfirmasi tidak valid.',
            'foto.required' => 'Foto bukti wajib diunggah.',
            'foto.file' => 'Berkas foto bukti tidak valid.',
            'foto.image' => 'Berkas harus berupa gambar.',
            'foto.mimes' => 'Format foto harus JPG, JPEG, atau PNG.',
            'foto.max' => 'Ukuran foto maksimal :max KB.',
            'catatan.string' => 'Catatan harus berupa teks.',
            'catatan.max' => 'Catatan maksimal 500 karakter.',
        ];
    }
}
