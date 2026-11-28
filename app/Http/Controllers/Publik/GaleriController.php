<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Komunitas\DokumentasiMedia;
use App\Models\Dokumentasi;
use App\Models\Wilayah;
use App\Services\DokumentasiService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** PBI-023: galeri aksi relawan. Hanya dokumentasi berstatus disetujui yang tampil. */
class GaleriController extends Controller
{
    public function index(Request $request, DokumentasiService $service): View
    {
        $wilayahId = $request->integer('wilayah') ?: null;

        $halaman = $service->galeriPublik($wilayahId);

        return view('galeri.index', [
            'halaman' => $halaman,
            'galeri' => $service->kelompokkan($halaman->getCollection()),
            'wilayahList' => Wilayah::query()
                ->whereHas('komunitasRelawan.dokumentasi', fn ($q) => $q->disetujui())
                ->orderBy('nama_kelurahan')
                ->get(['id', 'nama_kelurahan']),
            'wilayahId' => $wilayahId,
        ]);
    }

    public function media(Dokumentasi $dokumentasi): BinaryFileResponse
    {
        abort_unless($dokumentasi->status_moderasi === 'disetujui', 404);

        return DokumentasiMedia::kirim($dokumentasi, 'public, max-age=3600');
    }
}
