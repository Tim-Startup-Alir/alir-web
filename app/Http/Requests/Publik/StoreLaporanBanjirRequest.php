<?php

namespace App\Http\Requests\Publik;

use App\Models\Wilayah;
use App\Rules\TurnstileValid;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * PBI-004 & PBI-005: validasi form laporan banjir publik (tanpa login).
 */
class StoreLaporanBanjirRequest extends FormRequest
{
    public const POLA_WA = '/^08[1-9][0-9]{6,11}$/';

    private ?Wilayah $wilayah = null;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nomor_wa' => self::normalisasiWa($this->input('nomor_wa')),
            'alamat_singkat' => self::rapikan($this->input('alamat_singkat'), kosongJadiNull: false),
            'nama_pelapor' => self::rapikan($this->input('nama_pelapor')),
            'catatan' => self::rapikan($this->input('catatan')),
        ]);
    }

    /**
     * Terima format umum (spasi, strip, +62, 62) lalu simpan sebagai 08xxxxxxxxxx.
     */
    public static function normalisasiWa(mixed $nomor): mixed
    {
        if (! is_string($nomor)) {
            return $nomor;
        }

        $nomor = preg_replace('/[\s\-.()]/', '', $nomor);

        return preg_replace('/^\+?62/', '0', $nomor);
    }

    private static function rapikan(mixed $nilai, bool $kosongJadiNull = true): mixed
    {
        if (! is_string($nilai)) {
            return $nilai;
        }

        $nilai = trim($nilai);

        return ($kosongJadiNull && $nilai === '') ? null : $nilai;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'sumber_lokasi' => ['required', 'string', 'in:gps,pin'],
            'alamat_singkat' => ['required', 'string', 'min:5', 'max:255'],
            'severity' => ['required', 'string', 'in:ringan,sedang,berat'],
            'jumlah_warga' => ['required', 'integer', 'min:1', 'max:10000'],
            'foto' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(int) config('alir.laporan.foto_maks_kb', 5120)],
            'nomor_wa' => ['required', 'string', 'regex:'.self::POLA_WA],
            'nama_pelapor' => ['nullable', 'string', 'max:100'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'setuju_dihubungi' => ['accepted'],
            'cf-turnstile-response' => ['required', 'string', new TurnstileValid($this->ip())],
        ];
    }

    /**
     * PBI-005: titik di luar wilayah cakupan ditolak dan pelapor diminta mengoreksi lokasi.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['latitude', 'longitude'])) {
                    return;
                }

                $this->wilayah = Wilayah::temukanDari((float) $this->input('latitude'), (float) $this->input('longitude'));

                if (! $this->wilayah) {
                    $validator->errors()->add('latitude', self::pesanLuarCakupan());
                }
            },
        ];
    }

    public static function pesanLuarCakupan(): string
    {
        $nama = Wilayah::aktif()->orderBy('nama_kelurahan')->pluck('nama_kelurahan')->join(', ', ' dan ');

        return 'Titik lokasi berada di luar wilayah cakupan ALIR'.($nama ? " ({$nama})" : '').'. Geser pin ke lokasi banjir yang benar.';
    }

    public function wilayah(): ?Wilayah
    {
        return $this->wilayah;
    }

    public function messages(): array
    {
        return [
            'latitude.required' => 'Tandai lokasi banjir di peta atau gunakan GPS.',
            'longitude.required' => 'Tandai lokasi banjir di peta atau gunakan GPS.',
            'latitude.numeric' => 'Koordinat lokasi tidak valid.',
            'longitude.numeric' => 'Koordinat lokasi tidak valid.',
            'latitude.between' => 'Koordinat lokasi tidak valid.',
            'longitude.between' => 'Koordinat lokasi tidak valid.',
            'sumber_lokasi.required' => 'Tandai lokasi banjir di peta atau gunakan GPS.',
            'sumber_lokasi.in' => 'Sumber lokasi tidak valid.',
            'alamat_singkat.required' => 'Alamat singkat wajib diisi.',
            'alamat_singkat.min' => 'Alamat singkat minimal :min karakter.',
            'alamat_singkat.max' => 'Alamat singkat maksimal :max karakter.',
            'severity.required' => 'Pilih tingkat keparahan banjir.',
            'severity.in' => 'Tingkat keparahan harus ringan, sedang, atau berat.',
            'jumlah_warga.required' => 'Perkiraan warga terdampak wajib diisi.',
            'jumlah_warga.integer' => 'Perkiraan warga terdampak harus berupa angka.',
            'jumlah_warga.min' => 'Perkiraan warga terdampak minimal :min orang.',
            'jumlah_warga.max' => 'Perkiraan warga terdampak maksimal :max orang.',
            'foto.required' => 'Foto kondisi banjir wajib diunggah.',
            'foto.file' => 'Berkas foto tidak valid.',
            'foto.uploaded' => 'Foto gagal diunggah. Pastikan ukurannya tidak lebih dari 5 MB.',
            'foto.image' => 'Berkas harus berupa gambar.',
            'foto.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'foto.max' => 'Ukuran foto maksimal 5 MB.',
            'nomor_wa.required' => 'Nomor WhatsApp wajib diisi.',
            'nomor_wa.regex' => 'Format nomor WhatsApp tidak valid. Contoh: 081234567890.',
            'nama_pelapor.max' => 'Nama maksimal :max karakter.',
            'catatan.max' => 'Catatan maksimal :max karakter.',
            'setuju_dihubungi.accepted' => 'Centang persetujuan agar Tim ALIR dapat menghubungi Anda.',
            'cf-turnstile-response.required' => 'Selesaikan verifikasi CAPTCHA terlebih dahulu.',
        ];
    }
}
