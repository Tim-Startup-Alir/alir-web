<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePenugasanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'komunitas_relawan_id' => ['required', 'integer', Rule::exists('komunitas_relawan', 'id')],
            'jumlah_personil_tugas' => ['required', 'integer', 'min:1'],
            'armada_perahu_tugas' => ['required', 'integer', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'komunitas_relawan_id.required' => 'Komunitas relawan wajib dipilih.',
            'jumlah_personil_tugas.required' => 'Jumlah personel wajib diisi.',
            'jumlah_personil_tugas.min' => 'Minimal menugaskan 1 personel.',
            'armada_perahu_tugas.required' => 'Jumlah perahu wajib diisi.',
            'armada_perahu_tugas.min' => 'Jumlah perahu tidak boleh negatif.',
            'catatan.max' => 'Catatan maksimal 500 karakter.',
        ];
    }
}
