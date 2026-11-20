<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PetaLaporanRequest;
use App\Http\Resources\LaporanPetaResource;
use App\Http\Resources\LaporanSekitarResource;
use App\Models\FotoLaporan;
use App\Models\LaporanBanjir;
use App\Models\Wilayah;
use App\Services\LaporanSekitarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PetaLaporanController extends Controller
{
    public function __construct(private readonly LaporanSekitarService $service) {}

    public function index(PetaLaporanRequest $request): View
    {
        $wilayah = Wilayah::aktif()->orderBy('nama_kelurahan')->get(['id', 'nama_kelurahan']);
        $pusat = Wilayah::aktif()->get(['latitude', 'longitude']);
        $selected = $request->validated('laporan');
        $selectedReport = $selected ? LaporanBanjir::findOrFail($selected) : null;
        $center = $selectedReport
            ? [(float) $selectedReport->latitude, (float) $selectedReport->longitude]
            : ($pusat->isNotEmpty()
            ? [$pusat->avg(fn ($w) => (float) $w->latitude), $pusat->avg(fn ($w) => (float) $w->longitude)]
            : config('alir.peta.pusat_default'));

        return view('admin.peta-laporan.index', [
            'wilayahList' => $wilayah, 'center' => $center,
            'selectedId' => $selected,
            'statuses' => LaporanBanjir::STATUS,
        ]);
    }

    public function data(PetaLaporanRequest $request): JsonResponse
    {
        $data = $request->validated();
        abort_unless(isset($data['south'], $data['west'], $data['north'], $data['east']), 422, 'Batas peta wajib lengkap.');
        $limit = config('alir.peta.batas_marker');
        $laporan = $this->service->titikDalamArea($data, $data, $limit);
        $terpotong = $laporan->count() > $limit;

        return response()->json([
            'data' => LaporanPetaResource::collection($laporan->take($limit))->resolve(),
            'terpotong' => $terpotong,
        ]);
    }

    public function sekitar(PetaLaporanRequest $request, LaporanBanjir $laporan): JsonResponse
    {
        $laporan->load(['foto', 'wilayah']);
        $sekitar = $this->service->cariSekitar($laporan);
        $laporan->loadMissing(['konfirmasiLapangan.komunitasRelawan', 'duplikat']);
        $sekitar->load(['konfirmasiLapangan.komunitasRelawan', 'duplikat']);
        $this->service->siapkanKonteks(collect([$laporan])->concat($sekitar));

        return response()->json([
            'laporan' => (new LaporanSekitarResource($laporan))->resolve(),
            'sekitar' => LaporanSekitarResource::collection($sekitar)->resolve(),
            'parameter' => ['radius_m' => config('alir.kepercayaan.radius_m'), 'rentang_jam' => config('alir.kepercayaan.rentang_jam')],
        ]);
    }

    public function foto(LaporanBanjir $laporan, FotoLaporan $foto): StreamedResponse
    {
        $path = $foto->foto_path;
        abort_if(blank($path) || ! Storage::disk('local')->exists($path), 404, 'Foto laporan tidak ditemukan.');

        return Storage::disk('local')->response($path);
    }
}
