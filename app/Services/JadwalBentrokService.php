<?php

namespace App\Services;

use App\Models\JadwalKesiapsiagaan;
use InvalidArgumentException;

class JadwalBentrokService
{
    private const MENIT_PER_HARI = 1440;

    private const MENIT_PER_MINGGU = 10080;

    /**
     * Mencari jadwal bentrok pada komunitas yang sama (mendukung shift lintas tengah malam & minggu melingkar).
     */
    public function cariBentrok(
        int $komunitasRelawanId,
        string $hari,
        string $jamMulai,
        string $jamSelesai,
        ?int $kecualikanId = null
    ): ?JadwalKesiapsiagaan {
        $intervalBaru = $this->keIntervalMingguan($hari, $jamMulai, $jamSelesai);

        $jadwalList = JadwalKesiapsiagaan::query()
            ->where('komunitas_relawan_id', $komunitasRelawanId)
            ->when($kecualikanId !== null, fn ($q) => $q->where('id', '!=', $kecualikanId))
            ->get();

        foreach ($jadwalList as $jadwal) {
            $intervalAda = $this->keIntervalMingguan($jadwal->hari, $jadwal->jam_mulai, $jadwal->jam_selesai);

            if ($this->adaIrisan($intervalBaru, $intervalAda)) {
                return $jadwal;
            }
        }

        return null;
    }

    /**
     * Mengubah shift (hari, jam_mulai, jam_selesai) menjadi 1 atau 2 interval half-open [start, end) dalam [0, 10080).
     *
     * @return array<int, array{0: int, 1: int}>
     */
    public function keIntervalMingguan(string $hari, string $jamMulai, string $jamSelesai): array
    {
        $indeksHari = array_search($hari, JadwalKesiapsiagaan::HARI, true);
        if ($indeksHari === false) {
            throw new InvalidArgumentException("Hari tidak valid: {$hari}");
        }

        $menitMulai = $this->keMenit($jamMulai);
        $menitSelesai = $this->keMenit($jamSelesai);

        $durasi = $menitSelesai - $menitMulai;
        if ($durasi <= 0) {
            $durasi += self::MENIT_PER_HARI;
        }

        $awal = $indeksHari * self::MENIT_PER_HARI + $menitMulai;
        $akhir = $awal + $durasi;

        if ($akhir <= self::MENIT_PER_MINGGU) {
            return [[$awal, $akhir]];
        }

        return [
            [$awal, self::MENIT_PER_MINGGU],
            [0, $akhir - self::MENIT_PER_MINGGU],
        ];
    }

    /**
     * Dua daftar interval beririsan bila terdapat pasangan [aStart, aEnd) dan [bStart, bEnd) dengan aStart < bEnd && bStart < aEnd.
     * Bersentuhan tepat di batas (aEnd === bStart) BUKAN bentrok.
     *
     * @param  array<int, array{0: int, 1: int}>  $setA
     * @param  array<int, array{0: int, 1: int}>  $setB
     */
    private function adaIrisan(array $setA, array $setB): bool
    {
        foreach ($setA as [$aStart, $aEnd]) {
            foreach ($setB as [$bStart, $bEnd]) {
                if ($aStart < $bEnd && $bStart < $aEnd) {
                    return true;
                }
            }
        }

        return false;
    }

    private function keMenit(string $jam): int
    {
        $bagian = explode(':', $jam);

        return ((int) $bagian[0]) * 60 + ((int) ($bagian[1] ?? 0));
    }
}
