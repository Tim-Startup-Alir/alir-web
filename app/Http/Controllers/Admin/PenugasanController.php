<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GantiPenugasanRequest;
use App\Http\Requests\Admin\StorePenugasanRequest;
use App\Models\KomunitasRelawan;
use App\Models\LaporanBanjir;
use App\Models\Penugasan;
use App\Services\PenugasanService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PenugasanController extends Controller
{
    public function __construct(private readonly PenugasanService $penugasanService) {}

    public function index(Request $request): View
    {
        $tab = $request->string('tab')->toString();
        if (! in_array($tab, ['semua', 'belum_ditugaskan', 'sudah_ditugaskan'], true)) {
            $tab = 'semua';
        }

        $search = trim($request->string('q')->toString());
        $stageFilter = $request->string('stage')->toString();

        $belumCount = LaporanBanjir::where('status', 'terverifikasi')->count();
        $sudahCount = LaporanBanjir::whereIn('status', ['ditugaskan', 'ditangani'])->count();
        $counts = [
            'semua' => $belumCount + $sudahCount,
            'belum_ditugaskan' => $belumCount,
            'sudah_ditugaskan' => $sudahCount,
        ];

        $laporanQuery = LaporanBanjir::query()
            ->with([
                'wilayah',
                'penugasan' => fn ($q) => $q->with('komunitasRelawan')->latest('id'),
            ])
            ->when($tab === 'belum_ditugaskan', fn ($q) => $q->where('status', 'terverifikasi'))
            ->when($tab === 'sudah_ditugaskan', fn ($q) => $q->whereIn('status', ['ditugaskan', 'ditangani']))
            ->when($tab === 'semua', fn ($q) => $q->whereIn('status', ['terverifikasi', 'ditugaskan', 'ditangani']))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('kode_pelacakan', 'like', "%{$search}%")
                        ->orWhere('alamat_singkat', 'like', "%{$search}%")
                        ->orWhereHas('wilayah', fn ($w) => $w->where('nama_kelurahan', 'like', "%{$search}%"));
                });
            })
            ->orderByRaw("CASE status WHEN 'terverifikasi' THEN 1 WHEN 'ditugaskan' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at');

        $laporans = $laporanQuery->paginate(10)->withQueryString();

        $rekomendasiPerLaporan = [];
        foreach ($laporans as $lap) {
            $rekomendasiPerLaporan[$lap->id] = $this->penugasanService->rekomendasiUntuk($lap);
        }

        $tugasQuery = Penugasan::query()
            ->with(['laporanBanjir.wilayah', 'komunitasRelawan', 'penugas'])
            ->when($stageFilter !== '' && $stageFilter !== 'semua', fn ($q) => $q->where('stage', $stageFilter))
            ->orderByDesc('created_at');

        $daftarTugas = $tugasQuery->limit(50)->get();

        return view('admin.penugasan.index', [
            'laporans' => $laporans,
            'rekomendasiPerLaporan' => $rekomendasiPerLaporan,
            'daftarTugas' => $daftarTugas,
            'tab' => $tab,
            'search' => $search,
            'stageFilter' => $stageFilter ?: 'semua',
            'counts' => $counts,
        ]);
    }

    public function store(StorePenugasanRequest $request, LaporanBanjir $laporan): JsonResponse|RedirectResponse
    {
        $komunitas = KomunitasRelawan::findOrFail($request->validated('komunitas_relawan_id'));

        try {
            $penugasan = $this->penugasanService->tugaskan(
                $laporan,
                $komunitas,
                $request->user(),
                (int) $request->validated('jumlah_personil_tugas'),
                (int) $request->validated('armada_perahu_tugas'),
                $request->validated('catatan'),
            );
        } catch (DomainException $e) {
            return $this->errorResponse($request, $e->getMessage());
        }

        $msg = "Komunitas {$komunitas->nama} berhasil ditugaskan (TK-{$penugasan->id}).";

        return $request->expectsJson()
            ? response()->json(['message' => $msg, 'penugasan_id' => $penugasan->id])
            : redirect()->route('admin.penugasan.index')->with('success', $msg);
    }

    public function ganti(GantiPenugasanRequest $request, Penugasan $penugasan): JsonResponse|RedirectResponse
    {
        $komunitasBaru = KomunitasRelawan::findOrFail($request->validated('komunitas_relawan_id'));

        try {
            $baru = $this->penugasanService->ganti(
                $penugasan,
                $komunitasBaru,
                $request->user(),
                (int) $request->validated('jumlah_personil_tugas'),
                (int) $request->validated('armada_perahu_tugas'),
                $request->validated('catatan'),
            );
        } catch (DomainException $e) {
            return $this->errorResponse($request, $e->getMessage());
        }

        $msg = "Penugasan berhasil dialihkan ke {$komunitasBaru->nama} (TK-{$baru->id}).";

        return $request->expectsJson()
            ? response()->json(['message' => $msg, 'penugasan_id' => $baru->id])
            : redirect()->route('admin.penugasan.index')->with('success', $msg);
    }

    public function destroy(Request $request, Penugasan $penugasan): JsonResponse|RedirectResponse
    {
        $alasan = $request->string('alasan')->toString();

        try {
            $this->penugasanService->batalkan($penugasan, $request->user(), $alasan);
        } catch (DomainException $e) {
            return $this->errorResponse($request, $e->getMessage());
        }

        $msg = "Penugasan TK-{$penugasan->id} berhasil dibatalkan.";

        return $request->expectsJson()
            ? response()->json(['message' => $msg])
            : redirect()->route('admin.penugasan.index')->with('success', $msg);
    }

    private function errorResponse(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->with('error', $message);
    }
}
