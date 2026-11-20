<?php

namespace App\Http\Requests;

use App\Models\Posko;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProfilKomunitasRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $target = $this->route('komunitas') ?? $user->komunitasRelawan;

        return $target !== null && Gate::forUser($user)->allows('update', $target);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'nama_pic' => ['required', 'string', 'max:255'],
            'nomor_whatsapp_pic' => ['required', 'string', 'max:20', 'regex:/^(\+?62|0)8\d{8,11}$/'],
            'total_anggota' => ['required', 'integer', 'min:0'],
            'kapasitas_max' => ['required', 'integer', 'min:0'],
            'jumlah_perahu' => ['required', 'integer', 'min:0'],
            'posko_nama' => ['required', 'string', 'max:255'],
            'posko_alamat' => ['nullable', 'string', 'max:255'],
            'posko_latitude' => ['required', 'numeric', 'between:-90,90'],
            'posko_longitude' => ['required', 'numeric', 'between:-180,180'],
            'posko_radius_m' => ['required', 'integer', 'min:100'],
            'pernyataan_luar_cakupan' => ['nullable', 'boolean'],
        ];

        if ($this->user()?->isAdmin()) {
            $rules['nama'] = ['required', 'string', 'max:255'];
            $rules['wilayah_id'] = ['required', Rule::exists('wilayah', 'id')->where('is_aktif', true)];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama komunitas wajib diisi.',
            'wilayah_id.required' => 'Kelurahan layanan wajib dipilih.',
            'wilayah_id.exists' => 'Kelurahan layanan harus aktif dan terdaftar.',
            'nama_pic.required' => 'Nama PIC wajib diisi.',
            'nomor_whatsapp_pic.required' => 'Nomor WhatsApp PIC wajib diisi.',
            'nomor_whatsapp_pic.regex' => 'Format nomor WhatsApp tidak valid (contoh: 081234567890 atau 6281234567890).',
            'total_anggota.required' => 'Total anggota wajib diisi.',
            'total_anggota.min' => 'Total anggota minimal 0.',
            'kapasitas_max.required' => 'Kapasitas maksimal wajib diisi.',
            'kapasitas_max.min' => 'Kapasitas maksimal minimal 0.',
            'jumlah_perahu.required' => 'Jumlah perahu wajib diisi.',
            'jumlah_perahu.min' => 'Jumlah perahu minimal 0.',
            'posko_nama.required' => 'Nama posko utama wajib diisi.',
            'posko_latitude.required' => 'Latitude posko utama wajib diisi.',
            'posko_latitude.between' => 'Latitude posko harus di antara -90 dan 90.',
            'posko_longitude.required' => 'Longitude posko utama wajib diisi.',
            'posko_longitude.between' => 'Longitude posko harus di antara -180 dan 180.',
            'posko_radius_m.required' => 'Radius layanan posko wajib diisi.',
            'posko_radius_m.min' => 'Radius layanan posko minimal 100 meter.',
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
