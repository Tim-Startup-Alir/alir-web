<?php

namespace App\Support;

/**
 * Batas ukuran unggahan yang benar-benar bisa dipakai: nilai config aplikasi, dibatasi oleh
 * upload_max_filesize dan post_max_size milik PHP. Tanpa ini, berkas yang lolos validasi aplikasi
 * tetap ditolak PHP (HTTP 413) sebelum sampai ke Laravel.
 */
class BatasUnggah
{
    /** Sisa ruang post_max_size untuk field form lain dan boundary multipart. */
    private const OVERHEAD_KB = 256;

    public static function efektifKb(int $configKb): int
    {
        $batas = array_filter([
            self::iniKb('upload_max_filesize'),
            self::iniKb('post_max_size') === null ? null : max(0, self::iniKb('post_max_size') - self::OVERHEAD_KB),
        ], fn (?int $v) => $v !== null && $v > 0);

        return $batas === [] ? $configKb : min($configKb, ...$batas);
    }

    public static function fotoKb(): int
    {
        return self::efektifKb((int) config('alir.dokumentasi.foto_maks_kb', 5120));
    }

    public static function videoKb(): int
    {
        return self::efektifKb((int) config('alir.dokumentasi.video_maks_kb', 51200));
    }

    /** Dibulatkan ke bawah agar angka yang ditampilkan tidak pernah melebihi batas sebenarnya. */
    public static function mb(int $kb): int
    {
        return intdiv($kb, 1024);
    }

    private static function iniKb(string $kunci): ?int
    {
        $nilai = ini_get($kunci);
        if ($nilai === false || $nilai === '') {
            return null;
        }

        $byte = ini_parse_quantity($nilai);

        return $byte > 0 ? intdiv($byte, 1024) : null;
    }
}
