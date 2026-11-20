<?php

namespace App\Http\Requests\Admin;

use App\Models\JadwalKesiapsiagaan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JadwalKesiapsiagaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['jam_mulai', 'jam_selesai'] as $field) {
            $val = $this->input($field);
            if (is_string($val) && strlen($val) >= 5) {
                $this->merge([$field => substr($val, 0, 5)]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'komunitas_relawan_id' => ['required', Rule::exists('komunitas_relawan', 'id')],
            'hari' => ['required', Rule::in(JadwalKesiapsiagaan::HARI)],
            'nama_shift' => ['nullable', 'string', 'max:50'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i'],
            'status_ketersediaan' => ['required', Rule::in(['tersedia', 'siaga_terbatas', 'tidak_tersedia'])],
            'nama_pic' => ['required', 'string', 'max:255'],
            'nomor_whatsapp_pic' => ['required', 'string', 'max:20', 'regex:/^(\+?62|0)8\d{8,11}$/'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'komunitas_relawan_id.required' => 'Komunitas relawan wajib dipilih.',
            'komunitas_relawan_id.exists' => 'Komunitas relawan tidak ditemukan.',
            'hari.required' => 'Hari shift wajib dipilih.',
            'hari.in' => 'Hari shift tidak valid.',
            'jam_mulai.required' => 'Jam mulai wajib diisi.',
            'jam_mulai.date_format' => 'Format jam mulai harus JJ:MM.',
            'jam_selesai.required' => 'Jam selesai wajib diisi.',
            'jam_selesai.date_format' => 'Format jam selesai harus JJ:MM.',
            'status_ketersediaan.required' => 'Status ketersediaan wajib dipilih.',
            'status_ketersediaan.in' => 'Status ketersediaan tidak valid.',
            'nama_pic.required' => 'Nama PIC shift wajib diisi.',
            'nomor_whatsapp_pic.required' => 'Nomor WhatsApp PIC shift wajib diisi.',
            'nomor_whatsapp_pic.regex' => 'Format nomor WhatsApp tidak valid (contoh: 081234567890 atau 6281234567890).',
        ];
    }
}
