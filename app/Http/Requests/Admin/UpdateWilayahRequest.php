<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWilayahRequest extends FormRequest
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
        $wilayah = $this->route('wilayah');

        return [
            'kode_adm4' => ['required', 'string', 'max:20', 'regex:/^\d{2}\.\d{2}\.\d{2}\.\d{4}$/', Rule::unique('wilayah', 'kode_adm4')->ignore($wilayah)],
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
        return (new StoreWilayahRequest)->messages();
    }
}
