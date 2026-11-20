<?php

namespace App\Http\Requests\Auth;

use App\Models\Posko;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class RegisterKomunitasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'wilayah_id' => ['required', Rule::exists('wilayah', 'id')->where('is_aktif', true)],
            'posko_nama' => ['nullable', 'string', 'max:255'],
            'posko_alamat' => ['nullable', 'string', 'max:255'],
            'posko_latitude' => ['required', 'numeric', 'between:-90,90'],
            'posko_longitude' => ['required', 'numeric', 'between:-180,180'],
            'posko_radius_m' => ['nullable', 'integer', 'min:100', 'max:10000'],
            'pernyataan_luar_cakupan' => ['nullable', 'boolean'],
            'nama_pic' => ['required', 'string', 'max:255'],
            'nomor_whatsapp_pic' => ['required', 'string', 'max:20', 'regex:/^(\+?62|0)8\d{8,11}$/'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'total_anggota' => ['required', 'integer', 'min:1', 'max:5000'],
            'kapasitas_max' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'jumlah_perahu' => ['required', 'integer', 'min:0', 'max:500'],
            'dokumen_pendukung' => ['required', 'file', 'mimes:pdf,jpg,jpeg', 'max:5120'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'setuju_syarat' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama komunitas / tim wajib diisi.',
            'wilayah_id.required' => 'Kelurahan layanan wajib dipilih.',
            'wilayah_id.exists' => 'Kelurahan layanan harus aktif dan terdaftar.',
            'posko_latitude.required' => 'Tandai titik lokasi posko pada peta terlebih dahulu.',
            'posko_longitude.required' => 'Tandai titik lokasi posko pada peta terlebih dahulu.',
            'nama_pic.required' => 'Nama penanggung jawab wajib diisi.',
            'nomor_whatsapp_pic.required' => 'Nomor WhatsApp penanggung jawab wajib diisi.',
            'nomor_whatsapp_pic.regex' => 'Format nomor WhatsApp tidak valid (contoh: 081234567890 atau 6281234567890).',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email tersebut sudah terdaftar.',
            'total_anggota.required' => 'Jumlah anggota aktif wajib diisi.',
            'total_anggota.min' => 'Jumlah anggota aktif minimal 1 orang.',
            'jumlah_perahu.required' => 'Jumlah perahu / alat evakuasi wajib diisi.',
            'jumlah_perahu.min' => 'Jumlah perahu minimal 0.',
            'dokumen_pendukung.required' => 'Dokumen pendukung (PDF/JPG) wajib diunggah.',
            'dokumen_pendukung.mimes' => 'Dokumen pendukung harus berformat PDF atau JPG/JPEG.',
            'dokumen_pendukung.max' => 'Ukuran dokumen pendukung maksimal 5 MB.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'setuju_syarat.accepted' => 'Anda harus menyetujui pernyataan kebenaran data dan ketentuan ALIR.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['posko_latitude', 'posko_longitude'])) {
                return;
            }

            $posko = new Posko([
                'latitude' => $this->input('posko_latitude'),
                'longitude' => $this->input('posko_longitude'),
            ]);

            if ($posko->diLuarCakupan() && ! $this->boolean('pernyataan_luar_cakupan')) {
                $validator->errors()->add(
                    'pernyataan_luar_cakupan',
                    'Koordinat posko berada di luar 3 kelurahan pilot. Centang pernyataan kesiapan risiko.'
                );
            }
        });
    }
}
