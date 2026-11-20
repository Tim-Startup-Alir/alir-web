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

        return Storage::disk('local')->response($path);
    }
}
