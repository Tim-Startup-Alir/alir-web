<?php

namespace App\Http\Controllers\Komunitas;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\KomunitasRelawan;
use App\Models\Penugasan;
use App\Models\User;
use App\Services\PenugasanRelawanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PenugasanRelawanController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $komunitas = $user->komunitasRelawan;
        abort_unless($komunitas instanceof KomunitasRelawan, 403);

        $penugasan = $komunitas->penugasan()
            ->with(['laporanBanjir.wilayah'])
            ->latest()
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('komunitas.penugasan.index', compact('penugasan'));
    }

    public function show(Request $request, Penugasan $penugasan): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $komunitas = $user->komunitasRelawan;
        abort_unless($komunitas instanceof KomunitasRelawan, 403);
        abort_unless((int) $penugasan->komunitas_relawan_id === (int) $komunitas->id, 404);

        $penugasan->load([
            'laporanBanjir.wilayah',
            'komunitasRelawan.posko',
        ]);
        $riwayatAudit = AuditLog::query()
            ->where('subjek_type', $penugasan->getMorphClass())
            ->where('subjek_id', $penugasan->id)
            ->whereIn('aksi', [
                'penugasan.diterima',
                'penugasan.ditolak',
                'penugasan.menuju_lokasi',
                'penugasan.ditangani',
                'penugasan.selesai',
                'penugasan.batalkan',
                'penugasan.alihkan_batalkan',
            ])
            ->orderBy('waktu')
            ->get();

        return view('komunitas.penugasan.show', compact('penugasan', 'riwayatAudit'));
    }

    public function respon(
        Request $request,
        Penugasan $penugasan,
        PenugasanRelawanService $service
    ): JsonResponse|RedirectResponse {
        $data = $request->validate([
            'stage' => ['required', 'in:diterima,ditolak'],
            'alasan_tolak' => ['required_if:stage,ditolak', 'nullable', 'string', 'max:1000'],
        ]);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $komunitas = $user->komunitasRelawan;
        abort_unless($komunitas instanceof KomunitasRelawan, 403);

        $hasil = $service->responKomunitas(
            $penugasan,
            $komunitas,
            $data['stage'],
            $data['alasan_tolak'] ?? null
        );
        $pesan = $hasil->stage === 'diterima'
            ? 'Penugasan berhasil diterima.'
            : 'Penugasan ditolak. Tim Startup ALIR akan menerima pembaruan status.';

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $pesan]);
        }

        return back()->with('success', $pesan);
    }

    public function progres(
        Request $request,
        Penugasan $penugasan,
        PenugasanRelawanService $service
    ): JsonResponse|RedirectResponse {
        $data = $request->validate([
            'stage' => ['required', 'in:menuju_lokasi,ditangani,selesai'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $komunitas = $user->komunitasRelawan;
        abort_unless($komunitas instanceof KomunitasRelawan, 403);

        $hasil = $service->perbaruiProgresKomunitas(
            $penugasan,
            $komunitas,
            $data['stage'],
            $data['catatan'] ?? null
        );
        $pesan = 'Progres penugasan diperbarui menjadi '.str_replace('_', ' ', $hasil->stage).'.';

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $pesan]);
        }

        return redirect()
            ->route('komunitas.penugasan.show', $hasil)
            ->with('success', $pesan);
    }
}
