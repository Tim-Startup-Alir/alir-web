<?php

namespace App\Http\Requests\Auth;

use App\Models\Posko;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PerbaikanKomunitasRequest extends FormRequest
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
            'total_anggota' => ['required', 'integer', 'min:1', 'max:5000'],
            'kapasitas_max' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'jumlah_perahu' => ['required', 'integer', 'min:0', 'max:500'],
            'dokumen_pendukung' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg', 'max:5120'],
            'setuju_syarat' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return (new RegisterKomunitasRequest)->messages();
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
