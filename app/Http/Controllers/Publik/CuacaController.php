<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Halaman publik Cuaca & Rob. Saat ini FE saja: semua angka adalah contoh.
 * TODO (PBI cuaca): isi dari model Cuaca (API BMKG + BMKG Maritim) lalu kirim lewat view.
 */
class CuacaController extends Controller
{
    public function index(): View
    {
        return view('cuaca.index', [
            // lokasi yang bisa dipilih: [nama, selisih pasang (m), selisih suhu (derajat)]
            'lokasi' => [['Pekalongan Utara', 0, 0], ['Pekalongan Barat', 0.03, 1], ['Pekalongan Timur', -0.04, 0], ['Pekalongan Selatan', 0.06, -1]],
            'ringkasan' => ['kondisi' => 'Hujan ringan', 'kelembaban' => 88, 'angin' => 14, 'curah' => 12, 'diperbarui' => '08.45'],
        ]);
    }
}
