<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusLaporan;
use App\Http\Controllers\Controller;
use App\Models\LaporanBanjir;
use App\Notifications\LaporanBaruAdminNotification;
use App\Services\LaporanBanjirService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PBI-006: Tim Startup ALIR mencari laporan berdasarkan kode unik yang disebutkan pelapor.
 */
class CariLaporanController extends Controller
{
    public function __invoke(Request $request): View
    {
        $request->validate(['kode' => ['nullable', 'string', 'max:30']]);

        $input = trim((string) $request->query('kode', ''));
        $kode = LaporanBanjirService::normalisasiKode($input);
        $laporan = null;

        if ($kode !== '') {
            $laporan = LaporanBanjir::query()
                ->with(['wilayah:id,nama_kelurahan,kecamatan', 'foto'])
                ->where('kode_pelacakan', $kode)
                ->first();

            if ($laporan) {
                $this->tandaiNotifikasiDibaca($request, $laporan);
            }
        }

        $notifikasi = $request->user()->unreadNotifications()
            ->where('type', LaporanBaruAdminNotification::class)
            ->latest()->limit(8)->get();

        return view('admin.laporan.cari', [
            'input' => $input,
            'kode' => $kode,
            'laporan' => $laporan,
            'statusLabel' => $laporan ? StatusLaporan::labelDari($laporan->status) : null,
            'notifikasi' => $notifikasi,
            'terbaru' => LaporanBanjir::query()
                ->with('wilayah:id,nama_kelurahan')
                ->latest()->limit(8)
                ->get(['id', 'kode_pelacakan', 'wilayah_id', 'severity', 'status', 'created_at']),
        ]);
    }

    private function tandaiNotifikasiDibaca(Request $request, LaporanBanjir $laporan): void
    {
        $request->user()->unreadNotifications()
            ->where('type', LaporanBaruAdminNotification::class)
            ->get()
            ->filter(fn ($n) => ($n->data['laporan_banjir_id'] ?? null) === $laporan->id)
            ->each->markAsRead();
    }
}
