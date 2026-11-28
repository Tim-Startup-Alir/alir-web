<?php

namespace App\Http\Controllers\Komunitas;

use App\Models\Dokumentasi;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Pengirim berkas dokumentasi dari disk privat. Dipakai pratinjau komunitas, panel admin, dan galeri publik
 * (BinaryFileResponse mendukung Range sehingga video dapat digeser).
 */
class DokumentasiMedia
{
    private const TIPE = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'mp4' => 'video/mp4', 'webm' => 'video/webm',
    ];

    public static function kirim(Dokumentasi $dokumentasi, string $cacheControl): BinaryFileResponse
    {
        $disk = Storage::disk('local');
        $path = $dokumentasi->media_path;
        abort_if(blank($path) || ! $disk->exists($path), 404, 'Berkas tidak ditemukan.');

        $tipe = self::TIPE[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? null;
        abort_if($tipe === null, 404, 'Berkas tidak ditemukan.');

        return response()->file($disk->path($path), [
            'Content-Type' => $tipe,
            'Cache-Control' => $cacheControl,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline',
        ]);
    }
}
