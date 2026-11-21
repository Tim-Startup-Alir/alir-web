<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LaporanBanjir;
use App\Models\Penugasan;
use App\Models\User;
use App\Notifications\PenugasanRelawanNotification;
use App\Services\PenugasanRelawanService;
use App\Services\RekomendasiPenugasanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class PenugasanRelawanController extends Controller
{
    public function index(
        Request $request,
        RekomendasiPenugasanService $rekomendasi
    ): View {
        $validated = $request->validate([
            'status' => ['sometimes', 'in:semua,belum_ditugaskan,sudah_ditugaskan'],
        ]);
        $statusFilter = $validated['status'] ?? 'semua';

        $laporanDasar = LaporanBanjir::query()
            ->whereIn('status', ['terverifikasi', 'ditugaskan']);
        $penugasanAktif = fn ($query) => $query->whereIn('stage', Penugasan::STAGE_AKTIF);
        $belumDitugaskan = (clone $laporanDasar)
            ->where('status', 'terverifikasi')
            ->whereDoesntHave('penugasan', $penugasanAktif);
        $sudahDitugaskan = (clone $laporanDasar)
            ->whereHas('penugasan', $penugasanAktif);

        $jumlahLaporan = [
            'semua' => (clone $laporanDasar)->count(),
            'belum_ditugaskan' => (clone $belumDitugaskan)->count(),
            'sudah_ditugaskan' => (clone $sudahDitugaskan)->count(),
        ];
        $query = match ($statusFilter) {
            'belum_ditugaskan' => $belumDitugaskan,
            'sudah_ditugaskan' => $sudahDitugaskan,
            default => $laporanDasar,
        };

        $laporan = $query
            ->with([
                'wilayah',
                'penugasan' => $penugasanAktif,
                'penugasan.komunitasRelawan',
            ])
            ->latest()
            ->paginate(6)
            ->withQueryString();

        $rekomendasiLaporan = $laporan->getCollection()->mapWithKeys(
            fn (LaporanBanjir $item) => [$item->id => $rekomendasi->untukLaporan($item)]
        );
        $jumlahPelapor = $laporan->getCollection()->mapWithKeys(
            fn (LaporanBanjir $item) => [$item->id => $item->jumlahPelaporIndependen()]
        );
        $daftarTugas = Penugasan::query()
            ->with(['laporanBanjir.wilayah', 'komunitasRelawan'])
            ->latest()
            ->paginate(10, ['*'], 'tugas_page')
            ->withQueryString();
        $opsiEditPenugasan = collect();
        if ($statusFilter === 'sudah_ditugaskan') {
            $opsiEditPenugasan = $daftarTugas->getCollection()
                ->filter(fn (Penugasan $tugas) => $tugas->stage === 'menunggu')
                ->mapWithKeys(function (Penugasan $tugas) use ($rekomendasi): array {
                    $komunitas = $tugas->komunitasRelawan;
                    $tugasAktifLain = $komunitas->penugasan()
                        ->whereIn('stage', Penugasan::STAGE_AKTIF)
                        ->where('id', '!=', $tugas->id)
                        ->get();
                    $kelompok = $rekomendasi->untukLaporan($tugas->laporanBanjir)['kelompok'];
                    $kandidat = collect($kelompok)
                        ->flatMap(fn (array $group) => $group['kandidat'])
                        ->filter(fn (array $item) => $item['bisa_ditugaskan']
                            && (int) $item['komunitas']->id !== (int) $komunitas->id)
                        ->values();

                    return [$tugas->id => [
                        'maks_personil_saat_ini' => max(
                            0,
                            $komunitas->kapasitas_max - (int) $tugasAktifLain->sum('jumlah_personil_tugas')
                        ),
                        'maks_perahu_saat_ini' => max(
                            0,
                            $komunitas->jumlah_perahu - (int) $tugasAktifLain->sum('armada_perahu_tugas')
                        ),
                        'kandidat' => $kandidat,
                    ]];
                });
        }

        return view('admin.penugasan.index', compact(
            'daftarTugas',
            'jumlahLaporan',
            'jumlahPelapor',
            'laporan',
            'opsiEditPenugasan',
            'rekomendasiLaporan',
            'statusFilter'
        ));
    }

    public function store(
        Request $request,
        LaporanBanjir $laporan,
        PenugasanRelawanService $service
    ): JsonResponse|RedirectResponse {
        $data = $request->validate([
            'komunitas_relawan_id' => ['required', 'integer', 'exists:komunitas_relawan,id'],
            'jumlah_personil_tugas' => ['required', 'integer', 'min:1'],
            'armada_perahu_tugas' => ['nullable', 'integer', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);
        $admin = $request->user();
        abort_unless($admin instanceof User, 403);

        $penugasan = $service->tugaskan(
            $laporan,
            (int) $data['komunitas_relawan_id'],
            (int) $data['jumlah_personil_tugas'],
            (int) ($data['armada_perahu_tugas'] ?? 0),
            $data['catatan'] ?? null,
            $admin
        );
        $this->kirimNotifikasi($penugasan);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Penugasan berhasil disimpan.',
                'data' => ['id' => $penugasan->id, 'stage' => $penugasan->stage],
            ], 201);
        }

        return back()->with('success', 'Penugasan berhasil disimpan.');
    }

    public function batalkan(
        Request $request,
        Penugasan $penugasan,
        PenugasanRelawanService $service
    ): JsonResponse|RedirectResponse {
        $data = $request->validate(['alasan' => ['required', 'string', 'max:1000']]);
        $admin = $request->user();
        abort_unless($admin instanceof User, 403);
        $hasil = $service->batalkan($penugasan, $data['alasan'], $admin);
        $this->kirimNotifikasi(
            $hasil,
            "Penugasan untuk laporan {$hasil->laporanBanjir?->kode_pelacakan} dibatalkan."
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Penugasan berhasil dibatalkan.']);
        }

        return back()->with('success', "Penugasan {$hasil->id} berhasil dibatalkan.");
    }

    public function alihkan(
        Request $request,
        Penugasan $penugasan,
        PenugasanRelawanService $service
    ): JsonResponse|RedirectResponse {
        $data = $request->validate([
            'komunitas_relawan_id' => ['required', 'integer', 'exists:komunitas_relawan,id'],
            'jumlah_personil_tugas' => ['required', 'integer', 'min:1'],
            'armada_perahu_tugas' => ['nullable', 'integer', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'alasan_pengalihan' => ['required', 'string', 'max:1000'],
        ]);
        $admin = $request->user();
        abort_unless($admin instanceof User, 403);

        $baru = $service->alihkan(
            $penugasan,
            (int) $data['komunitas_relawan_id'],
            (int) $data['jumlah_personil_tugas'],
            (int) ($data['armada_perahu_tugas'] ?? 0),
            $data['catatan'] ?? null,
            $data['alasan_pengalihan'],
            $admin
        );
        $lama = $penugasan->fresh(['laporanBanjir', 'komunitasRelawan.user']);
        $this->kirimNotifikasi(
            $lama,
            "Penugasan untuk laporan {$lama->laporanBanjir?->kode_pelacakan} dialihkan ke komunitas lain."
        );
        $this->kirimNotifikasi($baru);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Penugasan berhasil dialihkan.',
                'data' => ['id' => $baru->id, 'stage' => $baru->stage],
            ], 201);
        }

        return back()->with('success', 'Penugasan berhasil dialihkan.');
    }

    public function update(
        Request $request,
        Penugasan $penugasan,
        PenugasanRelawanService $service
    ): JsonResponse|RedirectResponse {
        $data = $request->validate([
            'jumlah_personil_tugas' => ['required', 'integer', 'min:1'],
            'armada_perahu_tugas' => ['nullable', 'integer', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'komunitas_relawan_id' => [
                'nullable',
                'integer',
                'exists:komunitas_relawan,id',
                'not_in:'.$penugasan->komunitas_relawan_id,
            ],
            'alasan_pengalihan' => ['nullable', 'required_with:komunitas_relawan_id', 'string', 'max:1000'],
        ]);
        $admin = $request->user();
        abort_unless($admin instanceof User, 403);

        if (filled($data['komunitas_relawan_id'] ?? null)) {
            $lama = $penugasan->fresh(['laporanBanjir', 'komunitasRelawan.user']);
            $baru = $service->alihkan(
                $penugasan,
                (int) $data['komunitas_relawan_id'],
                (int) $data['jumlah_personil_tugas'],
                (int) ($data['armada_perahu_tugas'] ?? 0),
                $data['catatan'] ?? null,
                $data['alasan_pengalihan'],
                $admin
            );
            $this->kirimNotifikasi(
                $lama,
                "Penugasan untuk laporan {$lama->laporanBanjir?->kode_pelacakan} dialihkan ke komunitas lain."
            );
            $this->kirimNotifikasi($baru);
            $hasil = $baru;
        } else {
            $hasil = $service->perbarui(
                $penugasan,
                (int) $data['jumlah_personil_tugas'],
                (int) ($data['armada_perahu_tugas'] ?? 0),
                $data['catatan'] ?? null,
                $admin
            );
            $this->kirimNotifikasi(
                $hasil,
                "Detail penugasan untuk laporan {$hasil->laporanBanjir?->kode_pelacakan} diperbarui."
            );
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Penugasan berhasil diperbarui.',
                'data' => ['id' => $hasil->id, 'stage' => $hasil->stage],
            ]);
        }

        return back()->with('success', 'Penugasan berhasil diperbarui.');
    }

    private function kirimNotifikasi(Penugasan $penugasan, ?string $pesan = null): void
    {
        try {
            $penugasan->komunitasRelawan?->user?->notify(
                new PenugasanRelawanNotification($penugasan, $pesan)
            );
        } catch (Throwable $exception) {
            Log::error('Notifikasi penugasan relawan gagal dikirim.', [
                'penugasan_id' => $penugasan->id,
                'laporan_id' => $penugasan->laporan_banjir_id,
                'komunitas_relawan_id' => $penugasan->komunitas_relawan_id,
                'exception' => $exception,
            ]);
        }
    }
}
