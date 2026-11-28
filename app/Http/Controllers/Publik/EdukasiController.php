<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Halaman publik Edukasi Siaga Banjir (simulator, fase, tas siaga, mitos/fakta, kuis).
 * Saat ini FE saja: konten interaktif ada di resources/js/publik/edukasi.js.
 * TODO (PBI edukasi): $artikel diisi dari KontenEdukasi yang berstatus terbit; konten lain boleh dipindah ke CMS.
 */
class EdukasiController extends Controller
{
    public function index(): View
    {
        return view('edukasi.index', [
            'artikel' => [
                ['judul' => 'Cara membuat tas siaga bencana', 'kategori' => 'Persiapan', 'diperbarui' => '12 Mar 2026'],
                ['judul' => 'Mitos & fakta air banjir', 'kategori' => 'Saat Banjir', 'diperbarui' => '02 Apr 2026'],
            ],
        ]);
    }
}
