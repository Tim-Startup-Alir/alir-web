<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KonfirmasiLapangan;
use App\Models\LaporanBanjir;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KonfirmasiLapanganController extends Controller
{
    public function foto(LaporanBanjir $laporan, KonfirmasiLapangan $konfirmasi): StreamedResponse
    {
        abort_unless((int) $konfirmasi->laporan_banjir_id === (int) $laporan->getKey(), 404);

        $path = $konfirmasi->foto_path;
        abort_if(blank($path) || ! Storage::disk('local')->exists($path), 404, 'Foto bukti tidak ditemukan.');

        $stream = Storage::disk('local')->readStream($path);
        abort_unless(is_resource($stream), 404, 'Foto bukti tidak ditemukan.');

        return response()->stream(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $this->contentType($path),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function contentType(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };
    }
}
