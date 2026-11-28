<?php

namespace App\Http\Controllers\Komunitas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Komunitas\StoreDokumentasiRequest;
use App\Http\Requests\Komunitas\UpdateDokumentasiRequest;
use App\Models\Dokumentasi;
use App\Models\KomunitasRelawan;
use App\Models\User;
use App\Services\DokumentasiService;
use App\Support\BatasUnggah;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DokumentasiController extends Controller
{
    public function __construct(private readonly DokumentasiService $service) {}

    public function index(Request $request): View
    {
        $komunitas = $this->komunitas($request);

        return view('komunitas.dokumentasi.index', [
            'daftar' => $komunitas->dokumentasi()
                ->with(['laporanBanjir:id,kode_pelacakan,alamat_singkat', 'distribusiBantuan:id,item,jumlah'])
                ->latest()
                ->paginate(12),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Dokumentasi::class);
        $komunitas = $this->komunitas($request);

        return view('komunitas.dokumentasi.create', [
            'laporanList' => $this->service->laporanTersedia($komunitas),
            'logistikList' => $this->service->logistikTersedia($komunitas),
            'fotoMb' => BatasUnggah::mb(BatasUnggah::fotoKb()),
            'videoMb' => BatasUnggah::mb(BatasUnggah::videoKb()),
        ]);
    }

    public function store(StoreDokumentasiRequest $request): RedirectResponse
    {
        $pelaku = $request->user();
        abort_unless($pelaku instanceof User, 403);

        try {
            $this->service->unggah($this->komunitas($request), $request->safe()->except('berkas'), $request->file('berkas'), $pelaku);
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['laporan_banjir_id' => $e->getMessage()]);
        }

        return redirect()->route('komunitas.dokumentasi.index')
            ->with('success', 'Dokumentasi terkirim dan menunggu moderasi Tim Startup ALIR.');
    }

    public function edit(Request $request, Dokumentasi $dokumentasi): View
    {
        Gate::authorize('update', $dokumentasi);
        $komunitas = $this->komunitas($request);

        return view('komunitas.dokumentasi.edit', [
            'dokumentasi' => $dokumentasi,
            'laporanList' => $this->service->laporanTersedia($komunitas),
            'logistikList' => $this->service->logistikTersedia($komunitas),
            'fotoMb' => BatasUnggah::mb(BatasUnggah::fotoKb()),
            'videoMb' => BatasUnggah::mb(BatasUnggah::videoKb()),
        ]);
    }

    public function update(UpdateDokumentasiRequest $request, Dokumentasi $dokumentasi): RedirectResponse
    {
        $pelaku = $request->user();
        abort_unless($pelaku instanceof User, 403);

        try {
            $this->service->perbaiki($dokumentasi, $this->komunitas($request), $request->safe()->except('berkas'), $request->file('berkas'), $pelaku);
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['laporan_banjir_id' => $e->getMessage()]);
        }

        return redirect()->route('komunitas.dokumentasi.index')
            ->with('success', 'Perbaikan terkirim. Dokumentasi kembali menunggu moderasi Tim Startup ALIR.');
    }

    public function destroy(Request $request, Dokumentasi $dokumentasi): RedirectResponse
    {
        Gate::authorize('delete', $dokumentasi);
        $pelaku = $request->user();
        abort_unless($pelaku instanceof User, 403);

        try {
            $this->service->hapus($dokumentasi, $pelaku);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('komunitas.dokumentasi.index')->with('success', 'Dokumentasi dihapus.');
    }

    /** Pratinjau berkas milik sendiri, apa pun statusnya (NFR-03: berkas tidak berada di direktori publik). */
    public function media(Dokumentasi $dokumentasi): BinaryFileResponse
    {
        Gate::authorize('view', $dokumentasi);

        return DokumentasiMedia::kirim($dokumentasi, 'private, no-store');
    }

    private function komunitas(Request $request): KomunitasRelawan
    {
        $komunitas = $request->user()->komunitasRelawan;
        abort_if($komunitas === null, 404, 'Profil komunitas belum tersedia.');

        return $komunitas;
    }
}
