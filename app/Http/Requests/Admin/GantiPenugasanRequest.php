<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GantiPenugasanRequest extends FormRequest
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
}
