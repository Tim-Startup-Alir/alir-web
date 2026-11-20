<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKomunitasRequest extends FormRequest
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
        $komunitas = $this->route('komunitas');

        return [
            'nama' => ['required', 'string', 'max:255'],
            'wilayah_id' => ['required', Rule::exists('wilayah', 'id')->where('is_aktif', true)],
            'nama_pic' => ['required', 'string', 'max:255'],
            'nomor_whatsapp_pic' => ['required', 'string', 'max:20', 'regex:/^(\+?62|0)8\d{8,11}$/'],
            'total_anggota' => ['required', 'integer', 'min:0'],
            'kapasitas_max' => ['required', 'integer', 'min:0'],
            'jumlah_perahu' => ['required', 'integer', 'min:0'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($komunitas->user_id)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return (new StoreKomunitasRequest)->messages();
    }
}
