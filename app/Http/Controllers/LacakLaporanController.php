<?php

namespace App\Http\Controllers;

use App\Services\LacakLaporanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Pelapor melacak progres laporannya memakai kode ALR-XXXXXX, tanpa login.
 */
class LacakLaporanController extends Controller
{
    public function __construct(private readonly LacakLaporanService $service) {}

    public function index(Request $request): View
    {
        return view('lacak.index', ['kode' => (string) $request->query('kode', '')]);
    }

    public function cari(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate(
            ['kode' => ['required', 'string', 'max:20']],
            ['kode.*' => 'Masukkan kode laporan Anda, contoh: ALR-7K3M9P.'],
        );

        $laporan = $this->service->cari($data['kode']);

        if (! $laporan) {
            if ($request->expectsJson()) {
                return response()->json(['message' => LacakLaporanService::PESAN_TIDAK_ADA], 404);
            }

            return back()->withInput()->withErrors(['kode' => LacakLaporanService::PESAN_TIDAK_ADA]);
        }

        $tujuan = route('lacak.show', $laporan->kode_pelacakan);

        return $request->expectsJson() ? response()->json(['redirect' => $tujuan]) : redirect($tujuan);
    }

    public function show(string $kode): View|Response
    {
        $laporan = $this->service->cari($kode);

        if (! $laporan) {
            return response()->view('lacak.index', ['kode' => $kode, 'galat' => LacakLaporanService::PESAN_TIDAK_ADA], 404);
        }

        return response()
            ->view('lacak.show', ['lacak' => $this->service->ringkas($laporan)])
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Cache-Control', 'no-store, private');
    }
}
