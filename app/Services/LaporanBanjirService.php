<?php

namespace App\Services;

use App\Enums\StatusLaporan;
use App\Models\AuditLog;
use App\Models\FotoLaporan;
use App\Models\LaporanBanjir;
use App\Models\User;
use App\Models\Wilayah;
use App\Notifications\LaporanBaruAdminNotification;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * F-02 Pelaporan Banjir: PBI-004 (simpan laporan), PBI-005 (koordinat), PBI-006 (kode unik + notifikasi).
 */
class LaporanBanjirService
{
    public const PREFIX_KODE = 'ALR-';

    public const PANJANG_KODE = 6;

    // tanpa 0/O dan 1/I agar mudah dibacakan lewat telepon/WhatsApp
    public const KARAKTER_KODE = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public const POLA_KODE = '/^ALR-[A-HJ-NP-Z2-9]{6}$/';

    private const MAKS_PERCOBAAN = 5;

    public function buatKode(): string
    {
        for ($i = 0; $i < 20; $i++) {
            $kode = self::PREFIX_KODE.self::acak();

            if (! LaporanBanjir::where('kode_pelacakan', $kode)->exists()) {
                return $kode;
            }
        }

        throw new DomainException('Gagal membuat kode laporan unik. Silakan coba lagi.');
    }

    /**
     * Normalisasi input pencarian kode: huruf kecil, spasi, atau tanpa awalan "ALR-" tetap ditemukan.
     */
    public static function normalisasiKode(?string $kode): string
    {
        $bersih = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $kode));

        if (str_starts_with($bersih, 'ALR') && strlen($bersih) > self::PANJANG_KODE) {
            $bersih = substr($bersih, 3);
        }

        return $bersih === '' ? '' : self::PREFIX_KODE.$bersih;
    }

    /**
     * @param  array<string, mixed>  $data  data tervalidasi dari StoreLaporanBanjirRequest
     */
    public function buat(array $data, Wilayah $wilayah, UploadedFile $foto): LaporanBanjir
    {
        // NFR-03: foto disimpan di disk privat (storage/app/private), bukan public/storage
        $fotoPath = $foto->store('foto-laporan/'.now()->format('Y/m'), 'local');
        if (! is_string($fotoPath) || $fotoPath === '') {
            throw new DomainException('Foto gagal disimpan. Silakan coba lagi.');
        }

        try {
            $laporan = $this->simpan($data, $wilayah, $fotoPath);
        } catch (Throwable $e) {
            Storage::disk('local')->delete($fotoPath);

            throw $e;
        }

        $this->kabariAdmin($laporan);

        return $laporan;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function simpan(array $data, Wilayah $wilayah, string $fotoPath): LaporanBanjir
    {
        for ($percobaan = 1; ; $percobaan++) {
            try {
                return DB::transaction(function () use ($data, $wilayah, $fotoPath): LaporanBanjir {
                    $laporan = LaporanBanjir::create([
                        'wilayah_id' => $wilayah->id,
                        'kode_pelacakan' => $this->buatKode(),
                        'nama_pelapor' => $data['nama_pelapor'] ?? null,
                        'alamat_singkat' => $data['alamat_singkat'],
                        'latitude' => round((float) $data['latitude'], 7),
                        'longitude' => round((float) $data['longitude'], 7),
                        'sumber_lokasi' => $data['sumber_lokasi'],
                        'severity' => $data['severity'],
                        'jumlah_warga' => (int) $data['jumlah_warga'],
                        'status' => StatusLaporan::MenungguVerifikasi->value,
                        'catatan' => $data['catatan'] ?? null,
                        'nomor_wa_pelapor' => $data['nomor_wa'],
                        'setuju_dihubungi' => true,
                    ]);

                    FotoLaporan::create([
                        'laporan_banjir_id' => $laporan->id,
                        'foto_path' => $fotoPath,
                        'urutan' => 1,
                    ]);

                    AuditLog::catat(
                        'laporan.buat',
                        $laporan,
                        "Laporan banjir publik {$laporan->kode_pelacakan} di {$wilayah->nama_kelurahan} ({$laporan->severity})",
                        sistem: true,
                    );

                    return $laporan;
                });
            } catch (UniqueConstraintViolationException $e) {
                // dua laporan bersamaan mendapat kode yang sama: ulangi dengan kode baru
                if ($percobaan >= self::MAKS_PERCOBAAN) {
                    throw $e;
                }
            }
        }
    }

    private function kabariAdmin(LaporanBanjir $laporan): void
    {
        try {
            $admins = User::where('role', User::ROLE_ADMIN)->where('is_active', true)->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, new LaporanBaruAdminNotification($laporan->loadMissing('wilayah')));
            }
        } catch (Throwable $e) {
            // laporan sudah tersimpan; kegagalan notifikasi tidak boleh membatalkannya
            report($e);
        }
    }

    private static function acak(): string
    {
        $hasil = '';
        $maks = strlen(self::KARAKTER_KODE) - 1;

        for ($i = 0; $i < self::PANJANG_KODE; $i++) {
            $hasil .= self::KARAKTER_KODE[random_int(0, $maks)];
        }

        return $hasil;
    }
}
