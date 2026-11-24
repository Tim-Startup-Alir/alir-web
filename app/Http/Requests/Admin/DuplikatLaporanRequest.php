<?php

namespace App\Http\Requests\Admin;

use App\Models\LaporanBanjir;
use App\Support\Geo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DuplikatLaporanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'laporan_banjir_induk_id' => ['required', 'integer', Rule::exists('laporan_banjir', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'laporan_banjir_induk_id.required' => 'Laporan induk wajib dipilih.',
            'laporan_banjir_induk_id.exists' => 'Laporan induk tidak ditemukan.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var LaporanBanjir|null $laporan */
            $laporan = $this->route('laporan');
            $indukId = (int) $this->input('laporan_banjir_induk_id');

            if (! $laporan) {
                return;
            }

            if ($indukId === (int) $laporan->id) {
                $validator->errors()->add('laporan_banjir_induk_id', 'Laporan induk tidak boleh sama dengan laporan ini.');

                return;
            }

            $induk = LaporanBanjir::find($indukId);
            if (! $induk) {
                return;
            }

            if (in_array($induk->status, ['digabung', 'ditolak'], true)) {
                $validator->errors()->add('laporan_banjir_induk_id', 'Laporan induk yang dipilih sudah berstatus digabung atau ditolak.');

                return;
            }

            $radiusMaks = (int) config('alir.kepercayaan.radius_m', 500);
            $jarakM = Geo::jarakMeter(
                (float) $laporan->latitude,
                (float) $laporan->longitude,
                (float) $induk->latitude,
                (float) $induk->longitude,
            );

            if ($jarakM > $radiusMaks) {
                $validator->errors()->add('laporan_banjir_induk_id', "Laporan induk harus berada dalam radius {$radiusMaks} meter (jarak saat ini {$jarakM} m).");
            }
        });
    }
}
