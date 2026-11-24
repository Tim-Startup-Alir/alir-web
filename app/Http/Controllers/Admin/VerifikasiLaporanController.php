<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DuplikatLaporanRequest;
use App\Http\Requests\Admin\ValidasiLaporanRequest;
use App\Models\FotoLaporan;
use App\Models\LaporanBanjir;
use App\Services\KepercayaanLaporanService;
use App\Services\LaporanSekitarService;
use App\Services\VerifikasiLaporanService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VerifikasiLaporanController extends Controller
{
    public function __construct(
        private readonly VerifikasiLaporanService $verifikasiService,
        private readonly LaporanSekitarService $sekitarService,
        private readonly KepercayaanLaporanService $kepercayaanService,
    ) {}

    public function index(Request $request): View
    {
        $tab = $request->string('tab')->toString();
        if (! in_array($tab, ['menunggu_verifikasi', 'terverifikasi', 'ditolak'], true)) {
            $tab = 'menunggu_verifikasi';
        }

        $search = trim($request->string('q')->toString());
        $sort = $request->string('sort')->toString();
        if (! in_array($sort, ['terbaru', 'keparahan', 'kepercayaan'], true)) {
            $sort = 'terbaru';
        }

        $counts = $this->hitungTab();

        $query = LaporanBanjir::query()
            ->with(['wilayah', 'foto'])
            ->where('status', $tab)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('kode_pelacakan', 'like', "%{$search}%")
                        ->orWhere('alamat_singkat', 'like', "%{$search}%")
                        ->orWhere('nama_pelapor', 'like', "%{$search}%")
                        ->orWhereHas('wilayah', fn ($w) => $w->where('nama_kelurahan', 'like', "%{$search}%"));
                });
            });

        match ($sort) {
            'keparahan' => $query->orderByRaw("CASE severity WHEN 'berat' THEN 1 WHEN 'sedang' THEN 2 ELSE 3 END")->orderByDesc('created_at'),
            'kepercayaan' => $query->orderByRaw("CASE tingkat_kepercayaan WHEN 'tinggi' THEN 1 WHEN 'sedang' THEN 2 ELSE 3 END")->orderByDesc('created_at'),
            default => $query->orderByDesc('created_at'),
        };

        $laporans = $query->paginate(12)->withQueryString();

        $selectedId = $request->integer('laporan');
        $selected = $selectedId
            ? LaporanBanjir::with(['wilayah', 'foto', 'konfirmasiLapangan.komunitasRelawan', 'duplikat', 'verifikator'])->find($selectedId)
            : $laporans->first();

        $detailPayload = $selected ? $this->formatDetailPayload($selected) : null;

        return view('admin.laporan.verifikasi', [
            'laporans' => $laporans,
            'tab' => $tab,
            'search' => $search,
            'sort' => $sort,
            'counts' => $counts,
            'selectedId' => $selected?->id,
            'detailPayload' => $detailPayload,
        ]);
    }

    public function show(Request $request, LaporanBanjir $laporan): JsonResponse|View
    {
        $laporan->load(['wilayah', 'foto', 'konfirmasiLapangan.komunitasRelawan', 'duplikat', 'verifikator']);
        $payload = $this->formatDetailPayload($laporan);

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return $this->index($request->merge([
            'laporan' => $laporan->id,
            'tab' => in_array($laporan->status, ['menunggu_verifikasi', 'terverifikasi', 'ditolak'], true)
                ? $laporan->status
                : 'menunggu_verifikasi',
        ]));
    }

    public function valid(Request $request, LaporanBanjir $laporan): JsonResponse|RedirectResponse
    {
        try {
            $hasil = $this->verifikasiService->tandaiValid($laporan, $request->user());
        } catch (DomainException $e) {
            return $this->errorResponse($request, $e->getMessage());
        }

        return $this->successResponse($request, $hasil, "Laporan {$hasil->kode_pelacakan} berhasil diverifikasi valid.");
    }

    public function tidakValid(ValidasiLaporanRequest $request, LaporanBanjir $laporan): JsonResponse|RedirectResponse
    {
        try {
            $hasil = $this->verifikasiService->tandaiTidakValid(
                $laporan,
                $request->user(),
                $request->validated('alasan'),
            );
        } catch (DomainException $e) {
            return $this->errorResponse($request, $e->getMessage());
        }

        return $this->successResponse($request, $hasil, "Laporan {$hasil->kode_pelacakan} ditandai tidak valid.");
    }

    public function duplikat(DuplikatLaporanRequest $request, LaporanBanjir $laporan): JsonResponse|RedirectResponse
    {
        $induk = LaporanBanjir::findOrFail($request->validated('laporan_banjir_induk_id'));

        try {
            $hasil = $this->verifikasiService->tandaiDuplikat($laporan, $induk, $request->user());
        } catch (DomainException $e) {
            return $this->errorResponse($request, $e->getMessage());
        }

        return $this->successResponse($request, $hasil, "Laporan {$hasil->kode_pelacakan} berhasil digabung ke {$induk->kode_pelacakan}.");
    }

    private function formatDetailPayload(LaporanBanjir $laporan): array
    {
        $laporan->loadMissing(['wilayah', 'foto', 'konfirmasiLapangan.komunitasRelawan', 'duplikat', 'verifikator']);
        $sekitar = $this->sekitarService->cariSekitar($laporan);
        $sekitar->loadMissing(['wilayah', 'foto', 'duplikat']);
        $this->sekitarService->siapkanKonteks(collect([$laporan])->concat($sekitar));

        $analisis = $this->kepercayaanService->analisis($laporan);

        $fotos = $laporan->foto->map(fn (FotoLaporan $f) => [
            'id' => $f->id,
            'url' => route('admin.laporan.foto', ['laporan' => $laporan->id, 'foto' => $f->id]),
            'waktu' => $f->created_at?->format('Y/m/d H:i') ?? $laporan->created_at?->format('Y/m/d H:i'),
        ])->values()->all();

        $sekitarList = $sekitar->map(fn (LaporanBanjir $item) => [
            'id' => $item->id,
            'kode' => $item->kode_pelacakan,
            'kelurahan' => $item->wilayah?->nama_kelurahan ?? $item->alamat_singkat,
            'alamat_singkat' => $item->alamat_singkat,
            'latitude' => (float) $item->latitude,
            'longitude' => (float) $item->longitude,
            'jarak_m' => (int) ($item->jarak_m ?? 0),
            'status' => $item->status,
            'severity' => $item->severity,
            'jumlah_warga' => (int) $item->jumlah_warga,
            'dapat_jadi_induk' => ! in_array($item->status, ['digabung', 'ditolak'], true),
        ])->values()->all();

        return [
            'id' => $laporan->id,
            'kode' => $laporan->kode_pelacakan,
            'kelurahan' => $laporan->wilayah?->nama_kelurahan ?? 'Lokasi Banjir',
            'alamat_singkat' => $laporan->alamat_singkat,
            'catatan' => $laporan->catatan,
            'waktu_relatif' => $laporan->created_at?->diffForHumans() ?? 'Baru saja',
            'dibuat_pada' => $laporan->created_at?->toIso8601String(),
            'severity' => $laporan->severity,
            'status' => $laporan->status,
            'latitude' => (float) $laporan->latitude,
            'longitude' => (float) $laporan->longitude,
            'sumber_lokasi' => $laporan->sumber_lokasi ?? 'gps',
            'nama_pelapor' => $laporan->nama_pelapor ?: 'Anonim',
            'jumlah_warga' => (int) $laporan->jumlah_warga,
            'nomor_wa_masked' => $this->maskWa($laporan->nomor_wa_pelapor),
            'alasan_tolak' => $laporan->alasan_tolak,
            'verifikator' => $laporan->verifikator?->name,
            'diverifikasi_pada' => $laporan->diverifikasi_pada?->translatedFormat('d M Y H:i'),
            'fotos' => $fotos,
            'sekitar' => $sekitarList,
            'jumlah_sekitar' => count($sekitarList),
            'kepercayaan' => $analisis,
            'radius_m' => (int) config('alir.kepercayaan.radius_m', 500),
            'urls' => [
                'valid' => route('admin.laporan.valid', $laporan),
                'tolak' => route('admin.laporan.tolak', $laporan),
                'duplikat' => route('admin.laporan.duplikat', $laporan),
            ],
        ];
    }

    private function maskWa(?string $nomor): string
    {
        $clean = preg_replace('/\D+/', '', (string) $nomor);
        if (strlen($clean) < 7) {
            return $clean !== '' ? $clean : '—';
        }

        return substr($clean, 0, 4).'-****-'.substr($clean, -4);
    }

    private function hitungTab(): array
    {
        return [
            'menunggu_verifikasi' => LaporanBanjir::where('status', 'menunggu_verifikasi')->count(),
            'terverifikasi' => LaporanBanjir::where('status', 'terverifikasi')->count(),
            'ditolak' => LaporanBanjir::where('status', 'ditolak')->count(),
        ];
    }

    private function successResponse(Request $request, LaporanBanjir $laporan, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'status' => $laporan->status,
                'counts' => $this->hitungTab(),
            ]);
        }

        return redirect()->route('admin.laporan.verifikasi.index')->with('success', $message);
    }

    private function errorResponse(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->with('error', $message);
    }
}
