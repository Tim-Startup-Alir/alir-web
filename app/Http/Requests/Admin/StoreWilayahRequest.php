<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWilayahRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'kode_adm4' => ['required', 'string', 'max:20', 'regex:/^\d{2}\.\d{2}\.\d{2}\.\d{4}$/', Rule::unique('wilayah', 'kode_adm4')],
            'nama_kelurahan' => ['required', 'string', 'max:255'],
            'kecamatan' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_cakupan_m' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kode_adm4.required' => 'Kode wilayah (ADM4) wajib diisi.',
            'kode_adm4.regex' => 'Format kode wilayah harus XX.XX.XX.XXXX (contoh: 33.75.01.1009).',
            'kode_adm4.unique' => 'Kode wilayah tersebut sudah terdaftar.',
            'nama_kelurahan.required' => 'Nama kelurahan wajib diisi.',
            'kecamatan.required' => 'Nama kecamatan wajib diisi.',
            'latitude.required' => 'Latitude wajib diisi.',
            'latitude.between' => 'Latitude harus di antara -90 dan 90.',
            'longitude.required' => 'Longitude wajib diisi.',
            'longitude.between' => 'Longitude harus di antara -180 dan 180.',
            'radius_cakupan_m.integer' => 'Radius cakupan harus berupa bilangan bulat.',
            'radius_cakupan_m.min' => 'Radius cakupan minimal 1 meter.',
        ];
    }
}
