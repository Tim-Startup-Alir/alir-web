<?php

namespace App\Http\Requests\Admin;

use App\Models\KontenEdukasi;
use App\Services\KontenEdukasiService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class KontenEdukasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', KontenEdukasi::class) === true;
    }

    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:200'],
            'kategori' => ['required', Rule::in(KontenEdukasi::KATEGORI)],
            'tipe' => ['required', Rule::in(KontenEdukasi::TIPE)],
            'isi' => [Rule::requiredIf(fn () => $this->input('tipe') === 'artikel'), 'nullable', 'string', 'max:20000'],
            'media_url' => [
                Rule::requiredIf(fn () => in_array($this->input('tipe'), ['infografis', 'video_sematan'], true)),
                'nullable', 'url:https', 'max:500',
            ],
            'status' => ['required', Rule::in(['draft', 'terbit'])],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->input('tipe') === 'video_sematan' && filled($this->input('media_url'))
                    && KontenEdukasiService::embedUrl($this->input('media_url')) === null) {
                    $validator->errors()->add('media_url', 'Hanya tautan YouTube atau Vimeo yang dapat disematkan.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'judul.required' => 'Judul wajib diisi.',
            'judul.max' => 'Judul maksimal :max karakter.',
            'kategori.required' => 'Pilih kategori konten.',
            'kategori.in' => 'Kategori tidak valid.',
            'tipe.required' => 'Pilih tipe konten.',
            'tipe.in' => 'Tipe konten tidak valid.',
            'isi.required' => 'Isi artikel wajib diisi.',
            'isi.max' => 'Isi maksimal :max karakter.',
            'media_url.required' => 'Tautan media wajib diisi untuk infografis atau video sematan.',
            'media_url.url' => 'Tautan media harus berupa URL https yang valid.',
            'status.required' => 'Pilih status konten.',
            'status.in' => 'Status tidak valid.',
        ];
    }
}
