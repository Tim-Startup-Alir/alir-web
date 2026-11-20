<?php

namespace App\Http\Requests\Admin;

use App\Models\LaporanBanjir;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PetaLaporanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $rules = [
            'status' => ['sometimes', 'array'],
            'status.*' => ['string', Rule::in(LaporanBanjir::STATUS)],
            'kepercayaan' => ['sometimes', 'array'],
            'kepercayaan.*' => ['string', Rule::in(['tinggi', 'sedang', 'rendah'])],
            'wilayah_id' => ['sometimes', 'nullable', 'integer', 'exists:wilayah,id'],
            'laporan' => ['sometimes', 'nullable', 'integer', 'exists:laporan_banjir,id'],
        ];
        $bboxFields = ['south', 'west', 'north', 'east'];
        $hasAny = collect($bboxFields)->contains(fn ($key) => $this->query->has($key)) || $this->routeIs('admin.peta-laporan.data');

        foreach (['south' => [-90, 90], 'north' => [-90, 90], 'west' => [-180, 180], 'east' => [-180, 180]] as $key => [$min, $max]) {
            $rules[$key] = [$hasAny ? 'required' : 'sometimes', 'numeric', "between:$min,$max"];
        }
        $rules['south'][] = 'lt:north';
        $rules['north'][] = 'gt:south';
        $rules['east'][] = 'gt:west';
        $rules['west'][] = 'lt:east';

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->query->has('south') && $this->query->has('north') && $this->query->has('west') && $this->query->has('east')
                && ((float) $this->query('north') - (float) $this->query('south') > 2
                    || (float) $this->query('east') - (float) $this->query('west') > 2)) {
                $validator->errors()->add('bbox', 'Luas peta terlalu besar. Perbesar peta untuk melihat laporan.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'south.required' => 'Batas selatan wajib diisi bersama batas lainnya.',
            'west.required' => 'Batas barat wajib diisi bersama batas lainnya.',
            'north.required' => 'Batas utara wajib diisi bersama batas lainnya.',
            'east.required' => 'Batas timur wajib diisi bersama batas lainnya.',
            'status.*.in' => 'Status laporan tidak valid.',
            'kepercayaan.*.in' => 'Tingkat kepercayaan tidak valid.',
            'wilayah_id.exists' => 'Wilayah tidak ditemukan.',
            'laporan.exists' => 'Laporan tidak ditemukan.',
            'south.lt' => 'Batas selatan harus lebih kecil dari batas utara.',
            'north.gt' => 'Batas utara harus lebih besar dari batas selatan.',
            'east.gt' => 'Batas timur harus lebih besar dari batas barat.',
            'west.lt' => 'Batas barat harus lebih kecil dari batas timur.',
        ];
    }
}
