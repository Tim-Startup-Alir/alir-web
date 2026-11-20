<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreKomunitasRequest;
use App\Http\Requests\Admin\UpdateKomunitasRequest;
use App\Models\KomunitasRelawan;
use App\Models\Wilayah;
use App\Services\KomunitasService;
use App\Services\PendaftaranKomunitasService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KomunitasController extends Controller
{
    public function __construct(
        private readonly KomunitasService $service,
        private readonly PendaftaranKomunitasService $pendaftaranService,
    ) {}

    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $status = $request->string('status')->toString();

        $antreanPendaftaran = KomunitasRelawan::siapDitinjau()
            ->with(['user', 'wilayah', 'posko' => fn ($q) => $q->orderBy('id')])
            ->latest()
            ->get();

        $pendaftaranDitolak = KomunitasRelawan::query()
            ->where('status_pendaftaran', 'ditolak')
            ->with(['user', 'wilayah'])
            ->latest('diverifikasi_pada')
            ->limit(10)
            ->get();

        $komunitas = KomunitasRelawan::query()
            ->where('status_pendaftaran', 'disetujui')
            ->with(['user', 'wilayah', 'posko' => fn ($q) => $q->orderBy('id')])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('nama', 'like', "%{$search}%")
                        ->orWhere('nama_pic', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$search}%"));
                });
            })
            ->when($status === 'aktif', fn ($q) => $q->whereHas('user', fn ($u) => $u->where('is_active', true)))
            ->when($status === 'nonaktif', fn ($q) => $q->whereHas('user', fn ($u) => $u->where('is_active', false)))
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return view('admin.komunitas.index', [
            'komunitasList' => $komunitas,
            'antreanPendaftaran' => $antreanPendaftaran,
            'pendaftaranDitolak' => $pendaftaranDitolak,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('admin.komunitas.create', [
            'wilayahAktif' => Wilayah::aktif()->orderBy('nama_kelurahan')->get(),
        ]);
    }

    public function store(StoreKomunitasRequest $request): RedirectResponse
    {
        try {
            $komunitas = $this->service->create($request->validated());

            return redirect()->route('admin.komunitas.index')
                ->with('success', "Komunitas {$komunitas->nama} beserta akun dan posko utama berhasil dibuat.");
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['pernyataan_luar_cakupan' => $e->getMessage()]);
        }
    }

    public function edit(KomunitasRelawan $komunitas): View
    {
        $komunitas->load(['user', 'wilayah', 'posko' => fn ($q) => $q->orderBy('id')]);

        return view('admin.komunitas.edit', [
            'komunitas' => $komunitas,
            'wilayahAktif' => Wilayah::aktif()->orderBy('nama_kelurahan')->get(),
        ]);
    }

    public function update(UpdateKomunitasRequest $request, KomunitasRelawan $komunitas): RedirectResponse
    {
        $this->service->updateMaster($komunitas, $request->validated());

        return redirect()->route('admin.komunitas.index')
            ->with('success', "Data master komunitas {$komunitas->nama} berhasil diperbarui.");
    }

    public function toggleStatus(KomunitasRelawan $komunitas): RedirectResponse
    {
        $komunitas = $this->service->toggleStatus($komunitas);
        $status = $komunitas->user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('admin.komunitas.index')
            ->with('success', "Komunitas {$komunitas->nama} berhasil {$status}.");
    }

    public function destroy(KomunitasRelawan $komunitas): RedirectResponse
    {
        try {
            $nama = $komunitas->nama;
            $this->service->delete($komunitas);

            return redirect()->route('admin.komunitas.index')
                ->with('success', "Komunitas {$nama} dan akun penggunanya berhasil dihapus.");
        } catch (DomainException $e) {
            return redirect()->route('admin.komunitas.index')
                ->with('error', $e->getMessage());
        }
    }

    public function dokumen(KomunitasRelawan $komunitas): StreamedResponse
    {
        $path = $komunitas->dokumen_pendukung_path;

        abort_if(blank($path) || ! Storage::disk('local')->exists($path), 404, 'Dokumen pendukung tidak ditemukan.');

        return Storage::disk('local')->response($path);
    }

    public function setujui(Request $request, KomunitasRelawan $komunitas): RedirectResponse
    {
        try {
            $this->pendaftaranService->setujui($komunitas, $request->user());

            return redirect()->route('admin.komunitas.index')
                ->with('success', "Pendaftaran komunitas {$komunitas->nama} disetujui dan notifikasi email telah dikirim.");
        } catch (DomainException $e) {
            return redirect()->route('admin.komunitas.index')
                ->with('error', $e->getMessage());
        }
    }

    public function tolak(Request $request, KomunitasRelawan $komunitas): RedirectResponse
    {
        $validated = $request->validate([
            'alasan_tolak_registrasi' => ['required', 'string', 'max:1000'],
        ], [
            'alasan_tolak_registrasi.required' => 'Alasan penolakan wajib diisi agar komunitas tahu bagian yang harus diperbaiki.',
        ]);

        try {
            $this->pendaftaranService->tolak($komunitas, $request->user(), $validated['alasan_tolak_registrasi']);

            return redirect()->route('admin.komunitas.index')
                ->with('success', "Pendaftaran {$komunitas->nama} ditolak dan tautan perbaikan telah dikirim ke email pendaftar.");
        } catch (DomainException $e) {
            return redirect()->route('admin.komunitas.index')
                ->with('error', $e->getMessage());
        }
    }
}
