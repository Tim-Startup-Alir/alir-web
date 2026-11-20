<?php

namespace App\Http\Controllers\Komunitas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Komunitas\KonfirmasiLapanganRequest;
use App\Models\FotoLaporan;
use App\Models\KomunitasRelawan;
use App\Models\LaporanBanjir;
use App\Models\User;
use App\Services\KepercayaanLaporanService;
use App\Services\KonfirmasiLapanganService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KonfirmasiLapanganController extends Controller
{
    public function index(Request $request, KonfirmasiLapanganService $service): View
    {
        $komunitas = $this->komunitas($request);
        $laporan = $service->laporanUntuk($komunitas);

        return view('komunitas.konfirmasi.index', [
            'diminta' => $laporan->where('konfirmasi_diminta', true)->values(),
            'sekitar' => $laporan->where('konfirmasi_diminta', false)->values(),
        ]);
    }

    public function show(Request $request, LaporanBanjir $laporan, KepercayaanLaporanService $kepercayaan): View
    {
        Gate::authorize('konfirmasi', $laporan);
        $ringkasan = $kepercayaan->analisis($laporan);
        $komunitas = $this->komunitas($request);
        $laporanAman = LaporanBanjir::query()
            ->select(['id', 'kode_pelacakan', 'wilayah_id', 'alamat_singkat', 'latitude', 'longitude', 'severity', 'jumlah_warga', 'status', 'created_at', 'tingkat_kepercayaan'])
            ->with(['wilayah:id,nama_kelurahan', 'foto:id,laporan_banjir_id,foto_path,urutan'])
            ->findOrFail($laporan->getKey());
        $posko = $komunitas->posko()->orderBy('id')->get(['id', 'nama', 'latitude', 'longitude']);

        return view('komunitas.konfirmasi.show', [
            'laporan' => $laporanAman,
            'levelKepercayaan' => $ringkasan['level'],
            'posko' => $posko,
            'radiusFotoKb' => (int) config('alir.konfirmasi.foto_maks_kb', 5120),
        ]);
    }

    public function store(KonfirmasiLapanganRequest $request, LaporanBanjir $laporan, KonfirmasiLapanganService $service, KepercayaanLaporanService $kepercayaan): RedirectResponse
    {
        $komunitas = $this->komunitas($request);
        $data = $request->validated();
        $pelaku = $request->user();
        abort_unless($pelaku instanceof User, 403);

        try {
            $service->kirim(
                $komunitas,
                $laporan,
                $data['hasil'],
                $data['foto'],
                $data['catatan'] ?? null,
                $pelaku,
            );
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['konfirmasi' => $exception->getMessage()]);
        }

        $analisis = $kepercayaan->analisis($laporan->fresh());

        return redirect()->route('komunitas.konfirmasi.index')
            ->with('success', 'Konfirmasi lapangan berhasil dikirim.')
            ->with('kepercayaan_terbaru', [
                'kode' => $laporan->kode_pelacakan,
                'level' => $analisis['level'],
            ]);
    }

    public function fotoLaporan(Request $request, LaporanBanjir $laporan, FotoLaporan $foto, KonfirmasiLapanganService $service): StreamedResponse
    {
        Gate::authorize('konfirmasi', $laporan);
        abort_unless($service->bolehMengonfirmasi($this->komunitas($request), $laporan), 403);
        abort_unless((int) $foto->laporan_banjir_id === (int) $laporan->getKey(), 404);

        $path = $foto->foto_path;
        abort_if(blank($path) || ! Storage::disk('local')->exists($path), 404, 'Foto laporan tidak ditemukan.');

        return Storage::disk('local')->response($path);
    }

    private function komunitas(Request $request): KomunitasRelawan
    {
        $komunitas = $request->user()->komunitasRelawan;
        abort_if($komunitas === null, 404, 'Profil komunitas belum tersedia.');

        return $komunitas;
    }
}
