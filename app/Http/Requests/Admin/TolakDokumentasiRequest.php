<?php

namespace App\Http\Requests\Admin;

use App\Models\Dokumentasi;
use Illuminate\Foundation\Http\FormRequest;

class TolakDokumentasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('moderasi', Dokumentasi::class) === true;
    }

    public function rules(): array
    {
        return [
            'alasan_tolak' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'alasan_tolak.required' => 'Alasan penolakan wajib diisi agar komunitas tahu apa yang perlu diperbaiki.',
            'alasan_tolak.min' => 'Alasan penolakan minimal :min karakter.',
            'alasan_tolak.max' => 'Alasan penolakan maksimal :max karakter.',
        ];
    }
}
