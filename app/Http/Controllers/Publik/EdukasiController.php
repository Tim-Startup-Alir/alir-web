<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Models\KontenEdukasi;
use App\Services\KontenEdukasiService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman publik Edukasi Siaga Banjir. Bagian interaktif (simulator, fase, tas siaga, kuis) ada di
 * resources/js/publik/edukasi.js; konten terbit dari CMS (PBI-024) dibaca per kategori dan dapat dicari.
 */
class EdukasiController extends Controller
{
    public function index(Request $request, KontenEdukasiService $service): View
    {
        $kategori = $request->string('kategori')->toString();
        $kategori = in_array($kategori, KontenEdukasi::KATEGORI, true) ? $kategori : '';
        $q = mb_substr(trim($request->string('q')->toString()), 0, 100);

        return view('edukasi.index', [
            'konten' => $service->cari($kategori, $q)->paginate(9, ['*'], 'halaman')->withQueryString()->fragment('konten'),
            'kategori' => $kategori,
            'q' => $q,
            'hitungKategori' => KontenEdukasi::query()->terbit()->selectRaw('kategori, count(*) as total')->groupBy('kategori')->pluck('total', 'kategori'),
        ]);
    }

    public function show(KontenEdukasi $konten): View
    {
        abort_unless($konten->isTerbit(), 404);

        return view('edukasi.show', [
            'konten' => $konten,
            'embed' => $konten->tipe === 'video_sematan' ? KontenEdukasiService::embedUrl($konten->media_url) : null,
            'terkait' => KontenEdukasi::query()->terbit()
                ->where('kategori', $konten->kategori)->whereKeyNot($konten->getKey())
                ->latest('diterbitkan_pada')->limit(3)->get(),
        ]);
    }
}
