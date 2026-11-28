<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Komunitas\DokumentasiMedia;
use App\Http\Requests\Admin\TolakDokumentasiRequest;
use App\Models\Dokumentasi;
use App\Models\User;
use App\Services\DokumentasiService;
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
        Gate::authorize('moderasi', Dokumentasi::class);

        $status = $request->string('status')->toString();
        if (! in_array($status, Dokumentasi::STATUS, true)) {
            $status = 'menunggu';
        }

        $hitung = Dokumentasi::query()->selectRaw('status_moderasi, count(*) as total')->groupBy('status_moderasi')->pluck('total', 'status_moderasi');

        return view('admin.dokumentasi.index', [
            'daftar' => Dokumentasi::query()
                ->where('status_moderasi', $status)
                ->with(['komunitasRelawan.wilayah:id,nama_kelurahan', 'laporanBanjir:id,kode_pelacakan,alamat_singkat', 'distribusiBantuan:id,item,jumlah'])
                ->oldest()
                ->paginate(12)
                ->withQueryString(),
            'status' => $status,
            'hitung' => $hitung,
        ]);
    }

    public function media(Dokumentasi $dokumentasi): BinaryFileResponse
    {
        Gate::authorize('view', $dokumentasi);

        return DokumentasiMedia::kirim($dokumentasi, 'private, no-store');
    }

    public function setujui(Request $request, Dokumentasi $dokumentasi): RedirectResponse
    {
        Gate::authorize('moderasi', Dokumentasi::class);
        $admin = $request->user();
        abort_unless($admin instanceof User, 403);

        try {
            $this->service->setujui($dokumentasi, $admin);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Dokumentasi disetujui dan kini tampil di galeri publik.');
    }

    public function tolak(TolakDokumentasiRequest $request, Dokumentasi $dokumentasi): RedirectResponse
    {
        $admin = $request->user();
        abort_unless($admin instanceof User, 403);

        try {
            $this->service->tolak($dokumentasi, $request->validated('alasan_tolak'), $admin);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Dokumentasi ditolak. Alasan dapat dilihat oleh komunitas.');
    }
}
