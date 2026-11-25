<?php

namespace App\Enums;

/**
 * Status alur laporan banjir (PBI-004). Nilainya sama dengan kolom enum `laporan_banjir.status`
 * dan konstanta LaporanBanjir::STATUS; model tetap menyimpan string agar kompatibel dengan modul lain.
 */
enum StatusLaporan: string
{
    case MenungguVerifikasi = 'menunggu_verifikasi';
    case Terverifikasi = 'terverifikasi';
    case Ditugaskan = 'ditugaskan';
    case Ditangani = 'ditangani';
    case Selesai = 'selesai';
    case Ditolak = 'ditolak';
    case Digabung = 'digabung';

    public function label(): string
    {
        return match ($this) {
            self::MenungguVerifikasi => 'Menunggu Verifikasi',
            self::Terverifikasi => 'Terverifikasi',
            self::Ditugaskan => 'Ditugaskan',
            self::Ditangani => 'Ditangani',
            self::Selesai => 'Selesai',
            self::Ditolak => 'Ditolak',
            self::Digabung => 'Digabung',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labelDari(?string $status): string
    {
        return self::tryFrom((string) $status)?->label() ?? ucfirst(str_replace('_', ' ', (string) $status));
    }
}
