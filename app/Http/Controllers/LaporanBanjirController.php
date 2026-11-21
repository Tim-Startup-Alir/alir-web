<?php

namespace App\Http\Controllers;

use App\Enums\StatusLaporan;
use App\Http\Requests\Publik\StoreLaporanBanjirRequest;
use App\Models\LaporanBanjir;
use App\Models\Wilayah;
use App\Services\LaporanBanjirService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * F-02 Pelaporan Banjir publik: PBI-004, PBI-005, PBI-006.
 */
class LaporanBanjirController extends Controller
{
    public const SESSION_TERKIRIM = 'laporan_banjir_terkirim';

    public function __construct(private readonly LaporanBanjirService $service) {}

    public function store(StoreLaporanBanjirRequest $request): JsonResponse|RedirectResponse
    {
        $laporan = $this->service->buat($request->validated(), $request->wilayah(), $request->file('foto'));

        $request->session()->put(self::SESSION_TERKIRIM, $laporan->id);
        $tujuan = route('lapor.diterima');

        if ($request->expectsJson()) {
            return response()->json(['kode' => $laporan->kode_pelacakan, 'redirect' => $tujuan], 201);
        }

        return redirect($tujuan);
    }

    /**
     * PBI-005: cek cepat apakah titik pin/GPS berada di wilayah cakupan, agar pelapor bisa langsung mengoreksi.
     */
    public function cekLokasi(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ], [
            'latitude.*' => 'Koordinat lokasi tidak valid.',
            'longitude.*' => 'Koordinat lokasi tidak valid.',
        ]);

        $wilayah = Wilayah::temukanDari((float) $data['latitude'], (float) $data['longitude']);

        return response()->json([
            'dalam_cakupan' => $wilayah !== null,
            'wilayah' => $wilayah?->nama_kelurahan,
            'kecamatan' => $wilayah?->kecamatan,
            'pesan' => $wilayah
                ? "Lokasi berada di Kelurahan {$wilayah->nama_kelurahan}."
                : StoreLaporanBanjirRequest::pesanLuarCakupan(),
        ]);
    }

    /**
     * PBI-006: halaman "Laporan diterima" berisi kode unik. Hanya untuk browser yang baru saja mengirim laporan.
     */
    public function diterima(Request $request): View|RedirectResponse
    {
        $id = $request->session()->get(self::SESSION_TERKIRIM);
        $laporan = $id ? LaporanBanjir::with('wilayah:id,nama_kelurahan,kecamatan')->find($id) : null;

        if (! $laporan) {
            return redirect()->route('landing');
        }

        return view('lapor.diterima', [
            'laporan' => $laporan,
            'statusLabel' => StatusLaporan::labelDari($laporan->status),
        ]);
    }
}
